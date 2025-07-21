<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message and save it to the database
     *
     * @param string $chatId    WhatsApp chat ID (e.g. 2547xxxxxx@c.us)
     * @param string $message   Text message to send
     * @param int|null $userId  ID of the sending user (optional)
     * @return void
     */
    public function sendMessage(string $chatId, string $message, ?int $userId = null): void
    {
        Log::info('WhatsAppService: Sending message', [
            'chatId' => $chatId,
            'message' => $message,
            'userId' => $userId,
        ]);

        // Store message in the database
        Message::create([
            'chat_id' => $chatId,
            'from' => 'system',
            'to' => $chatId,
            'content' => $message,
            'type' => 'text',
            'timestamp' => now(),
            'messageable_type' => \App\Models\User::class,
            'messageable_id' => $userId ?? 1,
            'status' => 'queued',
        ]);

        // Dispatch job to send the WhatsApp message
        SendWhatsAppMessageJob::dispatch($chatId, $message, $userId ?? 1);
    }
}
