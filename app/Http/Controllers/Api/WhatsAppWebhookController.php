<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

use App\Services\AIResponderService;

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

        // Log all payloads for debugging (disable in production)
        Log::info('🔔 WhatsApp Webhook Received', $payload);

        $type = $payload['typeWebhook'] ?? null;

        if (!$type) {
            return response()->json(['error' => 'Missing webhook type'], 400);
        }

        // Handle different notification types
        switch ($type) {
            case 'incomingMessageReceived':
                return $this->handleIncomingMessage($payload);

                // case 'outgoingAPIMessageReceived':
                //     Log::info('📤 Outgoing message sent', $payload);
                //     break;


            case 'outgoingMessageReceived':
                return $this->handleOutgoing($payload);

            case 'outgoingMessageStatus':
                Log::info('✅ Message status update', $payload);
                return $this->handleStatusUpdate($payload);

                // break;

            case 'stateInstanceChanged':
                Log::info('⚙️ Instance state changed: ' . $payload['stateInstance']);
                break;

            default:
                Log::info("ℹ️ Unhandled webhook type: {$type}");
                break;
        }

        return response()->json(['status' => 'ignored']);
    }

    protected function handleIncomingMessage(array $payload)
    {
        $chatId   = $payload['senderData']['chatId'] ?? null;
        $senderId = $payload['senderData']['senderId'] ?? null;
        $text     = $payload['messageData']['textMessageData']['textMessage'] ?? null;
        $quoted   = $payload['messageData']['quotedMessage']['message'] ?? null;
        $quotedId = $payload['messageData']['quotedMessage']['stanzaId'] ?? null;
        $type     = $payload['messageData']['typeMessage'] ?? 'textMessage';
        $timestamp = now();

        if (!$chatId || !$text) {
            Log::warning('🚫 Invalid message payload', $payload);
            return response()->json(['error' => 'Missing data'], 400);
        }


        // 🔁 Use AIResponderService to generate a reply
        // $ai = new AIResponderService();
        // $reply = $ai->interpretCustomerQuery($text);

        // if ($reply) {
        //     // 🚀 Send reply via WhatsApp (stub)
        //     // $this->sendWhatsAppReply($chatId, $reply);

        //     $this->whatsAppService->sendMessage($chatId, $reply , 1); // Assuming user ID 1 for system messages

        // }



        // 🔍 Find user by chatId (WhatsApp number)
        $user = Client::where('phone_number', $chatId)->first();
        $recentOrders = [];

        if ($user) {
            $recentOrders = $user->orders()
                ->latest()
                ->take(5)
                ->get(['order_no', 'status', 'delivery_date'])
                ->toArray();
        }

        // 🧠 AI interpretation with order context
        $ai = new AIResponderService();
        $reply = $ai->interpretCustomerQuery($text, $recentOrders);

        // 🟢 Send response
        if ($reply) {
            $this->whatsAppService->sendMessage($chatId, $reply, 1); // 1 = System user
        }

        Message::create([
            'chat_id' => $chatId,
            'from' => $chatId,
            'to' => 'system',
            'content' => $text,
            'wa_message_id' => $payload['idMessage'] ?? null,
            'quoted_message_id' => $quotedId,
            'quoted_message_text' => $quoted,
            'type' => $type,
            'timestamp' => $timestamp,
            'messageable_type' => \App\Models\User::class,
            'messageable_id' => 1,

        ]);

        Log::info("💬 Message stored from {$chatId} at {$timestamp}");

        return response()->json(['status' => 'stored']);
    }

    protected function handleOutgoing(array $payload)
    {
        $chatId = $payload['senderData']['chatId'] ?? null;
        $senderId = $payload['senderData']['sender'] ?? null;
        $text = $payload['messageData']['textMessageData']['textMessage'] ?? null;

        if (!$chatId || !$text) {
            return response()->json(['error' => 'Missing outgoing data'], 400);
        }

        // Identify the sender using a helper function
        Log::info("🔍 Identifying sender for chatId: {$senderId}");
        $sender = $this->identifySender($senderId);


        // Optionally, you can use $sender for messageable_type and messageable_id if needed


        // if (!$sender) {
        //     Log::warning("🚫 Sender not found for chat ID: {$chatId}")   ;

        $msg = Message::updateOrCreate(
            // ['wa_message_id' => $payload['idMessage']],
            ['external_message_id' => $payload['idMessage']],
            [
                'chat_id' => $chatId,
                'from' => 'system',
                'to' => $chatId,
                // 'body' => $text,
                'content' => $text,

                'message_type' => $payload['messageData']['typeMessage'] ?? 'text',
                'timestamp' => now(),
                'direction' => 'outgoing',
                'message_status' => 'sent',
                'messageable_type' => \App\Models\User::class,
                'messageable_id' => $sender ? $sender->id : 1, // Default to 1 if sender not found
            ]
        );

        return response()->json(['status' => 'stored_outgoing', 'id' => $msg->id]);
    }

    protected function handleStatusUpdate(array $payload)
    {
        $idMessage = $payload['idMessage'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$idMessage || !$status) {
            return response()->json(['error' => 'Missing status data'], 400);
        }

        $msg = Message::where('external_message_id', $idMessage)->first();

        if ($msg) {
            // $msg->message_status = $status;
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

            return response()->json(['status' => 'updated', 'id' => $msg->id]);
        }

        Log::warning("⚠️ Status update received for unknown message ID: {$idMessage}");

        return response()->json(['warning' => 'Message not found'], 404);
    }

    private function identifySender($senderId)
    {
        // Remove WhatsApp suffix if present (e.g., @c.us)
        $cleanSenderId = preg_replace('/@.*$/', '', $senderId);
        Log::info("identifySender: cleanSenderId = {$cleanSenderId}");

        // Remove leading zeros and plus if present
        $normalized = ltrim($cleanSenderId, '0+');
        Log::info("identifySender: normalized = {$normalized}");

        // Try to match with phone_number in various formats
        // 1. Try with country code (e.g., 254...)
        $user = User::where('phone_number', 'like', "%{$normalized}")->first();
        Log::info("identifySender: user by normalized = " . ($user ? $user->id : 'not found'));
        if ($user) {
            return $user;
        }

        // 2. Try with leading zero (e.g., 07...)
        if (strlen($normalized) > 9 && substr($normalized, 0, 3) === '254') {
            $local = '0' . substr($normalized, 3);
            Log::info("identifySender: local = {$local}");
            $user = User::where('phone_number', $local)->first();
            Log::info("identifySender: user by local = " . ($user ? $user->id : 'not found'));
            if ($user) {
                return $user;
            }
        }

        // 3. Try with senderId directly (for custom mapping)
        return User::where('phone_number', $cleanSenderId)->first();
    }
}
