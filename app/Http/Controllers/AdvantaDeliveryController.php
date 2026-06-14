<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdvantaDeliveryController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Advanta Delivery Callback', $request->all());

        $messageId = $request->messageid;

        if (!$messageId) {
            return response()->json([
                'success' => false,
                'message' => 'Message ID missing'
            ], 400);
        }

        $message = Message::query()
            ->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(response_payload, '$.responses[0].messageid')) = ?",
                [$messageId]
            )
            ->first();

        if (!$message) {
            Log::warning('Advanta message not found', [
                'messageid' => $messageId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        $message->update([
            'message_status' => 'delivered',
            'delivered_at' => $request->timestamp ?? now(),
            'delivery_payload' => json_encode($request->all()),
            // 'message_status' => $request->response-description ?? null,
        ]);

        return response()->json([
            'success' => true
        ]);
    }
}