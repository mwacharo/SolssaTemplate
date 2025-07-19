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
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    protected $chatId;
    protected $messageContent;
    protected $userId;

    public function __construct(string $chatId, string $messageContent, int $userId)
    {
        // Log::info('SendWhatsAppMessageJob: constructor called', [
        //     'chatId' => $chatId,
        //     'userId' => $userId,
        //     'messageContent' => $messageContent,
        // ]);

        $this->chatId = $chatId;
        $this->messageContent = $messageContent;
        $this->userId = $userId;
    }

    public function handle()
    {
        Log::info('SendWhatsAppMessageJob: handle() started', [
            'chatId' => $this->chatId,
            'userId' => $this->userId,
        ]);

        // Find user to retrieve credentials
        $user = User::find($this->userId);

        // Log::info('SendWhatsAppMessageJob: User debugging', [
        //     'user_id' => $this->userId,
        //     'user_found' => $user ? 'yes' : 'no',
        //     'user_class' => $user ? get_class($user) : 'null',
        //     'user_file' => $user ? (new \ReflectionClass($user))->getFileName() : 'null',
        //     'has_method' => $user ? method_exists($user, 'channelCredentials') : 'no user',
        //     'available_methods' => $user ? implode(', ', array_slice(get_class_methods($user), 0, 10)) : 'no user',
        // ]);

        if (!$user) {
            Log::error('SendWhatsAppMessageJob: User not found', ['user_id' => $this->userId]);
            return;
        }

        // Test the relationship directly
        try {
            // Log::info('SendWhatsAppMessageJob: Testing direct relationship call');
            $testCall = $user->channelCredentials();
            // Log::info('SendWhatsAppMessageJob: Direct relationship call successful', [
            //     'relationship_type' => get_class($testCall)
            // ]);
        } catch (\Exception $e) {
            // Log::error('SendWhatsAppMessageJob: Direct relationship call failed', [
            //     'error' => $e->getMessage(),
            //     'file' => $e->getFile(),
            //     'line' => $e->getLine(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return;
        }

        // Now try the service
        try {
            // Log::info('SendWhatsAppMessageJob: About to initialize DynamicChannelCredentialService');
            $credentialService = new DynamicChannelCredentialService($user, 'whatsapp');
            // Log::info('SendWhatsAppMessageJob: DynamicChannelCredentialService initialized successfully');

            $instanceId = $credentialService->getInstanceId();
            $apiToken = $credentialService->getApiToken();
            $apiUrl = $credentialService->getApiUrl() ?? config('services.greenapi.url', 'https://api.green-api.com');

            // Log::info('SendWhatsAppMessageJob: Credentials retrieved', [
            //     'instanceId' => $instanceId,
            //     'apiToken' => $apiToken ? 'present' : 'missing',
            //     'apiUrl' => $apiUrl,
            // ]);

            if (!$instanceId || !$apiToken) {
                // Log::error('SendWhatsAppMessageJob: Missing Green-API credentials', [
                //     'user_id' => $this->userId,
                //     'instanceId' => $instanceId,
                //     'apiToken' => $apiToken ? 'present' : 'missing',
                // ]);
                return;
            }

            // Build the Green-API sendMessage endpoint
            $endpoint = rtrim($apiUrl, '/') . "/waInstance{$instanceId}/sendMessage/{$apiToken}";

            $payload = [
                'chatId' => $this->chatId,
                'message' => $this->messageContent,
            ];

            // Log::info('SendWhatsAppMessageJob: Sending request', [
            //     'endpoint' => $endpoint,
            //     'payload' => $payload,
            // ]);

            $response = Http::post($endpoint, $payload);

            // Log::info('SendWhatsAppMessageJob: Response received', [
            //     'status' => $response->status(),
            //     'body' => $response->body(),
            // ]);

            $status = $response->successful() ? 'sent' : 'failed';
            $responseData = $response->json();
            $externalMessageId = data_get($responseData, 'idMessage');

            // Store the message
            $msg = Message::create([
                'chat_id' => $this->chatId,
                'from' => 'system',
                'to' => $this->chatId,
                'content' => $this->messageContent,
                'message_type' => 'text',
                'wa_message_id' => $externalMessageId,
                'direction' => 'outgoing',
                'message_status' => $status,
                'external_message_id' => $externalMessageId,
                'response_payload' => $responseData,
                'messageable_id' => $user->id,
                'messageable_type' => get_class($user),
                'timestamp' => now(),
            ]);

            // Log::info('SendWhatsAppMessageJob: Message record created', [
            //     'id' => $msg->id,
            //     'chat_id' => $this->chatId,
            //     'status' => $status,
            // ]);
        } catch (\Exception $e) {
            // Log::error('SendWhatsAppMessageJob: Exception in service or HTTP call', [
            //     'chatId' => $this->chatId,
            //     'error' => $e->getMessage(),
            //     'file' => $e->getFile(),
            //     'line' => $e->getLine(),
            //     'trace' => $e->getTraceAsString(),
            // ]);

            // Record failed message
            try {
                Message::create([
                    'chat_id' => $this->chatId,
                    'from' => 'system',
                    'to' => $this->chatId,
                    'content' => $this->messageContent,

                    'message_type' => 'text',
                    'direction' => 'outgoing',
                    'message_status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'messageable_id' => $user->id,
                    'messageable_type' => get_class($user),
                    'timestamp' => now(),
                ]);
            } catch (\Exception $msgException) {
                // Log::error('SendWhatsAppMessageJob: Failed to create error message record', [
                //     'error' => $msgException->getMessage()
                // ]);
            }
        }

        // Log::info('SendWhatsAppMessageJob: handle() finished');
    }
}
