<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Services\DynamicChannelCredentialService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;



class SendWhatsAppMessageJob implements ShouldQueue
{

    use Queueable, Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected $chatId;
    protected $messageContent;
    protected $userId;

    public function __construct(string $chatId, string $messageContent, int $userId)
    {
        $this->chatId = $chatId;
        $this->messageContent = $messageContent;
        $this->userId = $userId;
    }

    public function handle()
    {
        // Find user to retrieve credentials
        $user = User::find($this->userId);
        if (!$user) {
            Log::error('SendWhatsAppMessageJob: User not found', ['user_id' => $this->userId]);
            return;
        }

        // Retrieve credentials via your service
        $credentialService = new DynamicChannelCredentialService($user, 'whatsapp');
        $instanceId = $credentialService->getInstanceId();    // implement to return your Green-API instanceId
        $apiToken   = $credentialService->getApiToken();      // return apiTokenInstance
        $apiUrl     = $credentialService->getApiUrl()
            ?? config('services.greenapi.url', 'https://api.green-api.com');

        if (!$instanceId || !$apiToken) {
            Log::error('SendWhatsAppMessageJob: Missing Green-API credentials', [
                'user_id' => $this->userId,
                'instanceId' => $instanceId,
                'apiToken' => $apiToken,
            ]);
            return;
        }

        // Build the Green-API sendMessage endpoint
        $endpoint = rtrim($apiUrl, '/')
            . "/waInstance{$instanceId}/sendMessage/{$apiToken}";

        $payload = [
            'chatId' => $this->chatId,
            'message' => $this->messageContent,
            // if you want to support quotedMessageId: add 'quotedMessageId' => ...
        ];

        try {
            Log::info('SendWhatsAppMessageJob: Sending request', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'user_id' => $this->userId,
            ]);

            $response = Http::post($endpoint, $payload);

            $status = $response->successful() ? 'sent' : 'failed';
            $responseData = $response->json();

            // Extract Green-API's message ID: usually in response: { idMessage: "..." }
            $externalMessageId = data_get($responseData, 'idMessage');
            if (!$externalMessageId) {
                Log::warning('SendWhatsAppMessageJob: missing idMessage in response', [
                    'response' => $responseData,
                ]);
            }

            // Store the outgoing message in your messages table
            $msg = Message::create([
                // adjust fields according to your table columns
                'chat_id'             => $this->chatId,
                'from'                => 'system',
                'to'                  => $this->chatId,
                'body'                => $this->messageContent,
                'message_type'        => 'text',
                'wa_message_id'       => $externalMessageId,
                'direction'           => 'outgoing',
                'message_status'      => $status,
                'external_message_id' => $externalMessageId,
                'response_payload'    => $responseData,
                // polymorphic link:
                'messageable_id'      => $user->id,
                'messageable_type'    => get_class($user),
                'timestamp'           => now(),
            ]);

            Log::info('SendWhatsAppMessageJob: Message record created', [
                'id' => $msg->id,
                'chat_id' => $this->chatId,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendWhatsAppMessageJob: Exception when sending message', [
                'chatId' => $this->chatId,
                'error' => $e->getMessage(),
            ]);

            // Optionally: record a failed message entry
            Message::create([
                'chat_id'             => $this->chatId,
                'from'                => 'system',
                'to'                  => $this->chatId,
                'body'                => $this->messageContent,
                'message_type'        => 'text',
                'direction'           => 'outgoing',
                'message_status'      => 'failed',
                'error_message'       => $e->getMessage(),
                'messageable_id'      => $user->id,
                'messageable_type'    => get_class($user),
                'timestamp'           => now(),
            ]);
        }
    }
}
