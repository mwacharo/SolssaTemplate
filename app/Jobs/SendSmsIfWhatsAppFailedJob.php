<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendSmsIfWhatsAppFailedJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $phone,
        public int $userId
    ) {}

    public function handle()
    {
        $chatId = $this->phone . '@c.us';
        Log::debug("Checking WhatsApp delivery for chatId: {$chatId}, userId: {$this->userId}");

        $recentMessage = Message::where('to', $chatId)
            ->where('messageable_id', $this->userId)
            ->latest()
            ->first();

        Log::debug('Recent message:', [
            'exists' => (bool)$recentMessage,
            'message_status' => $recentMessage?->message_status ?? null,
            'message_id' => $recentMessage?->id ?? null,
        ]);

        if (!$recentMessage || $recentMessage->message_status !== 'sent') {
            Log::debug("WhatsApp failed or no record found. Dispatching SMS job.", [
                'recipients' => $this->phone,
                'userId' => $this->userId,
            ]);
            AdvantaSmsJob::dispatch(
                recipients: $this->phone,
                messageContent: "We tried calling you about your order. Please call us back.",
                userId: $this->userId
            );
            Log::info("SMS fallback sent to {$this->phone}");
        } else {
            Log::info("WhatsApp delivered to {$this->phone}, SMS fallback skipped.");
        }
    }
}
