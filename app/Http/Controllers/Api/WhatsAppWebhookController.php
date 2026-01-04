<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Services\AIResponderService;
use App\Services\IntelligentSupportService;
use App\Services\WhatsAppService;

class WhatsAppWebhookController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    // public function handle(Request $request)
    // {
    //     $payload = $request->all();
    //     Log::info('🔔 WhatsApp Webhook Received', $payload);

    //     try {
    //         $type = $payload['typeWebhook'] ?? 'undefined';

    //         switch ($type) {
    //             case 'incomingMessageReceived':
    //                 return $this->handleIncomingMessage($payload);

    //             case 'outgoingMessageReceived':
    //                 return $this->handleOutgoing($payload);

    //             case 'outgoingMessageStatus':
    //                 return $this->handleStatusUpdate($payload);

    //             case 'stateInstanceChanged':
    //                 Log::info('⚙️ Instance state changed', [
    //                     'state' => $payload['stateInstance'] ?? 'unknown'
    //                 ]);
    //                 break;


    //             case 'incomingCall':
    //                 $this->handleIncomingCall($payload);
    //                 break;

    //             default:
    //                 Log::warning("⚠️ Unhandled or unknown webhook type: {$type}", $payload);
    //                 break;
    //         }


    //         return response()->json(['status' => 'ok'], 200);
    //     } catch (\Throwable $e) {
    //         Log::error('❌ Webhook error: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json(['message' => 'Message not found'], 200); // Return 200 anyway

    //     }
    // }


    public function handle(Request $request)
    {
        // ACK IMMEDIATELY
        response()->json(['status' => 'ok'], 200)->send();

        // Continue processing safely
        try {
            $payload = $request->all();
            Log::info('🔔 WhatsApp Webhook Received', $payload);

            $type = data_get($payload, 'typeWebhook', 'undefined');

            match ($type) {
                'incomingMessageReceived' => $this->handleIncomingMessage($payload),
                'outgoingMessageReceived' => $this->handleOutgoing($payload),
                'outgoingMessageStatus'   => $this->handleStatusUpdate($payload),
                'stateInstanceChanged'    => Log::info('Instance state changed', $payload),
                'incomingCall'            => $this->handleIncomingCall($payload),
                default                   => Log::warning('Unknown webhook type', $payload),
            };
        } catch (\Throwable $e) {
            Log::error('Webhook processing error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }



    protected function handleIncomingMessage(array $payload)
    {
        $chatId    = data_get($payload, 'senderData.chatId');
        $text      = data_get($payload, 'messageData.textMessageData.textMessage');
        $quoted    = data_get($payload, 'messageData.quotedMessage.message');
        $quotedId  = data_get($payload, 'messageData.quotedMessage.stanzaId');
        $type      = data_get($payload, 'messageData.typeMessage', 'textMessage');
        $timestamp = now();

        Log::info("🔍 Incoming message from chatId: {$chatId}");

        try {
            if (!is_string($text) || trim($text) === '') {
                throw new \Exception('Incoming message text is empty');
            }

            /**
             * 🔹 Normalize phone number
             */
            $phone = preg_replace('/@.*/', '', $chatId);
            $phone = preg_replace('/\D/', '', $phone);

            if (Str::startsWith($phone, '0')) {
                $phone = '254' . substr($phone, 1);
            } elseif (Str::startsWith($phone, '7')) {
                $phone = '254' . $phone;
            } elseif (Str::startsWith($phone, '+254')) {
                $phone = ltrim($phone, '+');
            }

            Log::info("📞 Normalized phone: {$phone}");

            /**
             * 🔹 Fetch client & orders (DB ONLY)
             */
            $client = Customer::where('phone', $phone)->first();

            if (!$client) {
                Log::warning("🚫 Client not found for phone: {$phone}");

                $reply = "Sorry, I could not find your number in our system. Please confirm your registered phone number.";
                $actions = [];
            } else {
                $orders = $client->orders()->latest()->take(5)->get();

                // log the orders response 
                Log::info("📦 Fetched orders", ['orders_count' => $orders]);


                Log::info("📦 Found {$orders->count()} orders for client {$client->id}");

                $recentOrders = $orders->map(function ($order) {
                    // Safely build products array (guard against null relation)
                    $products = [];
                    if ($order->items && $order->items instanceof \Illuminate\Support\Collection) {
                        $products = $order->items->map(function ($item) {
                            return [
                                'product_id'   => $item->product_id ?? null,
                                'product_name' => $item->product->name ?? null,
                                'sku'          => $item->product->sku ?? null,
                                'quantity'     => $item->quantity ?? null,
                                'price'        => $item->price ?? null,
                                'total_price'  => $item->total_price ?? null,
                            ];
                        })->toArray();
                    }

                    // Safely build client info (guard against null relation)
                    $client = [];
                    if ($order->customer) {
                        $client = [
                            'id'      => $order->customer->id ?? null,
                            'name'    => $order->customer->name ?? null,
                            'phone'   => $order->customer->phone ?? null,
                            'address' => $order->customer->address ?? null,
                            'city'    => $order->customer->city ?? null,
                        ];
                    }

                    return [
                        'order_id'        => $order->id ?? null,
                        'order_no'        => $order->order_no ?? null,
                        'status'          => $order->status ?? null,
                        'delivery_status' => $order->delivery_status ?? null,
                        'total'           => $order->total ?? $order->total_price ?? null,
                        'sub_total'       => $order->sub_total ?? null,
                        'payment_method'  => $order->payment_method ?? null,
                        'paid'            => $order->paid ?? $order->amount_paid ?? false,
                        'delivery_date'   => $order->delivery_date ?? null,
                        'dispatched_on'   => $order->dispatched_on ?? null,
                        'customer_notes'  => $order->customer_notes ?? null,
                        'products'        => $products,
                        'client'          => $client,
                    ];
                })->toArray();

                /**
                 * 🔹 AI handling
                 */
                $ai = new IntelligentSupportService();
                $result = $ai->handleCustomerMessage($text, $recentOrders);

                if ($result instanceof \GuzzleHttp\Promise\PromiseInterface) {
                    $result = $result->wait();
                }

                $reply   = $result['reply'] ?? '[no reply]';
                $actions = $result['actions'] ?? [];

                Log::info("🤖 AI reply generated", compact('reply', 'actions'));
            }
        } catch (\Throwable $e) {
            Log::error("❌ Message handling error: {$e->getMessage()}");

            $reply = 'Sorry, something went wrong while processing your message. Please try again.';
            $actions = [];
        }

        /**
         * 🔹 Send WhatsApp reply
         */
        if (!empty($reply)) {
            try {
                $this->whatsAppService->sendMessage($chatId, $reply, 1, $order->id);
                Log::info("📤 Reply sent to {$chatId}");
            } catch (\Throwable $e) {
                Log::error("❌ WhatsApp send failed: {$e->getMessage()}");
            }
        }

        /**
         * 🔹 Store incoming message
         */
        try {
            Message::create([
                'chat_id'             => $chatId,
                'from'                => $chatId,
                'to'                  => 'system',
                'content'             => $text,
                'wa_message_id'       => data_get($payload, 'idMessage'),
                'quoted_message_id'   => $quotedId,
                'quoted_message_text' => $quoted,
                'type'                => $type,
                'timestamp'           => $timestamp,
                'messageable_type'    => \App\Models\User::class,
                'messageable_id'      => 1,
            ]);

            Log::info("💬 Message stored for {$chatId}");
        } catch (\Throwable $e) {
            Log::error("❌ Failed to store message: {$e->getMessage()}");
        }

        return response()->json(['status' => 'stored']);
    }


    protected function handleOutgoing(array $payload)
    {
        $chatId = data_get($payload, 'senderData.chatId');
        $senderId = data_get($payload, 'senderData.sender');
        $text = data_get($payload, 'messageData.textMessageData.textMessage');

        if (!$chatId || !$text) {
            return response()->json(['status' => 'ok'], 200);
        }

        Log::info("🔍 Identifying sender for chatId: {$senderId}");
        $sender = $this->identifySender($senderId);

        try {
            $msg = Message::updateOrCreate(
                ['external_message_id' => data_get($payload, 'idMessage')],
                [
                    'chat_id' => $chatId,
                    'from' => 'system',
                    'to' => $chatId,
                    'content' => $text,
                    'message_type' => data_get($payload, 'messageData.typeMessage', 'text'),
                    'timestamp' => now(),
                    'direction' => 'outgoing',
                    'message_status' => 'sent',
                    'messageable_type' => \App\Models\User::class,
                    'messageable_id' => $sender ? $sender->id : 1,
                ]
            );

            return response()->json(['status' => 'stored_outgoing', 'id' => $msg->id]);
        } catch (\Throwable $e) {
            Log::error("❌ Failed to store outgoing message: " . $e->getMessage());

            return response()->json(['message' => 'Message not found'], 200); // Return 200 anyway

        }
    }


    protected function handleStatusUpdate(array $payload)
    {
        $idMessage = data_get($payload, 'idMessage');
        $status = data_get($payload, 'status');

        if (!$idMessage || !$status) {
            Log::warning("❌ Missing status update data: ", $payload);
            return response()->json(['error' => 'Missing status data'], 200); // Return 200 anyway to satisfy Green API
        }

        $msg = Message::where('external_message_id', $idMessage)
            ->first();

        if ($msg) {
            $msg->status = $status;
            $msg->timestamp = now();

            if ($status === 'delivered') {
                $msg->delivered_at = now();
            }

            if ($status === 'read') {
                $msg->read_at = now();
            }

            if ($status === 'failed') {
                $msg->failed_at = now();
            }

            $msg->save();

            return response()->json(['status' => 'updated', 'id' => $msg->id], 200);
        }

        Log::warning("⚠️ Status update received for unknown message ID: {$idMessage}");


        return response()->json(['message' => 'Message not found'], 200); // Return 200 anyway

    }


    private function identifyClient($chatId)
    {
        $cleanChatId = preg_replace('/@.*$/', '', $chatId);
        $normalized = ltrim($cleanChatId, '0+');

        $client = Client::where('phone_number', 'like', "%{$normalized}")->first();
        if ($client) return $client;

        if (strlen($normalized) > 9 && str_starts_with($normalized, '254')) {
            $local = '0' . substr($normalized, 3);
            $client = Client::where('phone_number', $local)->first();
            if ($client) return $client;
        }

        return Client::where('phone_number', $cleanChatId)->first();
    }

    private function identifySender($senderId)
    {
        $cleanSenderId = preg_replace('/@.*$/', '', $senderId);
        $normalized = ltrim($cleanSenderId, '0+');

        $user = User::where('phone_number', 'like', "%{$normalized}")->first();
        if ($user) return $user;

        if (strlen($normalized) > 9 && str_starts_with($normalized, '254')) {
            $local = '0' . substr($normalized, 3);
            $user = User::where('phone_number', $local)->first();
            if ($user) return $user;
        }

        return User::where('phone_number', $cleanSenderId)->first();
    }



    protected function handleIncomingCall(array $payload)
    {

        // log the payload
        Log::info('📞 Incoming WhatsApp call received', $payload);
        $from = $payload['from'] ?? null;

        if ($from) {
            // Send auto-reply
            $this->whatsAppService->sendMessage($from, "👋 Sorry, I am an AI agent and currently I handle all SMS queries only. Please send us a message here.", 35);
        }
    }
}
