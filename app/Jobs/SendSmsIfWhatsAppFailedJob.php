<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

use App\Services\MessageTemplateService;


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


            $messageTemplateService = app(MessageTemplateService::class);


            // Generate personalized message using template service
            $result = $messageTemplateService->generateMessage(
                phone: $this->phone, // e.g., '254712345678' or '0712345678'
                templateId: 3, // Optional: specific template ID
                templateSlug: 'order_followup', // Template slug like 'order_followup', 'delivery_reminder'
                additionalData: [] // Optional: extra data to merge
            );

            AdvantaSmsJob::dispatch(
                recipients: $this->phone,
                // messageContent: "We tried calling you about your order. Please call us back.",
                messageContent: $result['message'], // Personalized message with all placeholders replaced

                userId: $this->userId
            );
            Log::info("SMS fallback sent to {$this->phone}");
        } 
        
        
        else {
            Log::info("WhatsApp delivered to {$this->phone}, SMS fallback skipped.");
        }
    }
}
