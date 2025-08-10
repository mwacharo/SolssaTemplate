<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

use App\Services\GreenApiService;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use Illuminate\Support\Facades\Bus;






class WhatsAppController extends Controller
{


    protected $greenApi;





    public function __construct(GreenApiService $greenApi)
    {
        $this->greenApi = $greenApi;
    }



    public function getState()
    {
        $state = $this->greenApi->getInstanceState();

        if (isset($state['error'])) {
            return response()->json(['status' => 'error', 'message' => $state['message']], 500);
        }

        return response()->json([
            'status' => 'success',
            'instance_state' => $state['stateInstance'],
        ]);
    }



    // public function sendMessage(Request $request)
    // {

    //     $delayMinutes = 5;
    //     $counter = 0;
    //     $index = 0; // Start counting from zero

    //     Log::debug('sendMessage called', ['request' => $request->all()]);




    //     $request->validate([
    //         'message' => 'required|string',
    //         'user_id' => 'required|integer',
    //         'order_ids' => 'nullable|array',
    //         'contact_ids' => 'nullable|array',
    //         'contacts' => 'nullable|array',
    //         'contacts.*.chatId' => 'required_with:contacts|string',
    //         'template_id' => 'nullable|integer',
    //     ]);

    //     $userId = $request->user_id;
    //     $messageTemplate = $request->message;
    //     $templateId = $request->template_id;
    //     $queued = 0;

    //     // 1. Send to orders' clients
    //     if ($request->filled('order_ids')) {
    //         Log::debug('Processing order_ids', ['order_ids' => $request->order_ids]);

    //         $orders = Order::with('client')->whereIn('id', $request->order_ids)->get();

    //         foreach ($orders as $order) {
    //             $client = $order->client;
    //             Log::debug('Processing order client', ['order_id' => $order->id, 'client' => $client]);

    //             $phone = $client?->phone_number ?? $client?->alt_phone_number;
    //             if (!$phone) {
    //                 Log::warning('No phone found for client', ['order_id' => $order->id, 'client_id' => $client?->id]);
    //                 continue;
    //             }

    //             // Process message with order-specific placeholders
    //             $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, $order, $client);

    //             $chatId = preg_replace('/[^0-9]/', '', $phone) . '@c.us';
    //             Log::info('Dispatching WhatsApp message job (order)', [
    //                 'chatId' => $chatId,
    //                 'userId' => $userId,
    //                 'original_message' => $messageTemplate,
    //                 'personalized_message' => $personalizedMessage
    //             ]);

    //             // SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId);
    //             // $queued++;

    //             Log::info('Dispatching delayed job', [
    //                 'delay' => now()->addMinutes(5),
    //                 'job' => 'SendWhatsAppMessageJob',
    //                 'user_id' => $userId
    //             ]);

    //             SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
    //                 // ->delay($delayMinutes * $counter);
    //                         ->delay(now()->addMinutes($delayMinutes * $index));

    //             $counter++;
    //             $queued++;
    //         }
    //     }

    //     // 2. Send to user's saved contacts
    //     if ($request->filled('contact_ids')) {
    //         Log::debug('Processing contact_ids', ['contact_ids' => $request->contact_ids]);

    //         $user = User::with(['contacts' => function ($q) use ($request) {
    //             $q->whereIn('id', $request->contact_ids);
    //         }])->find($userId);

    //         foreach ($user?->contacts ?? [] as $contact) {
    //             Log::debug('Processing user contact', ['contact_id' => $contact->id, 'contact' => $contact]);

    //             $phone = $contact->phone ?? $contact->alt_phone;
    //             if (!$phone) {
    //                 Log::warning('No phone found for contact', ['contact_id' => $contact->id]);
    //                 continue;
    //             }

    //             // Process message with contact-specific placeholders
    //             $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, null, $contact);

    //             $chatId = preg_replace('/[^0-9]/', '', $phone) . '@c.us';
    //             Log::info('Dispatching WhatsApp message job (saved contact)', [
    //                 'chatId' => $chatId,
    //                 'userId' => $userId,
    //                 'personalized_message' => $personalizedMessage
    //             ]);

    //             // SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId);
    //             // $queued++;

    //             Log::info('Dispatching delayed job', [
    //                 'delay' => now()->addMinutes(5),
    //                 'job' => 'SendWhatsAppMessageJob',
    //                 'user_id' => $userId
    //             ]);

    //             SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
    //                 ->delay($delayMinutes * $counter);
    //             $counter++;
    //             $queued++;
    //         }
    //     }

    //     // 3. Send to unknown contacts directly via chatId
    //     if ($request->filled('contacts')) {
    //         Log::debug('Processing direct contacts array', ['contacts' => $request->contacts]);

    //         foreach ($request->contacts as $contactData) {
    //             $rawChatId = $contactData['chatId'] ?? null;
    //             if (!$rawChatId) {
    //                 Log::warning('Missing chatId in direct contact', ['contact' => $contactData]);
    //                 continue;
    //             }

    //             // Create a temporary contact object for placeholder processing
    //             $tempContact = (object) [
    //                 'name' => $contactData['name'] ?? 'Customer',
    //                 'phone' => $rawChatId,
    //                 'id' => $contactData['id'] ?? null
    //             ];

    //             // Process message with contact-specific placeholders
    //             $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, null, $tempContact);

    //             $chatId = preg_replace('/[^0-9]/', '', $rawChatId) . '@c.us';

    //             Log::info('Dispatching WhatsApp message job (external contact)', [
    //                 'chatId' => $chatId,
    //                 'userId' => $userId,
    //                 'personalized_message' => $personalizedMessage
    //             ]);

    //             // SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId);
    //             // $queued++;


    //             Log::info('Dispatching delayed job', [
    //                 'delay' => now()->addMinutes(5),
    //                 'job' => 'SendWhatsAppMessageJob',
    //                 'user_id' => $userId
    //             ]);


    //             SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
    //                 ->delay($delayMinutes * $counter);
    //             $counter++;
    //             $queued++;
    //         }
    //     }






    //     Log::info('sendMessage completed', ['queued_count' => $queued]);

    //     return response()->json([
    //         'status' => 'success',
    //         'queued_count' => $queued,
    //         'message' => "Queued $queued WhatsApp messages."
    //     ]);
    // }





    // public function sendMessage(Request $request)
    // {
    //     $delayMinutes = 5; // delay between each message
    //     $counter = 0;      // progressive delay counter (shared across all loops)
    //     $queued = 0;

    //     Log::debug('sendMessage called', ['request' => $request->all()]);

    //     $request->validate([
    //         'message'       => 'required|string',
    //         'user_id'       => 'required|integer',
    //         'order_ids'     => 'nullable|array',
    //         'contact_ids'   => 'nullable|array',
    //         'contacts'      => 'nullable|array',
    //         'contacts.*.chatId' => 'required_with:contacts|string',
    //         'template_id'   => 'nullable|integer',
    //     ]);

    //     $userId = $request->user_id;
    //     $messageTemplate = $request->message;

    //     // === 1. Orders' clients ===
    //     if ($request->filled('order_ids')) {
    //         $orders = Order::with('client')->whereIn('id', $request->order_ids)->get();

    //         foreach ($orders as $order) {
    //             $client = $order->client;
    //             $phone = $client?->phone_number ?? $client?->alt_phone_number;

    //             if (!$phone) {
    //                 Log::warning('No phone for order client', ['order_id' => $order->id]);
    //                 continue;
    //             }

    //             $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, $order, $client);
    //             $chatId = preg_replace('/\D/', '', $phone) . '@c.us';

    //             // Calculate delay for this recipient
    //             $delayTime = Carbon::now()->addMinutes($delayMinutes * $counter);

    //             // Dispatch the job with delay
    //             SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
    //                 ->delay($delayTime);

    //             // Log the dispatch event
    //             Log::info('WhatsApp message dispatched', [
    //                 'recipient_phone' => $phone,
    //                 'chat_id' => $chatId,
    //                 'delay_minutes' => $delayMinutes * $counter,
    //                 'scheduled_at' => $delayTime->toDateTimeString(),
    //                 'counter' => $counter,
    //                 'recipient_type' => 'order_client',
    //                 'order_id' => $order->id
    //             ]);

    //             $counter++;
    //             $queued++;
    //         }
    //     }

    //     // === 2. User's saved contacts ===
    //     if ($request->filled('contact_ids')) {
    //         $user = User::with(['contacts' => function ($q) use ($request) {
    //             $q->whereIn('id', $request->contact_ids);
    //         }])->find($userId);

    //         foreach ($user?->contacts ?? [] as $contact) {
    //             $phone = $contact->phone ?? $contact->alt_phone;
    //             if (!$phone) {
    //                 Log::warning('No phone for saved contact', ['contact_id' => $contact->id]);
    //                 continue;
    //             }

    //             $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, null, $contact);
    //             $chatId = preg_replace('/\D/', '', $phone) . '@c.us';

    //             // Calculate delay for this recipient
    //             $delayTime = Carbon::now()->addMinutes($delayMinutes * $counter);

    //             // Dispatch the job with delay
    //             SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
    //                 ->delay($delayTime);

    //             // Log the dispatch event
    //             Log::info('WhatsApp message dispatched', [
    //                 'recipient_phone' => $phone,
    //                 'chat_id' => $chatId,
    //                 'delay_minutes' => $delayMinutes * $counter,
    //                 'scheduled_at' => $delayTime->toDateTimeString(),
    //                 'counter' => $counter,
    //                 'recipient_type' => 'saved_contact',
    //                 'contact_id' => $contact->id
    //             ]);

    //             $counter++;
    //             $queued++;
    //         }
    //     }

    //     // === 3. Direct contacts from request ===
    //     if ($request->filled('contacts')) {
    //         foreach ($request->contacts as $contactData) {
    //             $rawChatId = $contactData['chatId'] ?? null;
    //             if (!$rawChatId) {
    //                 Log::warning('Missing chatId for external contact', ['contact' => $contactData]);
    //                 continue;
    //             }

    //             $tempContact = (object) [
    //                 'name'  => $contactData['name'] ?? 'Customer',
    //                 'phone' => $rawChatId,
    //                 'id'    => $contactData['id'] ?? null
    //             ];

    //             $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, null, $tempContact);
    //             $chatId = preg_replace('/\D/', '', $rawChatId) . '@c.us';

    //             // Calculate delay for this recipient
    //             $delayTime = Carbon::now()->addMinutes($delayMinutes * $counter);

    //             // Dispatch the job with delay
    //             SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
    //                 ->delay($delayTime);

    //             // Log the dispatch event
    //             Log::info('WhatsApp message dispatched', [
    //                 'recipient_phone' => $rawChatId,
    //                 'chat_id' => $chatId,
    //                 'delay_minutes' => $delayMinutes * $counter,
    //                 'scheduled_at' => $delayTime->toDateTimeString(),
    //                 'counter' => $counter,
    //                 'recipient_type' => 'direct_contact',
    //                 'contact_name' => $contactData['name'] ?? 'Customer'
    //             ]);

    //             $counter++;
    //             $queued++;
    //         }
    //     }

    //     Log::info('sendMessage completed', [
    //         'queued_count' => $queued,
    //         'total_delay_span_minutes' => $counter > 0 ? ($counter - 1) * $delayMinutes : 0
    //     ]);

    //     return response()->json([
    //         'status'        => 'success',
    //         'queued_count'  => $queued,
    //         'message'       => "Queued $queued WhatsApp messages with {$delayMinutes} min gap.",
    //         'delay_info'    => [
    //             'delay_minutes' => $delayMinutes,
    //             'total_recipients' => $queued,
    //             'last_message_delay' => $counter > 0 ? ($counter - 1) * $delayMinutes : 0
    //         ]
    //     ]);
    // }



    // protected function dispatchWhatsAppJob($chatId, $message, $userId, $delayTime)
    // {
    //     Log::info('Dispatching WhatsApp message job', [
    //         'chatId' => $chatId,
    //         'userId' => $userId,
    //         'delay'  => $delayTime
    //     ]);

    //     SendWhatsAppMessageJob::dispatch($chatId, $message, $userId)
    //         ->delay($delayTime);
    // }

    public function sendMessage(Request $request)
    {
        $delayMinutes = 5; // gap between messages
        // $counter = 0; // progressive delay counter

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

        $recipients = [];

        // 1. Orders
        if ($request->filled('order_ids')) {
            $orders = Order::with('client')->whereIn('id', $request->order_ids)->get();
            foreach ($orders as $order) {
                $phone = $order->client?->phone_number ?? $order->client?->alt_phone_number;
                if ($phone) {
                    $recipients[] = [
                        'chatId'  => preg_replace('/\D/', '', $phone) . '@c.us',
                        'message' => $this->processMessagePlaceholders($messageTemplate, $order, $order->client)
                    ];
                }
            }
        }

        // 2. Saved contacts
        if ($request->filled('contact_ids')) {
            $user = User::with(['contacts' => fn($q) => $q->whereIn('id', $request->contact_ids)])->find($userId);
            foreach ($user?->contacts ?? [] as $contact) {
                $phone = $contact->phone ?? $contact->alt_phone;
                if ($phone) {
                    $recipients[] = [
                        'chatId'  => preg_replace('/\D/', '', $phone) . '@c.us',
                        'message' => $this->processMessagePlaceholders($messageTemplate, null, $contact)
                    ];
                }
            }
        }

        // 3. Direct contacts
        if ($request->filled('contacts')) {
            foreach ($request->contacts as $contactData) {
                if (!empty($contactData['chatId'])) {
                    $tempContact = (object) [
                        'name'  => $contactData['name'] ?? 'Customer',
                        'phone' => $contactData['chatId']
                    ];
                    $recipients[] = [
                        'chatId'  => preg_replace('/\D/', '', $contactData['chatId']) . '@c.us',
                        'message' => $this->processMessagePlaceholders($messageTemplate, null, $tempContact)
                    ];
                }
            }
        }

        // Deduplicate and preserve order
        $recipients = collect($recipients)
            ->unique('chatId')
            ->values();

        Log::info('Recipients after deduplication', [
            'recipients' => $recipients->toArray(),
            'count' => $recipients->count()
        ]);



        // Progressive delay logic
        $counter = 0; // Make sure counter starts at 0

        foreach ($recipients as $recipient) {
            $counter++; // Increment at the start of each loop

            // If $recipients is an array of chat IDs
            $chatId = is_array($recipient) ? $recipient['chatId'] : $recipient;

            $delayForThisJob = $delayMinutes * $counter;

            logger()->info("Dispatching job to {$chatId} with delay: {$delayForThisJob} minutes");

            SendWhatsAppMessageJob::dispatch(
                $chatId,
                "Test staggered message {$counter} from Laravel",
                $userId
            )->delay(now()->addMinutes($delayForThisJob));
        }




        return response()->json([
            'status'   => 'success',
            'count'    => count($recipients),
            'interval' => $delayMinutes
        ]);
    }


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



    public function getChat($phone)
    {
        // Check if the phone number already has '@c.us' suffix
        $waId = str_contains($phone, '@c.us') ? $phone : $phone . '@c.us';

        $messages = Message::where(function ($query) use ($waId) {
            $query->where('from', $waId)
                ->orWhere('to', $waId)
                ->orwhere('recipient_phone', $waId);
        })
            ->orderBy('timestamp', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function listConversations()
    {
        // Get the latest message per chat_id using Eloquent
        $latestMessages = Message::select('to as chat_id')
            ->selectRaw('MAX(id) as id')
            ->groupBy('chat_id');

        $conversations = Message::with('messageable')
            ->whereIn('id', $latestMessages->pluck('id'))
            ->orderBy('timestamp', 'desc')
            ->get();

        return MessageResource::collection($conversations);
    }



    /**
     * Retry sending a failed WhatsApp message by message ID.
     */
    public function retryMessage($id)
    {
        Log::debug('retryMessage called', ['message_id' => $id]);


        $message = Message::find($id);
        Log::debug('retryMessage called', ['message' => $message]);


        if (!$message) {
            Log::warning('Message not found for retry', ['message_id' => $id]);
            return response()->json(['status' => 'error', 'message' => 'Message not found.'], 404);
        }

        // if ($message->status !== 'failed') {
        //     Log::info('Attempt to retry non-failed message', [
        //         'message_id' => $id,
        //         'current_status' => $message->status
        //     ]);
        //     return response()->json(['status' => 'error', 'message' => 'Only failed messages can be retried.'], 400);
        // }

        $chatId = $message->to ?? $message->recipient_phone;
        $userId = $message->user_id ?? auth()->id();
        $text = $message->content;

        if (!$chatId || !$text) {
            Log::error('Missing chatId or message body for retry', [
                'message_id' => $id,
                'chatId' => $chatId,
                'text' => $text
            ]);
            return response()->json(['status' => 'error', 'message' => 'Missing chatId or message body.'], 400);
        }

        // Remove any '@c.us' suffix and reformat if needed
        $chatId = preg_replace('/[^0-9]/', '', $chatId) . '@c.us';

        Log::info('Dispatching WhatsApp message job for retry', [
            'chatId' => $chatId,
            'userId' => $userId,
            'message_id' => $id
        ]);

        SendWhatsAppMessageJob::dispatch($chatId, $text, $userId);

        // Optionally update status to 'queued'
        $message->status = 'queued';
        $message->save();

        Log::info('Message retry queued', ['message_id' => $id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Message retry queued.',
            'message_id' => $message->id
        ]);
    }



    /**
     * Get the full message thread for a given chat_id
     */
    public function getConversation($chatId)
    {
        $messages = Message::where(function ($q) use ($chatId) {
            $q->where('from', $chatId)
                ->orWhere('to', $chatId);
        })->orderBy('timestamp')->get();

        return MessageResource::collection($messages);
    }


    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     // Fetch messages with pagination, only not deleted
    //     $messages = Message::whereNull('deleted_at')
    //         ->latest()
    //         ->paginate(20);

    //     // Return resource collection
    //     return MessageResource::collection($messages);
    // }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
