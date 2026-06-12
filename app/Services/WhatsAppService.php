<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class WhatsAppService
{


    public function sendMessage($chatId, $message, $userId, $orderId = null)
    {
        // Dispatch the WhatsApp message job immediately
        SendWhatsAppMessageJob::dispatch($chatId, $message, $userId, ['order_id' => $orderId]);

        // Optionally log the dispatch event
        Log::info('WhatsApp message dispatched', [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'order_id' => $orderId
        ]);
    }
}
