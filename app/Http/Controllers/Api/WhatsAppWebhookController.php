<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Client;
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

    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('🔔 WhatsApp Webhook Received', $payload);

        try {
            $type = $payload['typeWebhook'] ?? 'undefined';

            switch ($type) {
                case 'incomingMessageReceived':
                    return $this->handleIncomingMessage($payload);

                case 'outgoingMessageReceived':
                    return $this->handleOutgoing($payload);

                case 'outgoingMessageStatus':
                    return $this->handleStatusUpdate($payload);

                case 'stateInstanceChanged':
                    Log::info('⚙️ Instance state changed', [
                        'state' => $payload['stateInstance'] ?? 'unknown'
                    ]);
                    break;


                case 'incomingCall':
                    $this->handleIncomingCall($payload);
                    break;

                default:
                    Log::warning("⚠️ Unhandled or unknown webhook type: {$type}", $payload);
                    break;
            }

            // return response()->json(['status' => 'received']);

            return response()->json(['status' => 'ok'], 200);
        } catch (\Throwable $e) {
            Log::error('❌ Webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            // return response()->json(['error' => 'Webhook processing failed'], 500);
            return response()->json(['message' => 'Message not found'], 200); // Return 200 anyway

        }
    }


    protected function handleIncomingMessage(array $payload)
    {
        $chatId    = data_get($payload, 'senderData.chatId');
        $senderId  = data_get($payload, 'senderData.senderId');
        $text      = data_get($payload, 'messageData.textMessageData.textMessage');
        $quoted    = data_get($payload, 'messageData.quotedMessage.message');
        $quotedId  = data_get($payload, 'messageData.quotedMessage.stanzaId');
        $type      = data_get($payload, 'messageData.typeMessage', 'textMessage');
        $timestamp = now();

        Log::info("🔍 Looking up client by chatId: {$chatId}");

        $recentOrders = [];
        $reply = null;
        $clientFound = false;

        try {
            // 🔹 Extract & normalize phone
            // $phone = preg_replace('/@.*/', '', $chatId);
            // if (Str::startsWith($phone, '254')) {
            //     $phone = '0' . substr($phone, 3);
            // }


            // 🔹 Extract and normalize phone number
            $phone = preg_replace('/@.*/', '', $chatId); // remove @c.us etc.

            // Remove any non-digit characters just in case
            $phone = preg_replace('/\D/', '', $phone);

            // Normalize to international format (254...)
            if (Str::startsWith($phone, '0')) {
                $phone = '254' . substr($phone, 1); // convert 07... → 2547...
            } elseif (Str::startsWith($phone, '7')) {
                $phone = '254' . $phone; // convert 7... → 2547...
            } elseif (Str::startsWith($phone, '+254')) {
                $phone = str_replace('+', '', $phone); // +254... → 254...
            }

            // ✅ Now $phone will always be in the format: 254XXXXXXXXX


            Log::info("📞 Normalized phone number: {$phone}");

            // 🔹 Call external API (wait until response is ready)
            $response = Http::timeout(120)
                ->retry(3, 2000)
                ->get("https://app.boxleocourier.com/api/contact-search/{$phone}");

            if (!$response->successful()) {
                Log::warning("🚫 Boxleo API call not successful for {$phone}. Status: " . $response->status(), [
                    'body' => $response->body()
                ]);
                throw new \Exception("API error for {$phone}, status: " . $response->status());
            }

            $clients = $response->json();
            if (!is_array($clients) || empty($clients)) {
                Log::warning("🚫 No client found for phone: {$phone}");
            } else {
                Log::info("✅ Boxleo API returned clients for {$phone}", [
                    'clients' => $clients
                ]);
            }

            $clients = $response->json();

            if (!empty($clients)) {
                $clientFound = true;
                $client = collect($clients)->first(fn($c) => !empty($c['sales'])) ?? $clients[0];

                Log::info("✅ Client found: {$client['id']} - {$client['name']}");

                // 🔹 Format orders
                $recentOrders = collect($client['sales'] ?? [])
                    ->sortByDesc('created_at')
                    ->take(5)
                    ->map(fn($order) => [
                        'order_id'        => $order['id'],
                        'order_no'        => $order['order_no'],
                        'status'          => $order['status'],
                        'delivery_status' => $order['delivery_status'],
                        'total'           => $order['total_price'],
                        'sub_total'       => $order['sub_total'],
                        'payment_method'  => $order['payment_method'],
                        'paid'            => $order['paid'],
                        'delivery_date'   => $order['delivery_date'],
                        'dispatched_on'   => $order['dispatched_on'],
                        'customer_notes'  => $order['customer_notes'],
                        'products'        => collect($order['products'] ?? [])->map(fn($product) => [
                            'product_id'    => $product['id'],
                            'product_name'  => $product['product_name'],
                            'sku'           => $product['sku_no'],
                            'vendor_id'     => $product['vendor_id'],
                            'vendor_name'   => $product['user_id'],
                            'quantity'      => $product['pivot']['quantity'] ?? 0,
                            'price'         => $product['pivot']['price'] ?? 0,
                            'total_price'   => $product['pivot']['total_price'] ?? 0,
                        ])->toArray(),
                        'client' => [
                            'id'      => $order['client']['id'] ?? null,
                            'name'    => $order['client']['name'] ?? null,
                            'phone'   => $order['client']['phone'] ?? null,
                            'address' => $order['client']['address'] ?? null,
                            'city'    => $order['client']['city'] ?? null,
                        ],
                    ])
                    ->values()
                    ->toArray();

                Log::info("📦 Recent orders formatted", $recentOrders);
            } else {
                Log::warning("🚫 No client found for phone: {$phone}");
            }

            // 🔹 AI Service
            if (!is_string($text) || trim($text) === '') {
                throw new \Exception('Incoming message text is empty or invalid');
            }

            $ai = new IntelligentSupportService();

            // ✅ Adjust AI input depending on client presence
            if ($clientFound) {
                Log::info("🧠 Client found, passing text and to handleCustomerMessage recent orders to AI", [
                    'recentOrders' => $recentOrders,
                    'text' => $text
                ]);
                $result = $ai->handleCustomerMessage($text, $recentOrders);
                Log::info("🧠 AI response generated", $result);
            } else {
                $result = [
                    'reply'   => "Sorry, I could not find your phone number in our system. Can you confirm your registered number?",
                    'actions' => [],
                ];
            }

            // If async Promise, block until resolved
            if ($result instanceof \GuzzleHttp\Promise\PromiseInterface) {
                $result = $result->wait();
            }

            $reply   = $result['reply'] ?? '[no reply]';
            $actions = $result['actions'] ?? [];

            Log::info("🤖 AI reply ready", ['reply' => $reply, 'actions' => $actions]);
        } catch (\Throwable $e) {
            Log::error("❌ Error handling message: " . $e->getMessage());
            return [
                'reply'   => 'Sorry, I encountered an error processing your request. Please try again.',
                'actions' => []
            ];
        }

        // 🔹 Send reply
        if ($reply) {
            try {
                $this->whatsAppService->sendMessage($chatId, $reply, 1);
                Log::info("📤 Reply sent to {$chatId}");
            } catch (\Throwable $e) {
                Log::error("❌ WhatsAppService error: " . $e->getMessage());
            }
        } else {
            Log::info("ℹ️ No reply generated for {$chatId}");
        }

        // 🔹 Store incoming message
        try {
            Message::create([
                'chat_id'              => $chatId,
                'from'                 => $chatId,
                'to'                   => 'system',
                'content'              => $text,
                'wa_message_id'        => data_get($payload, 'idMessage'),
                'quoted_message_id'    => $quotedId,
                'quoted_message_text'  => $quoted,
                'type'                 => $type,
                'timestamp'            => $timestamp,
                'messageable_type'     => \App\Models\User::class,
                'messageable_id'       => 1,
            ]);

            Log::info("💬 Message stored from {$chatId} at {$timestamp}");
        } catch (\Throwable $e) {
            Log::error("❌ Failed to store message: " . $e->getMessage());
        }

        return response()->json(['status' => 'stored']);
    }



    protected function handleOutgoing(array $payload)
    {
        $chatId = data_get($payload, 'senderData.chatId');
        $senderId = data_get($payload, 'senderData.sender');
        $text = data_get($payload, 'messageData.textMessageData.textMessage');

        if (!$chatId || !$text) {
            return response()->json(['error' => 'Missing outgoing data'], 400);
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
            // return response()->json(['error' => 'Failed to store outgoing'], 500);

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

        // return response()->json(['warning' => 'Message not found'], 200); // Green API expects 200 regardless

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
