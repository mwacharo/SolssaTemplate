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



use App\Services\MessageTemplateService;


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




    // public function sendMessageX(Request $request)
    // {
    //     Log::debug('sendMessage called', ['request' => $request->all()]);

    //     $request->validate([
    //         'message' => 'nullable|string',
    //         'user_id' => 'required|integer',
    //         'order_ids' => 'nullable|array',
    //         'contact_ids' => 'nullable|array',
    //         'contacts' => 'nullable|array',
    //         'contacts.*.chatId' => 'required_with:contacts|string',
    //         'template_id' => 'nullable|integer',
    //         'template_slug' => 'nullable|string',
    //     ]);

    //     $userId = $request->user_id;
    //     $messageTemplate = $request->message;
    //     $templateId = $request->template_id;
    //     $templateSlug = $request->template_slug;
    //     $queued = 0;
    //     $delayMinutes = 5;
    //     $index = 0;

    //     // Initialize MessageTemplateService
    //     $messageTemplateService = app(MessageTemplateService::class);

    //     // 1. Send to orders' clients
    //     if ($request->filled(
    //         'order_ids',
    //         // 'orderItems.product'
    //     )) {
    //         Log::debug('Processing order_ids', ['order_ids' => $request->order_ids]);

    //         $orders = Order::with('client', '')->whereIn('id', $request->order_ids)->get();

    //         foreach ($orders as $order) {
    //             $client = $order->client;

    //             if (!$client) {
    //                 Log::warning('No client found for order', ['order_id' => $order->id]);
    //                 continue;
    //             }

    //             $phone = $client->phone_number ?? $client->alt_phone_number ?? $client->phone;

    //             if (!$phone) {
    //                 Log::warning('No phone found for client', [
    //                     'order_id' => $order->id,
    //                     'client_id' => $client->id
    //                 ]);
    //                 continue;
    //             }

    //             try {
    //                 // Use MessageTemplateService to generate personalized message
    //                 $result = $messageTemplateService->generateMessage(
    //                     phone: $phone,
    //                     templateId: $templateId,
    //                     templateSlug: $templateSlug ?? 'order_followup',
    //                     additionalData: [
    //                         'order_id' => $order->id,
    //                         'customer_id' => $client->id,
    //                     ]
    //                 );

    //                 $personalizedMessage = $result['message'];
    //                 $chatId = preg_replace('/[^0-9]/', '', $phone) . '@c.us';

    //                 Log::info('Dispatching WhatsApp message (order)', [
    //                     'chatId' => $chatId,
    //                     'order_id' => $order->id,
    //                     'template_used' => $result['template']->name ?? 'Unknown',
    //                     'delay_minutes' => $delayMinutes * $index
    //                 ]);

    //                 SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId,)
    //                     ->delay(now()->addMinutes($delayMinutes * $index));

    //                 $index++;
    //                 $queued++;
    //             } catch (\Exception $e) {
    //                 Log::error('Error generating message for order', [
    //                     'order_id' => $order->id,
    //                     'phone' => $phone,
    //                     'error' => $e->getMessage()
    //                 ]);
    //                 continue;
    //             }
    //         }
    //     }

    //     // 2. Send to user's saved contacts
    //     if ($request->filled('contact_ids')) {
    //         Log::debug('Processing contact_ids', ['contact_ids' => $request->contact_ids]);

    //         $user = User::with(['contacts' => function ($q) use ($request) {
    //             $q->whereIn('id', $request->contact_ids);
    //         }])->find($userId);

    //         foreach ($user?->contacts ?? [] as $contact) {
    //             $phone = $contact->phone ?? $contact->alt_phone;

    //             if (!$phone) {
    //                 Log::warning('No phone found for contact', ['contact_id' => $contact->id]);
    //                 continue;
    //             }

    //             try {
    //                 // Use MessageTemplateService for saved contacts
    //                 $result = $messageTemplateService->generateMessage(
    //                     phone: $phone,
    //                     templateId: $templateId,
    //                     templateSlug: $templateSlug ?? 'general',
    //                     additionalData: [
    //                         'customer_name' => $contact->name ?? 'Customer',
    //                         'customer_id' => $contact->id,
    //                     ]
    //                 );

    //                 $personalizedMessage = $result['message'];
    //                 $chatId = preg_replace('/[^0-9]/', '', $phone) . '@c.us';

    //                 Log::info('Dispatching WhatsApp message (saved contact)', [
    //                     'chatId' => $chatId,
    //                     'contact_id' => $contact->id,
    //                     'template_used' => $result['template']->name ?? 'Unknown',
    //                     'delay_minutes' => $delayMinutes * $index
    //                 ]);

    //                 SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
    //                     ->delay(now()->addMinutes($delayMinutes * $index));

    //                 $index++;
    //                 $queued++;
    //             } catch (\Exception $e) {
    //                 Log::error('Error generating message for contact', [
    //                     'contact_id' => $contact->id,
    //                     'phone' => $phone,
    //                     'error' => $e->getMessage()
    //                 ]);
    //                 continue;
    //             }
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

    //             $phone = preg_replace('/[^0-9]/', '', $rawChatId);

    //             try {
    //                 // Use MessageTemplateService for external contacts
    //                 $result = $messageTemplateService->generateMessage(
    //                     phone: $phone,
    //                     templateId: $templateId,
    //                     templateSlug: $templateSlug ?? 'general',
    //                     additionalData: [
    //                         'customer_name' => $contactData['name'] ?? 'Customer',
    //                         'customer_id' => $contactData['id'] ?? null,
    //                     ]
    //                 );

    //                 $personalizedMessage = $result['message'];
    //                 $chatId = $phone . '@c.us';

    //                 Log::info('Dispatching WhatsApp message (external contact)', [
    //                     'chatId' => $chatId,
    //                     'contact_name' => $contactData['name'] ?? 'Unknown',
    //                     'template_used' => $result['template']->name ?? 'Unknown',
    //                     'delay_minutes' => $delayMinutes * $index
    //                 ]);

    //                 SendWhatsAppMessageJob::dispatch($chatId, $personalizedMessage, $userId)
    //                     ->delay(now()->addMinutes($delayMinutes * $index));

    //                 $index++;
    //                 $queued++;
    //             } catch (\Exception $e) {
    //                 Log::error('Error generating message for external contact', [
    //                     'chatId' => $rawChatId,
    //                     'error' => $e->getMessage()
    //                 ]);
    //                 continue;
    //             }
    //         }
    //     }

    //     Log::info('sendMessage completed', [
    //         'queued_count' => $queued,
    //         'total_delay_minutes' => $delayMinutes * ($index - 1)
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'queued_count' => $queued,
    //         'message' => "Queued $queued WhatsApp messages successfully.",
    //         'estimated_completion_minutes' => $queued > 0 ? $delayMinutes * ($queued - 1) : 0
    //     ]);
    // }

    /**
     * Legacy method - kept for backward compatibility
     * Use MessageTemplateService instead for new implementations
     */



    public function sendMessage(Request $request)
    {
        Log::debug('📨 sendMessage called', ['request' => $request->all()]);

        $request->validate([
            'user_id' => 'required|integer',
            'template_id' => 'nullable|integer',
            'template_slug' => 'nullable|string',
            'contacts' => 'required|array',
            'contacts.*.id' => 'nullable|integer',
            'contacts.*.name' => 'nullable|string',
            'contacts.*.chatId' => 'required|string',
            'contacts.*.phone' => 'nullable|string',
            'contacts.*.orderId' => 'nullable|integer',     // ⭐ Accept orderId
            'contacts.*.orderOid' => 'nullable|string',     // ⭐ Accept orderOid
            'message' => 'nullable|string',
            'order' => 'nullable|array',
        ]);

        $userId = $request->user_id;
        $templateId = $request->template_id;
        $templateSlug = $request->template_slug ?? 'general';
        $messageTemplateService = app(MessageTemplateService::class);

        $queued = 0;
        $delayMinutes = 5;
        $index = 0;

        foreach ($request->contacts as $contact) {
            // Extract phone
            $rawPhone = $contact['phone'] ?? null;

            if (!$rawPhone && isset($contact['chatId'])) {
                $rawPhone = preg_replace('/[^0-9]/', '', $contact['chatId']);
            }

            if (!$rawPhone) {
                Log::warning('⚠️ Skipping contact - no phone', [
                    'contact' => $contact
                ]);
                continue;
            }

            $phone = preg_replace('/[^0-9]/', '', $rawPhone);
            $chatId = $phone . '@c.us';

            // ⭐ CRITICAL: Include orderId in additionalData
            $additionalData = [
                'customer_name' => $contact['name'] ?? 'Customer',
                'customer_id' => $contact['id'] ?? null,
                'order_id' => $contact['orderId'] ?? null,        // ⭐ Pass orderId
                'order_oid' => $contact['orderOid'] ?? null,      // ⭐ Pass orderOid
            ];

            Log::info('📋 Processing contact', [
                'phone' => $phone,
                'customer_name' => $additionalData['customer_name'],
                'order_id' => $additionalData['order_id'],
                'order_oid' => $additionalData['order_oid'],
            ]);

            try {
                // Generate personalized message
                $result = $messageTemplateService->generateMessage(
                    phone: $phone,
                    templateId: $templateId,
                    templateSlug: $templateSlug,
                    additionalData: $additionalData
                );

                $personalizedMessage = $result['message'];

                Log::info('✅ Generated message', [
                    'template_used' => $result['template']->name,
                    'message_length' => strlen($personalizedMessage),
                    'order_no' => $result['data_used']['order_no'] ?? 'N/A'
                ]);

                // Dispatch job
                SendWhatsAppMessageJob::dispatch(
                    $chatId,
                    $personalizedMessage,
                    $userId,
                    $additionalData

                )->delay(now()->addMinutes($delayMinutes * $index));

                $index++;
                $queued++;
            } catch (\Exception $e) {
                Log::error('❌ Error generating message', [
                    'contact' => $contact,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }

        return response()->json([
            'status' => 'success',
            'queued_count' => $queued,
            'message' => "Queued $queued WhatsApp messages successfully.",
            'estimated_completion_minutes' => $queued > 0
                ? $delayMinutes * ($queued - 1)
                : 0
        ]);
    }


    private function processMessagePlaceholders(string $template, ?Order $order = null, $client = null): string
    {
        Log::warning('Using legacy processMessagePlaceholders - consider migrating to MessageTemplateService');

        $message = $template;

        // Basic client placeholders
        if ($client) {
            $message = str_replace('{{client_name}}', $client->name ?? 'Customer', $message);
            $message = str_replace('{{customer_name}}', $client->name ?? 'Customer', $message);
            $message = str_replace('{{client_phone}}', $client->phone ?? $client->phone_number ?? '', $message);
        }

        // Basic order placeholders
        if ($order) {
            $message = str_replace('{{order_no}}', $order->order_no ?? $order->number ?? '', $message);
            $message = str_replace('{{order_number}}', $order->order_no ?? $order->number ?? '', $message);
            $message = str_replace('{{total_price}}', $order->total_price ?? '', $message);
            $message = str_replace('{{delivery_date}}', $order->delivery_date ?? '', $message);
            $message = str_replace('{{status}}', $order->status ?? '', $message);
        }

        // Remove any unmatched placeholders
        $message = preg_replace('/\{\{[^}]*\}\}/', '', $message);

        return trim($message);
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
        Log::info('Fetching conversation for chatId', ['chatId' => $chatId]);
        $messages = Message::where(function ($q) use ($chatId) {
            $q->where('from', $chatId)
                ->orWhere('to', $chatId);
        })->orderBy('timestamp')->get();

        return MessageResource::collection($messages);
    }
}
