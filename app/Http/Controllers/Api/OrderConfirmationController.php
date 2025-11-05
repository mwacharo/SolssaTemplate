<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderConfirmationController extends Controller
{
    


public function submit(Request $request, $order_no)
{
    logger()->info('OrderConfirmationController@submit - incoming request', [
        'order_no'   => $order_no,
        'payload'    => $request->all(),
        'ip'         => $request->ip(),
        'user_agent' => $request->header('User-Agent'),
    ]);

    $data = $request->validate([
        'address' => 'required|string',
        'delivery_date' => 'required|date',
        'callback' => 'nullable|string',
        'notes' => 'nullable|string',
    ]);

    $callback = $data['callback'] ?? 'N/A';
    $notes    = $data['notes'] ?? '-';

$telegramMessage = <<<MSG
📦 *Order Confirmation*
---------------------
*Order No:* {$order_no}
*Address:* {$data['address']}
*Delivery Date:* {$data['delivery_date']}
*Callback:* {$callback}
*Notes:* {$notes}
MSG;




        // Save to DB (messages table)
        $message = Message::create([
            'messageable_type' => 'App\Models\User',
            'messageable_id'   => 1,
            'channel'          => 'telegram',
            'recipient_name'   => null,
            'recipient_phone'  => null,
            'content'          => $telegramMessage,
            'message_type'     => 'text',
            'direction'        => 'outgoing',
            'message_status'   => 'sent',
            'timestamp'        => now(),
            'order_id'         => $order_no,
        ]);

        // Send to Telegram
        $this->sendTelegramMessage(env('TELEGRAM_GROUP_CHAT_ID'), $telegramMessage);

        // update the order

        Log::info("✅ Telegram confirmation sent", ['order_no' => $order_no]);

        return response()->json(['success' => true, 'message' => 'Confirmation submitted successfully']);
    }


    private function sendTelegramMessage($chatId, $text)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url   = "https://api.telegram.org/bot{$token}/sendMessage";

        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ];

        try {
            Log::debug('Telegram send - request', ['url' => $url, 'payload' => $payload]);

            $response = Http::timeout(10)->post($url, $payload);

            Log::debug('Telegram send - response', [
                'status'     => $response->status(),
                'successful' => $response->successful(),
                'body'       => $response->body(),
            ]);

            if (! $response->successful()) {
                Log::error('Telegram API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }

            return $response;
        } catch (\Throwable $e) {
            Log::error('Telegram send failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return null;
        }
    }
}
