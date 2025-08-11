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

class AdvantaSmsJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    protected array $recipients;
    protected string $messageContent;
    protected int $userId;

    protected string $baseUrl;
    protected string $defaultSenderId;

    /**
     * @param string|array $recipients Single phone or array of phones
     * @param string       $messageContent
     * @param int          $userId
     */
    public function __construct(string|array $recipients, string $messageContent, int $userId)
    {
        // Normalize to array
        if (is_string($recipients)) {
            $recipients = array_map('trim', explode(',', $recipients));
        }

        $this->recipients     = $recipients;
        $this->messageContent = $messageContent;
        $this->userId         = $userId;

        // $this->baseUrl         = config('services.advanta_sms.base_url', 'https://sms.advantasms.com/api');
        // $this->defaultSenderId = config('services.advanta_sms.sender_id', 'MyCompany');
    }

    public function handle()
    {
        Log::info('AvantaSmsJob started', [
            'recipients' => $this->recipients,
            'userId'     => $this->userId,
        ]);

        $user = User::find($this->userId);

        if (!$user) {
            Log::error('AvantaSmsJob: User not found', ['userId' => $this->userId]);
            return;
        }

        try {
            // Keep DynamicChannelCredentialService
            $credentialService = new DynamicChannelCredentialService($user, 'Advanta');

            $apiKey   = $credentialService->getApiToken();  // Advanta API key
            $senderId = $credentialService->getInstanceId() ?? $this->defaultSenderId;
            $apiUrl   = rtrim($credentialService->getApiUrl() ?? $this->baseUrl, '/');

            if (!$apiKey) {
                Log::error('AvantaSmsJob: Missing Advanta API key', [
                    'userId' => $this->userId,
                ]);
                return;
            }

            // Decide endpoint: single or bulk
            if (count($this->recipients) === 1) {
                $endpoint = "{$apiUrl}/send-sms";
                $payload  = [
                    'to'       => $this->recipients[0],
                    'message'  => $this->messageContent,
                    'senderId' => $senderId,
                ];
            } else {
                $endpoint = "{$apiUrl}/bulk-sms";
                $payload  = [
                    'messages' => collect($this->recipients)->map(function ($number) use ($senderId) {
                        return [
                            'to'       => $number,
                            'message'  => $this->messageContent,
                            'senderId' => $senderId,
                        ];
                    })->toArray(),
                ];
            }

            $response = Http::withHeaders([
                'apiKey'       => $apiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            $status       = $response->successful() ? 'sent' : 'failed';
            $responseData = $response->json();

            // Store messages
            if (count($this->recipients) === 1) {
                $externalId = data_get($responseData, 'messageId');

                Message::create([
                    'chat_id'             => $this->recipients[0],
                    'from'                => $senderId,
                    'to'                  => $this->recipients[0],
                    'content'             => $this->messageContent,
                    'message_type'        => 'sms',
                    'direction'           => 'outgoing',
                    'message_status'      => $status,
                    'external_message_id' => $externalId,
                    'response_payload'    => $responseData,
                    'messageable_id'      => $user->id,
                    'messageable_type'    => get_class($user),
                    'timestamp'           => now(),
                ]);
            } else {
                foreach ($responseData['messages'] ?? [] as $msg) {
                    $number     = data_get($msg, 'to');
                    $externalId = data_get($msg, 'messageId');
                    $msgStatus  = data_get($msg, 'status', $status);

                    Message::create([
                        'chat_id'             => $number,
                        'from'                => $senderId,
                        'to'                  => $number,
                        'content'             => $this->messageContent,
                        'message_type'        => 'sms',
                        'direction'           => 'outgoing',
                        'message_status'      => $msgStatus,
                        'external_message_id' => $externalId,
                        'response_payload'    => $msg,
                        'messageable_id'      => $user->id,
                        'messageable_type'    => get_class($user),
                        'timestamp'           => now(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('AvantaSmsJob: Exception during send', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            foreach ($this->recipients as $number) {
                Message::create([
                    'chat_id'         => $number,
                    'from'            => $this->defaultSenderId,
                    'to'              => $number,
                    'content'         => $this->messageContent,
                    'message_type'    => 'sms',
                    'direction'       => 'outgoing',
                    'message_status'  => 'failed',
                    'error_message'   => $e->getMessage(),
                    'messageable_id'  => $user->id,
                    'messageable_type'=> get_class($user),
                    'timestamp'       => now(),
                ]);
            }
        }

        Log::info('AvantaSmsJob finished');
    }
}
