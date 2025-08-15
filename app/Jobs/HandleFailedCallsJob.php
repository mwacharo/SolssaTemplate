<?php

namespace App\Jobs;

use App\Models\CallHistory;
use App\Helpers\WhatsAppHelper;
use App\Helpers\SmsHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HandleFailedCallsJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        Log::info('HandleFailedCallsJob started: Checking for failed calls...');

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

        $calls = CallHistory::where('created_at', '>=', now()->subMinutes(10))
            ->where(function ($q) use ($failedStatuses) {
                $q->whereNull('lastBridgeHangupCause')
                  ->orWhereIn('lastBridgeHangupCause', $failedStatuses)
                  ->whereNull('whatsapp_sent_at'); // Prevent re-sending WA

            })
            ->get();

        Log::info('Found ' . $calls->count() . ' failed calls in the last 10 minutes.');

        foreach ($calls as $call) {
            Log::debug('Processing call', [
                'call_id' => $call->id,
                'clientDialedNumber' => $call->clientDialedNumber,
                'lastBridgeHangupCause' => $call->lastBridgeHangupCause,
                'userId' => $call->userId ?? 35
            ]);

            $call->update(['whatsapp_sent_at' => now()]);


            $phone = $this->normalizeNumber($call->clientDialedNumber);
            Log::debug('Normalized phone number', ['original' => $call->clientDialedNumber, 'normalized' => $phone]);

            if (!$phone) {
                Log::warning('Skipping call due to invalid phone number', ['call_id' => $call->id]);
                continue;
            }

            Log::info('Dispatching SendWhatsAppMessageJob', [
                'chatId' => $phone . "@c.us",
                'userId' => $call->userId ?? 35
            ]);
            SendWhatsAppMessageJob::dispatch(
                chatId: $phone . "@c.us",
                messageContent: "We tried calling you about your order. Please call us back.",
                userId: $call->userId ?? 35
            );

            Log::info('Dispatching SendSmsIfWhatsAppFailedJob with delay', [
                'phone' => $phone,
                'userId' => $call->userId ?? 35,
                'delay_minutes' => 5
            ]);
            SendSmsIfWhatsAppFailedJob::dispatch(
                phone: $phone,
                userId: $call->userId ?? 35
            )->delay(now()->addMinutes(5));
        }

        Log::info('HandleFailedCallsJob finished processing.');
    }

    private function normalizeNumber($number)
    {
        if (!$number) {
            Log::warning('normalizeNumber called with empty number');
            return null;
        }
        $number = preg_replace('/\D/', '', $number);
        if (str_starts_with($number, '0')) {
            $normalized = '254' . substr($number, 1);
        } elseif (str_starts_with($number, '+254')) {
            $normalized = substr($number, 1);
        } elseif (!str_starts_with($number, '254')) {
            $normalized = '254' . $number;
        } else {
            $normalized = $number;
        }
        Log::debug('normalizeNumber result', ['input' => $number, 'output' => $normalized]);
        return $normalized;
    }
}
