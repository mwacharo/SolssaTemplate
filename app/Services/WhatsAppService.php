<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
   

    public function sendMessage(Request $request)
    {
        $delayMinutes = 0; // delay between each message
        $counter = 0;      // progressive delay counter (shared across all loops)
        $queued = 0;

        Log::debug('sendMessage called', ['request' => $request->all()]);

        $request->validate([
            'message'       => 'required|string',
            'user_id'       => 'required|integer',
            'order_ids'     => 'nullable|array',
            'contact_ids'   => 'nullable|array',
            'contacts'      => 'nullable|array',
            'contacts.*.chatId' => 'required_with:contacts|string',
            'template_id'   => 'nullable|integer',
        ]);

        $userId = $request->user_id;
        $messageTemplate = $request->message;

        // === 1. Orders' clients ===
        if ($request->filled('order_ids')) {
            $orders = Order::with('client')->whereIn('id', $request->order_ids)->get();

            foreach ($orders as $order) {
                $client = $order->client;
                $phone = $client?->phone_number ?? $client?->alt_phone_number;

                if (!$phone) {
                    Log::warning('No phone for order client', ['order_id' => $order->id]);
                    continue;
                }

                $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, $order, $client);
                $chatId = preg_replace('/\D/', '', $phone) . '@c.us';

                // Calculate progressive delay (0 min for first, 5 min for second, 10 min for third, etc.)
                $totalDelayMinutes = $delayMinutes * $counter;

                // Dispatch the job with progressive delay
                if ($counter == 0) {
                    // First message - no delay
                    SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId);
                } else {
                    // Subsequent messages - add progressive delay
                    SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
                        ->delay(now()->addMinutes($totalDelayMinutes));
                }

                // Log the dispatch event
                Log::info('WhatsApp message dispatched', [
                    'recipient_phone' => $phone,
                    'chat_id' => $chatId,
                    'delay_minutes' => $totalDelayMinutes,
                    'scheduled_at' => $counter == 0 ? 'immediate' : now()->addMinutes($totalDelayMinutes)->toDateTimeString(),
                    'counter' => $counter,
                    'recipient_type' => 'order_client',
                    'order_id' => $order->id
                ]);

                $counter++;
                $queued++;
            }
        }

        // === 2. User's saved contacts ===
        if ($request->filled('contact_ids')) {
            $user = User::with(['contacts' => function ($q) use ($request) {
                $q->whereIn('id', $request->contact_ids);
            }])->find($userId);

            foreach ($user?->contacts ?? [] as $contact) {
                $phone = $contact->phone ?? $contact->alt_phone;
                if (!$phone) {
                    Log::warning('No phone for saved contact', ['contact_id' => $contact->id]);
                    continue;
                }

                $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, null, $contact);
                $chatId = preg_replace('/\D/', '', $phone) . '@c.us';

                // Calculate progressive delay (0 min for first, 5 min for second, 10 min for third, etc.)
                $totalDelayMinutes = $delayMinutes * $counter;

                // Dispatch the job with progressive delay
                if ($counter == 0) {
                    // First message - no delay
                    SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId);
                } else {
                    // Subsequent messages - add progressive delay
                    SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
                        ->delay(now()->addMinutes($totalDelayMinutes));
                }

                // Log the dispatch event
                Log::info('WhatsApp message dispatched', [
                    'recipient_phone' => $phone,
                    'chat_id' => $chatId,
                    'delay_minutes' => $totalDelayMinutes,
                    'scheduled_at' => $counter == 0 ? 'immediate' : now()->addMinutes($totalDelayMinutes)->toDateTimeString(),
                    'counter' => $counter,
                    'recipient_type' => 'saved_contact',
                    'contact_id' => $contact->id
                ]);

                $counter++;
                $queued++;
            }
        }

        // === 3. Direct contacts from request ===
        if ($request->filled('contacts')) {
            foreach ($request->contacts as $contactData) {
                $rawChatId = $contactData['chatId'] ?? null;
                if (!$rawChatId) {
                    Log::warning('Missing chatId for external contact', ['contact' => $contactData]);
                    continue;
                }

                $tempContact = (object) [
                    'name'  => $contactData['name'] ?? 'Customer',
                    'phone' => $rawChatId,
                    'id'    => $contactData['id'] ?? null
                ];

                $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, null, $tempContact);
                $chatId = preg_replace('/\D/', '', $rawChatId) . '@c.us';

                // Calculate progressive delay (0 min for first, 5 min for second, 10 min for third, etc.)
                $totalDelayMinutes = $delayMinutes * $counter;

                // Dispatch the job with progressive delay
                if ($counter == 0) {
                    // First message - no delay
                    SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId);
                } else {
                    // Subsequent messages - add progressive delay
                    SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
                        ->delay(now()->addMinutes($totalDelayMinutes));
                }

                // Log the dispatch event
                Log::info('WhatsApp message dispatched', [
                    'recipient_phone' => $rawChatId,
                    'chat_id' => $chatId,
                    'delay_minutes' => $totalDelayMinutes,
                    'scheduled_at' => $counter == 0 ? 'immediate' : now()->addMinutes($totalDelayMinutes)->toDateTimeString(),
                    'counter' => $counter,
                    'recipient_type' => 'direct_contact',
                    'contact_name' => $contactData['name'] ?? 'Customer'
                ]);

                $counter++;
                $queued++;
            }
        }

        Log::info('sendMessage completed', [
            'queued_count' => $queued,
            'total_delay_span_minutes' => $counter > 0 ? ($counter - 1) * $delayMinutes : 0
        ]);

        return response()->json([
            'status'        => 'success',
            'queued_count'  => $queued,
            'message'       => "Queued $queued WhatsApp messages with {$delayMinutes} min gap.",
            'delay_info'    => [
                'delay_minutes' => $delayMinutes,
                'total_recipients' => $queued,
                'last_message_delay' => $counter > 0 ? ($counter - 1) * $delayMinutes : 0
            ]
        ]);
    }

    // // Helper method (if you don't already have it)
    // private function dispatchWhatsAppJob($chatId, $message, $userId, $delayTime)
    // {
    //     SendWhatsAppMessageJob::dispatch($chatId, $message, $userId)
    //         ->delay($delayTime);
    // }
}
