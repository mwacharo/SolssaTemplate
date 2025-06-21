<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
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
                break;

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
        $text     = $payload['messageData']['textMessageData']['textMessage'] ?? null;
        $quoted   = $payload['messageData']['quotedMessage']['message'] ?? null;
        $quotedId = $payload['messageData']['quotedMessage']['stanzaId'] ?? null;
        $type     = $payload['messageData']['typeMessage'] ?? 'textMessage';
        $timestamp = now();

        if (!$chatId || !$text) {
            Log::warning('🚫 Invalid message payload', $payload);
            return response()->json(['error' => 'Missing data'], 400);
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
        $text = $payload['messageData']['textMessageData']['textMessage'] ?? null;

        if (!$chatId || !$text) {
            return response()->json(['error' => 'Missing outgoing data'], 400);
        }

        $msg = Message::updateOrCreate(
            ['wa_message_id' => $payload['idMessage']],
            [
                'chat_id' => $chatId,
                'from' => 'system',
                'to' => $chatId,
                'body' => $text,
                'message_type' => $payload['messageData']['typeMessage'] ?? 'text',
                'timestamp' => now(),
                'direction' => 'outgoing',
                'message_status' => 'sent',
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

        $msg = Message::where('wa_message_id', $idMessage)->first();

        if ($msg) {
            $msg->message_status = $status;
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
}
