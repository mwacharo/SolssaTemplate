<?php

namespace App\Jobs;

use App\Models\CallHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendWhatsAppMessageJob;
use App\Jobs\SendSmsIfWhatsAppFailedJob;

use App\Services\MessageTemplateService;


class HandleFailedCallsJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    public function handle()
    {
        // Log::info('HandleFailedCallsJob started: Checking for failed calls...');

        $failedStatuses = [
            'NO_ANSWER',
            'USER_BUSY',
            'CALL_REJECTED',
            'SUBSCRIBER_ABSENT',
            'NORMAL_TEMPORARY_FAILURE',
            'UNSPECIFIED',
            'RECOVERY_ON_TIMER_EXPIRE',
            'NO_USER_RESPONSE',
            'UNALLOCATED_NUMBER',
            'Aborted'
        ];

        // ✅ Apply whatsapp_sent_at filter OUTSIDE the grouped OR condition
        $calls = CallHistory::where('created_at', '>=', now()->subMinutes(360))
            ->where(function ($q) use ($failedStatuses) {
                $q->whereNull('lastBridgeHangupCause')
                    ->orWhereIn('lastBridgeHangupCause', $failedStatuses);
            })
            ->whereNull('whatsapp_sent_at')
            ->get();

        // Log::info('Found ' . $calls->count() . ' failed calls in the last 10 minutes.');

        foreach ($calls as $call) {

            // if client dialed number is empty check call->callerNumber
            $phone = $call->clientDialedNumber
                ? $this->normalizeNumber($call->clientDialedNumber)
                : $this->normalizeNumber($call->callerNumber);


            if (!$phone) {
                Log::warning('Skipping call due to invalid phone number', ['call_id' => $call->id]);
                continue;
            }

            // ✅ Atomic claim: mark whatsapp_sent_at only if still NULL to avoid double send
            $claimed = CallHistory::whereKey($call->id)
                ->whereNull('whatsapp_sent_at')
                ->update(['whatsapp_sent_at' => now()]);

            if (!$claimed) {
                // Another worker/job already claimed this call
                Log::info('Call already claimed for WhatsApp send, skipping', ['call_id' => $call->id]);
                continue;
            }

            $userId = $call->userId ?? 1;
            $chatId = $phone . '@c.us';

            Log::info('Dispatching SendWhatsAppMessageJob', compact('chatId', 'userId'));
            SendWhatsAppMessageJob::dispatch(
                chatId: $chatId,
                // pass the order details in the and come with messageContent
                messageContent: "We tried calling you about your order. Please call us back.",
                // dynamic message 
                // pass the phone number to message template service 
                // the message template service will fetch the message template from the databas
                // find appropriate orders of client using phone number



                userId: $userId
            );



            // $messageTemplateService = app(MessageTemplateService::class);


            // // Generate personalized message using template service
            // $result = $messageTemplateService->generateMessage(
            //     phone: $phone, // e.g., '254712345678' or '0712345678'
            //     templateId: 3, // Optional: specific template ID
            //     templateSlug: 'order_followup', // Template slug like 'order_followup', 'delivery_reminder'
            //     additionalData: [] // Optional: extra data to merge
            // );

            // SendWhatsAppMessageJob::dispatch(
            //     chatId: $chatId,
            //     messageContent: $result['message'], // Personalized message with all placeholders replaced
            //     userId: $userId
            // );

            // Log::info('Message dispatched with template', [
            //     'phone' => $phone,
            //     'template_used' => $result['template']->name,
            //     'message_preview' => ($result['message'])
            // ]);


            // Schedule conditional SMS fallback (job will check WA status before sending)
            Log::info('Dispatching SendSmsIfWhatsAppFailedJob with delay', [
                'phone' => $phone,
                'userId' => $userId,
                'delay_minutes' => 5
            ]);
            SendSmsIfWhatsAppFailedJob::dispatch(
                phone: $phone,
                userId: $userId
            );
            // ->delay(now()->addMinutes(5));
        }

        // Log::info('HandleFailedCallsJob finished processing.');
    }

    private function normalizeNumber($number)
    {
        if (!$number) return null;
        $number = preg_replace('/\D/', '', $number);

        if (str_starts_with($number, '0')) {
            return '254' . substr($number, 1);
        } elseif (str_starts_with($number, '+254')) {
            return substr($number, 1);
        } elseif (!str_starts_with($number, '254')) {
            return '254' . $number;
        }
        return $number;
    }
}
