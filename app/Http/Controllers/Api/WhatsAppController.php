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

   

    public function sendMessage(Request $request)
{
    Log::debug('sendMessage called', ['request' => $request->all()]);

    $request->validate([
        'message' => 'required|string',
        'user_id' => 'required|integer',
        'order_ids' => 'nullable|array',
        'contact_ids' => 'nullable|array',
        'contacts' => 'nullable|array',
        'contacts.*.chatId' => 'required_with:contacts|string',
        'template_id' => 'nullable|integer',
    ]);

    $userId = $request->user_id;
    $messageTemplate = $request->message;
    $templateId = $request->template_id;
    $queued = 0;

    // 1. Send to orders' clients
    if ($request->filled('order_ids')) {
        Log::debug('Processing order_ids', ['order_ids' => $request->order_ids]);

        $orders = Order::with('client')->whereIn('id', $request->order_ids)->get();

        foreach ($orders as $order) {
            $client = $order->client;
            Log::debug('Processing order client', ['order_id' => $order->id, 'client' => $client]);

            $phone = $client?->phone_number ?? $client?->alt_phone_number;
            if (!$phone) {
                Log::warning('No phone found for client', ['order_id' => $order->id, 'client_id' => $client?->id]);
                continue;
            }

            // Process message with order-specific placeholders
            $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, $order, $client);

            $chatId = preg_replace('/[^0-9]/', '', $phone) . '@c.us';
            Log::info('Dispatching WhatsApp message job (order)', [
                'chatId' => $chatId, 
                'userId' => $userId,
                'original_message' => $messageTemplate,
                'personalized_message' => $personalizedMessage
            ]);
            
            SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId);
            $queued++;
        }
    }

    // 2. Send to user's saved contacts
    if ($request->filled('contact_ids')) {
        Log::debug('Processing contact_ids', ['contact_ids' => $request->contact_ids]);

        $user = User::with(['contacts' => function ($q) use ($request) {
            $q->whereIn('id', $request->contact_ids);
        }])->find($userId);

        foreach ($user?->contacts ?? [] as $contact) {
            Log::debug('Processing user contact', ['contact_id' => $contact->id, 'contact' => $contact]);

            $phone = $contact->phone ?? $contact->alt_phone;
            if (!$phone) {
                Log::warning('No phone found for contact', ['contact_id' => $contact->id]);
                continue;
            }

            // Process message with contact-specific placeholders
            $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, null, $contact);

            $chatId = preg_replace('/[^0-9]/', '', $phone) . '@c.us';
            Log::info('Dispatching WhatsApp message job (saved contact)', [
                'chatId' => $chatId, 
                'userId' => $userId,
                'personalized_message' => $personalizedMessage
            ]);
            
            SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId);
            $queued++;
        }
    }

    // 3. Send to unknown contacts directly via chatId
    if ($request->filled('contacts')) {
        Log::debug('Processing direct contacts array', ['contacts' => $request->contacts]);

        foreach ($request->contacts as $contactData) {
            $rawChatId = $contactData['chatId'] ?? null;
            if (!$rawChatId) {
                Log::warning('Missing chatId in direct contact', ['contact' => $contactData]);
                continue;
            }

            // Create a temporary contact object for placeholder processing
            $tempContact = (object) [
                'name' => $contactData['name'] ?? 'Customer',
                'phone' => $rawChatId,
                'id' => $contactData['id'] ?? null
            ];

            // Process message with contact-specific placeholders
            $personalizedMessage = $this->processMessagePlaceholders($messageTemplate, null, $tempContact);

            $chatId = preg_replace('/[^0-9]/', '', $rawChatId) . '@c.us';

            Log::info('Dispatching WhatsApp message job (external contact)', [
                'chatId' => $chatId,
                'userId' => $userId,
                'personalized_message' => $personalizedMessage
            ]);

            SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId);
            $queued++;
        }
    }

    Log::info('sendMessage completed', ['queued_count' => $queued]);

    return response()->json([
        'status' => 'success',
        'queued_count' => $queued,
        'message' => "Queued $queued WhatsApp messages."
    ]);
}

/**
 * Process message placeholders with actual data
 */
private function processMessagePlaceholders($messageTemplate, $order = null, $contact = null)
{
    $placeholders = [];

    // Contact/Client placeholders
    if ($contact) {
        $placeholders['customer_name'] = $contact->name ?? 'Customer';
        $placeholders['client_name'] = $contact->name ?? 'Customer';
        $placeholders['customer_phone'] = $contact->phone ?? $contact->phone_number ?? '';
    }

    // Order placeholders
    if ($order) {
        $placeholders['order_number'] = $order->order_number ?? $order->id ?? '';
        $placeholders['order_no'] = $order->order_number ?? $order->id ?? '';
        $placeholders['product_name'] = $order->product_name ?? $order->product ?? '';
        $placeholders['price'] = $order->price ?? $order->amount ?? '';
        $placeholders['tracking_id'] = $order->tracking_id ?? $order->tracking_number ?? '';
        $placeholders['delivery_date'] = $order->delivery_date ? 
            \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '';
        $placeholders['status'] = $order->status ?? '';
        $placeholders['agent_name'] = $order->agent_name ?? '';
        $placeholders['zone'] = $order->zone ?? '';
        
        // If order has client info, use it
        if ($order->client) {
            $placeholders['customer_name'] = $order->client->name ?? 'Customer';
            $placeholders['client_name'] = $order->client->name ?? 'Customer';
            $placeholders['customer_phone'] = $order->client->phone_number ?? $order->client->alt_phone_number ?? '';
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
