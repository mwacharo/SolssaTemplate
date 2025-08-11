<?php


namespace App\Services;


use App\Jobs\AdvantaSmsJob;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdvantaSmsService
{
    protected int $delayMinutes = 0;

    /**
     * Send a single sms message immediately.
     */
    public function sendSingleMessage(array $data): array
    {
        $validator = Validator::make($data, [
            'chat_id'   => 'required|string',
            'message'   => 'required|string',
            'user_id'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'errors' => $validator->errors(),
            ];
        }

     AdvantaSmsJob:dispatch($data['chat_id'], $data['message'], $data['user_id']);

        Log::info('Single sms dispatched', [
            'chat_id' => $data['chat_id'],
            'user_id' => $data['user_id'],
        ]);

        return [
            'status'  => 'success',
            'message' => 'Single sms message queued.',
        ];
    }

    /**
     * Send bulk sms messages with progressive delay.
     */
    public function sendBulkMessages(array $data)
    {
        Log::info('sendBulkMessages started', [
            'data' => $data,
        ]);
        $delayMinutes = $this->delayMinutes;
        $counter = 0;
        $queued = 0;
        $recipients = [];

        $validator = Validator::make($data, [
            'message'       => 'required|string',
            'user_id'       => 'required|integer',
            'order_ids'     => 'nullable|array',
            'contact_ids'   => 'nullable|array',
            'contacts'      => 'nullable|array',
            // Accept chatId or phone_number or phone for each contact
            'contacts.*.chatId'      => 'required_without_all:contacts.*.phone_number,contacts.*.phone|string',
            'contacts.*.phone_number'=> 'required_without_all:contacts.*.chatId,contacts.*.phone|string',
            'contacts.*.phone'       => 'required_without_all:contacts.*.chatId,contacts.*.phone_number|string',
            'template_id'   => 'nullable|integer',
            'template'      => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = $data['user_id'];
        $messageTemplate = !empty($data['template']['content'])
            ? $data['template']['content']
            : $data['message'];

        // 1. Orders' clients
        if (!empty($data['order_ids'])) {
            $orders = Order::with('client')->whereIn('id', $data['order_ids'])->get();
            foreach ($orders as $order) {
                $client = $order->client;
                $phone = $client?->phone_number ?? $client?->alt_phone_number;
                if ($phone) {
                    $recipients[] = [
                        'chat_id' => $this->$phone,
                        'message' => $this->processMessagePlaceholders($messageTemplate, $order, $client),
                        'meta'    => [
                            'type' => 'order_client',
                            'order_id' => $order->id,
                            'phone' => $phone,
                        ],
                    ];
                }
            }
        }

        // 2. User's saved contacts
        if (!empty($data['contact_ids'])) {
            $user = User::with(['contacts' => function ($q) use ($data) {
                $q->whereIn('id', $data['contact_ids']);
            }])->find($userId);

            foreach ($user?->contacts ?? [] as $contact) {
                $phone = $contact->phone ?? $contact->alt_phone;
                if ($phone) {
                    $recipients[] = [
                        'chat_id' => $this->$phone,
                        'message' => $this->processMessagePlaceholders($messageTemplate, null, $contact),
                        'meta'    => [
                            'type' => 'saved_contact',
                            'contact_id' => $contact->id,
                            'phone' => $phone,
                        ],
                    ];
                }
            }
        }

        // 3. Direct contacts from request
        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $contactData) {
                // Accept chatId, phone_number, or phone
                $rawChatId = $contactData['chatId'] ?? $contactData['phone_number'] ?? $contactData['phone'] ?? null;
                if ($rawChatId) {
                    $tempContact = (object) [
                        'name'  => $contactData['name'] ?? 'Customer',
                        'phone' => $rawChatId,
                        'id'    => $contactData['id'] ?? null
                    ];
                    $recipients[] = [
                        'chat_id' => $rawChatId,
                        'message' => $this->processMessagePlaceholders($messageTemplate, null, $tempContact),
                        'meta'    => [
                            'type' => 'direct_contact',
                            'contact_name' => $contactData['name'] ?? 'Customer',
                            'phone' => $rawChatId,
                        ],
                    ];
                }
            }
        }

        Log::info('Preparing to dispatch sms bulk messages', [
            'total_recipients' => count($recipients),
            'user_id' => $userId,
            'delay_minutes' => $delayMinutes,
        ]);

        foreach ($recipients as $recipient) {
            $totalDelayMinutes = $delayMinutes * $counter;

            Log::debug('Dispatching sms message', [
                'recipient' => $recipient,
                'user_id' => $userId,
                'delay_minutes' => $totalDelayMinutes,
                'counter' => $counter,
            ]);

            Log::info('Dispatching sms message job', [
                'recipient_chat_id' => $recipient['chat_id'],
                'message' => $recipient['message'],
                'user_id' => $userId,
                'delay_minutes' => $totalDelayMinutes,
                'counter' => $counter,
            ]);
            $job = AdvantaSmsJob::dispatch(
                $recipient['chat_id'],
                $recipient['message'],
                $userId
            );

            if ($counter > 0) {
                $job->delay(now()->addMinutes($totalDelayMinutes));
                Log::debug('Job delayed', [
                    'delay_until' => now()->addMinutes($totalDelayMinutes)->toDateTimeString(),
                    'recipient_chat_id' => $recipient['chat_id'],
                ]);
            }

            Log::info('sms message dispatched', [
                'recipient_phone' => $recipient['meta']['phone'],
                'chat_id' => $recipient['chat_id'],
                'delay_minutes' => $totalDelayMinutes,
                'scheduled_at' => $counter == 0 ? 'immediate' : now()->addMinutes($totalDelayMinutes)->toDateTimeString(),
                'counter' => $counter,
                'recipient_type' => $recipient['meta']['type'],
                ...$recipient['meta'],
            ]);

            $counter++;
            $queued++;
        }

        Log::info('sendBulkMessages completed', [
            'queued_count' => $queued,
            'total_delay_span_minutes' => $counter > 0 ? ($counter - 1) * $delayMinutes : 0
        ]);

        return response()->json([
            'status'        => 'success',
            'queued_count'  => $queued,
            'message'       => "Queued $queued sms messages with {$delayMinutes} min gap.",
            'delay_info'    => [
                'delay_minutes' => $delayMinutes,
                'total_recipients' => $queued,
                'last_message_delay' => $counter > 0 ? ($counter - 1) * $delayMinutes : 0
            ]
        ]);
    }

    /**
     * Format phone number to sms chatId.
     */
    // protected function formatChatId($phone): string
    // {
    //     return preg_replace('/\D/', '', $phone) . '@c.us';
    // }

  
    private function processMessagePlaceholders($messageTemplate, $order = null, $contact = null)
    {
        $placeholders = [];

        // Log the order object if provided
        if ($order) {
            Log::debug('processMessagePlaceholders received order', [
                'order_id' => $order->id ?? null,
                'order' => $order
            ]);
        }

        // Contact/Client placeholders
        if ($contact) {
            $placeholders['customer_name'] = $contact->name ?? 'Customer';
            $placeholders['client_name'] = $contact->name ?? 'Customer';
            $placeholders['customer_phone'] = $contact->phone ?? $contact->phone_number ?? '';
        }

        // Order placeholders
        if ($order) {
            $placeholders['order_no'] = $order->order_no ?? $order->no ?? '';
            // $placeholders['product_name'] = $order->product_name ?? $order->product ?? '';

            $placeholders['order_number'] = $order->order_number ?? $order->number ?? '';
            // $placeholders['price'] = $order->total_price ?? $order->amount ?? '';
            $placeholders['tracking_id'] = $order->tracking_id ?? $order->tracking_number ?? '';
            $placeholders['total_price'] = $order->total_price ?? '';
            $placeholders['delivery_date'] = $order->delivery_date ?? '';
            // \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '';
            $placeholders['status'] = $order->status ?? '';
            $placeholders['agent_name'] = $order->agent_name ?? '';
            $placeholders['vendor_name'] = $order->vendor_name ?? '';
            $placeholders['website_url'] = $order->website_url ?? '';
            $placeholders['zone'] = $order->zone ?? '';

            if (!empty($order->orderItems) && is_iterable($order->orderItems)) {
                $itemsList = [];

                foreach ($order->orderItems as $item) {
                    $name = $item->name ?? $item->product_name ?? '';
                    $qty = $item->quantity ?? $item->qty ?? 1;
                    $itemsList[] = "{$qty} x {$name}";
                }

                $placeholders['order_items'] = implode(', ', $itemsList);
            } else {
                $placeholders['order_items'] = '';
            }
        }

        // Replace placeholders in the message
        $processedMessage = $messageTemplate;
        foreach ($placeholders as $key => $value) {
            // Handle both {{placeholder}} and {{ placeholder }} formats
            $patterns = [
                '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                '/\{\{' . preg_quote($key, '/') . '\}\}/'
            ];

            foreach ($patterns as $pattern) {
                $processedMessage = preg_replace($pattern, $value, $processedMessage);
            }
        }

        // Remove any remaining unmatched placeholders
        $processedMessage = preg_replace('/\{\{[^}]*\}\}/', '', $processedMessage);

        // Clean up extra spaces
        $processedMessage = preg_replace('/\s+/', ' ', trim($processedMessage));

        Log::debug('Placeholder processing', [
            'original' => $messageTemplate,
            'placeholders' => $placeholders,
            'processed' => $processedMessage
        ]);

        return $processedMessage;
    }



}
