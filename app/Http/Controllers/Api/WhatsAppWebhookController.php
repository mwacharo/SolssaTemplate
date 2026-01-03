<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Services\AIResponderService;
use App\Services\IntelligentSupportService;
use App\Services\WhatsAppService;

class WhatsAppWebhookController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('🔔 WhatsApp Webhook Received', $payload);

        try {
            $type = $payload['typeWebhook'] ?? 'undefined';

            switch ($type) {
                case 'incomingMessageReceived':
                    return $this->handleIncomingMessage($payload);

                case 'outgoingMessageReceived':
                    return $this->handleOutgoing($payload);

                case 'outgoingMessageStatus':
                    return $this->handleStatusUpdate($payload);

                case 'stateInstanceChanged':
                    Log::info('⚙️ Instance state changed', [
                        'state' => $payload['stateInstance'] ?? 'unknown'
                    ]);
                    break;


                case 'incomingCall':
                    $this->handleIncomingCall($payload);
                    break;

                default:
                    Log::warning("⚠️ Unhandled or unknown webhook type: {$type}", $payload);
                    break;
            }

            // return response()->json(['status' => 'received']);

            return response()->json(['status' => 'ok'], 200);
        } catch (\Throwable $e) {
            Log::error('❌ Webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            // return response()->json(['error' => 'Webhook processing failed'], 500);
            return response()->json(['message' => 'Message not found'], 200); // Return 200 anyway

        }
    }


         $client = Customer::where('phone', $phone)->first();

            $orders = $client->orders;

    protected function handleOutgoing(array $payload)
    {
        $chatId = data_get($payload, 'senderData.chatId');
        $senderId = data_get($payload, 'senderData.sender');
        $text = data_get($payload, 'messageData.textMessageData.textMessage');

        if (!$chatId || !$text) {
            return response()->json(['error' => 'Missing outgoing data'], 400);
        }

        Log::info("🔍 Identifying sender for chatId: {$senderId}");
        $sender = $this->identifySender($senderId);

        try {
            $msg = Message::updateOrCreate(
                ['external_message_id' => data_get($payload, 'idMessage')],
                [
                    'chat_id' => $chatId,
                    'from' => 'system',
                    'to' => $chatId,
                    'content' => $text,
                    'message_type' => data_get($payload, 'messageData.typeMessage', 'text'),
                    'timestamp' => now(),
                    'direction' => 'outgoing',
                    'message_status' => 'sent',
                    'messageable_type' => \App\Models\User::class,
                    'messageable_id' => $sender ? $sender->id : 1,
                ]
            );

            return response()->json(['status' => 'stored_outgoing', 'id' => $msg->id]);
        } catch (\Throwable $e) {
            Log::error("❌ Failed to store outgoing message: " . $e->getMessage());
            // return response()->json(['error' => 'Failed to store outgoing'], 500);

            return response()->json(['message' => 'Message not found'], 200); // Return 200 anyway

        }
    }


    protected function handleStatusUpdate(array $payload)
    {
        $idMessage = data_get($payload, 'idMessage');
        $status = data_get($payload, 'status');

        if (!$idMessage || !$status) {
            Log::warning("❌ Missing status update data: ", $payload);
            return response()->json(['error' => 'Missing status data'], 200); // Return 200 anyway to satisfy Green API
        }

        $msg = Message::where('external_message_id', $idMessage)
            ->first();

        if ($msg) {
            $msg->status = $status;
            $msg->timestamp = now();

            if ($status === 'delivered') {
                $msg->delivered_at = now();
            }

            if ($status === 'read') {
                $msg->read_at = now();
            }

            if ($status === 'failed') {
                $msg->failed_at = now();
            }

            $msg->save();

            return response()->json(['status' => 'updated', 'id' => $msg->id], 200);
        }

        Log::warning("⚠️ Status update received for unknown message ID: {$idMessage}");

        // return response()->json(['warning' => 'Message not found'], 200); // Green API expects 200 regardless

        return response()->json(['message' => 'Message not found'], 200); // Return 200 anyway

    }


    private function identifyClient($chatId)
    {
        $cleanChatId = preg_replace('/@.*$/', '', $chatId);
        $normalized = ltrim($cleanChatId, '0+');

        $client = Client::where('phone_number', 'like', "%{$normalized}")->first();
        if ($client) return $client;

        if (strlen($normalized) > 9 && str_starts_with($normalized, '254')) {
            $local = '0' . substr($normalized, 3);
            $client = Client::where('phone_number', $local)->first();
            if ($client) return $client;
        }

        return Client::where('phone_number', $cleanChatId)->first();
    }

    private function identifySender($senderId)
    {
        $cleanSenderId = preg_replace('/@.*$/', '', $senderId);
        $normalized = ltrim($cleanSenderId, '0+');

        $user = User::where('phone_number', 'like', "%{$normalized}")->first();
        if ($user) return $user;

        if (strlen($normalized) > 9 && str_starts_with($normalized, '254')) {
            $local = '0' . substr($normalized, 3);
            $user = User::where('phone_number', $local)->first();
            if ($user) return $user;
        }

        return User::where('phone_number', $cleanSenderId)->first();
    }



    protected function handleIncomingCall(array $payload)
    {

        // log the payload
        Log::info('📞 Incoming WhatsApp call received', $payload);
        $from = $payload['from'] ?? null;

        if ($from) {
            // Send auto-reply
            $this->whatsAppService->sendMessage($from, "👋 Sorry, I am an AI agent and currently I handle all SMS queries only. Please send us a message here.", 35);
        }
    }
}
