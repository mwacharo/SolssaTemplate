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
            $phone = preg_replace('/@.*/', '', $chatId);
            if (Str::startsWith($phone, '254')) {
                $phone = '0' . substr($phone, 3);
            }

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


    // protected function handleIncomingMessage(array $payload)
    // {
    //     $chatId   = data_get($payload, 'senderData.chatId');
    //     $senderId = data_get($payload, 'senderData.senderId');
    //     $text     = data_get($payload, 'messageData.textMessageData.textMessage');
    //     $quoted   = data_get($payload, 'messageData.quotedMessage.message');
    //     $quotedId = data_get($payload, 'messageData.quotedMessage.stanzaId');
    //     $type     = data_get($payload, 'messageData.typeMessage', 'textMessage');
    //     $timestamp = now();

    //     Log::info("🔍 Looking up client by chatId: {$chatId}");
    //     // $user = $this->identifyClient($chatId);
        

    //     // given client details and order come from a different system 


    //     // ai endpoint ni https://app.boxleocourier.com/api/contact-search/{phone}

    //     // https://app.boxleocourier.com/api/contact-search/0714836875

    //     // [{"id":1435465,"user_id":0,"ou_id":1,"name":"Alphonce","email":null,"phone":"0714836875","alt_phone":null,"address":"Ndhiwa","gender":null,"group_id":null,"payment_type":null,"seller_id":380,"deleted_at":null,"created_at":"Mon 01 Sep 2025 07:40","updated_at":"2025-09-01T04:40:19.000000Z","city":"Ndhiwa","sales":[{"id":1610424,"reference":null,"drawer_id":null,"client_id":1435465,"agent_id":365,"total_price":5399,"scale":1,"invoice_value":null,"amount_paid":null,"sub_total":"5399.00","order_no":"MRKT-07165984","sku_no":null,"tracking_no":null,"waybill_no":null,"customer_notes":"UNRESPONSIVE 2ND ATTEMPT","discount":"0.00","shipping_charges":"0.00","charges":"0.00","delivery_date":"2025-09-02 00:00:00","delivery_d":null,"status":"Pending","delivery_status":"Pending","warehouse_id":1,"seller_id":380,"paypal":null,"payment_method":"cash","payment_id":null,"mpesa_code":null,"terms":null,"template_name":null,"platform":"API","route":null,"cancel_notes":null,"is_return_waiting_for_approval":0,"is_salesreturn_allowed":0,"is_test_order":0,"is_emailed":0,"is_dropshipped":0,"is_cancel_item_waiting_for_approval":0,"track_inventory":1,"confirmed":false,"delivered":0,"returned":0,"cancelled":0,"invoiced":false,"packed":0,"printed":false,"print_count":0,"sticker_printed":0,"prepaid":0,"paid":false,"weight":null,"return_count":0,"dispatched_on":"2025-09-01","return_date":null,"delivered_on":null,"returned_on":null,"cancelled_on":null,"printed_at":null,"print_no":null,"sticker_at":null,"recall_date":null,"history_comment":"Order status updated from <b style=\"color:red\"> New<\/b> to <b style=\"color:#1564c0;\"> Pending","return_notes":null,"ou_id":1,"pickup_address":null,"pickup_phone":null,"pickup_shop":null,"upsell":0,"pickup_city":null,"user_id":365,"schedule_date":null,"rider_id":null,"zone_id":null,"checkout_id":null,"longitude":"0","latitude":"0","distance":null,"geocoded":0,"loading_no":null,"boxes":null,"archived_at":null,"deleted_at":null,"created_at":"Mon 01 Sep 2025 07:40","order_date":null,"updated_at":"2025-09-01","tat":null,"products":[{"id":40514,"product_name":"Motorcycle Raincoat Rain Cover","sku_no":"mrkt-3873","bar_code":null,"active":true,"virtual":true,"stock_management":"0","vendor_id":380,"update_comment":null,"user_id":338,"deleted_at":null,"created_at":"Wed 20 Aug 2025 13:52","updated_at":"2025-08-20T10:52:44.000000Z","pivot":{"sale_id":1610424,"product_id":40514,"id":1638349,"quantity":1,"price":"5399.00","sku_no":"mrkt-3873","total_price":5399,"seller_id":380,"quantity_sent":0,"quantity_delivered":0,"quantity_returned":0,"quantity_remaining":0,"product_rate":"0.00","delivered":0,"sent":0,"quantity_tobe_delivered":1},"product_variants":[],"skus":[{"id":40242,"product_id":40514,"price":null,"buying_price":null,"sku_no":"mrkt-3873","quantity":0,"reorder_point":0,"deleted_at":null,"created_at":"2025-08-20T10:52:44.000000Z","updated_at":"2025-08-20T10:52:44.000000Z"}],"categories":[],"brands":[],"subcategories":[],"images":null,"bins":[]}],"client":{"id":1435465,"user_id":0,"ou_id":1,"name":"Alphonce","email":null,"phone":"0714836875","alt_phone":null,"address":"Ndhiwa","gender":null,"group_id":null,"payment_type":null,"seller_id":380,"deleted_at":null,"created_at":"Mon 01 Sep 2025 07:40","updated_at":"2025-09-01T04:40:19.000000Z","city":"Ndhiwa"}}]}]

    //     $recentOrders = [];

    //     // if ($user) {
    //     //     Log::info("✅ Client found: {$user->id}");
    //     //     $recentOrders = $user->orders()
    //     //         ->with(['orderItems.product', 'vendor', 'rider', 'agent', 'client'])
    //     //         ->latest()
    //     //         ->take(5)
    //     //         ->get();

    //     //     Log::info("📦 Recent orders fetched", $recentOrders->toArray());
    //     // } else {
    //     //     Log::warning("🚫 No client found for chatId: {$chatId}");
    //     // }

    //     try {
    //         // $ai = new AIResponderService();
    //         // Log::info("🤖 Interpreting customer query: {$text}");
    //         // $reply = $ai->interpretCustomerQuery($text, is_array($recentOrders) ? $recentOrders : $recentOrders->toArray());
    //         Log::info("🧠 Initializing IntelligentSupportService for chatId: {$chatId}");
    //         $ai = new IntelligentSupportService();
    //         Log::info("📝 Handling customer message: {$text}", [
    //             'recentOrders' => is_array($recentOrders) ? $recentOrders : $recentOrders->toArray()
    //         ]);


    //         $reply = $ai->handleCustomerMessage($text, is_array($recentOrders) ? $recentOrders : $recentOrders->toArray());
    //         Log::info("🤖 IntelligentSupportService reply: " . ($reply ?? '[no reply]'));



    //         Log::info("🧠 Initializing IntelligentSupportService for chatId: {$chatId}");
    //         // $ai = new IntelligentSupportService();
    //         Log::info("📝 Handling customer message: {$text}", [
    //             'recentOrders' => is_array($recentOrders) ? $recentOrders : $recentOrders->toArray()
    //         ]);

    //         // FIX: handleCustomerMessage returns an array, not a string
    //         // Validate $text before passing to AI service
    //         if (!is_string($text) || trim($text) === '') {
    //             throw new \Exception('Incoming message text is empty or invalid');
    //         }
    //         $result = $ai->handleCustomerMessage($text, is_array($recentOrders) ? $recentOrders : $recentOrders->toArray());

    //         $result = $ai->handleCustomerMessage($text, is_array($recentOrders) ? $recentOrders : $recentOrders->toArray());

    //         // Extract the reply string from the result array
    //         $reply = $result['reply'] ?? '[no reply]';
    //         $actions = $result['actions'] ?? [];

    //         Log::info("🤖 IntelligentSupportService reply: " . $reply, [
    //             'actions' => $actions
    //         ]);
    //     } catch (\Throwable $e) {
    //         // Log::error("❌ AIResponderService error: " . $e->getMessage());
    //         // $reply = null;


    //         Log::error("IntelligentSupportService error: " . $e->getMessage());
    //         // Handle the error appropriately
    //         return [
    //             'reply' => 'Sorry, I encountered an error processing your request. Please try again.',
    //             'actions' => []
    //         ];
    //     }

    //     if ($reply) {
    //         Log::info("📤 Sending AI reply to {$chatId}: {$reply}");
    //         try {
    //             // $this->whatsAppService->sendMessage($chatId, $reply, 35); // 1 = System user
    //         } catch (\Throwable $e) {
    //             Log::error("❌ WhatsAppService error: " . $e->getMessage());
    //         }
    //     } else {
    //         Log::info("ℹ️ No reply generated by AI for {$chatId}");
    //     }

    //     try {
    //         Message::create([
    //             'chat_id' => $chatId,
    //             'from' => $chatId,
    //             'to' => 'system',
    //             'content' => $text,
    //             'wa_message_id' => data_get($payload, 'idMessage'),
    //             'quoted_message_id' => $quotedId,
    //             'quoted_message_text' => $quoted,
    //             'type' => $type,
    //             'timestamp' => $timestamp,
    //             'messageable_type' => \App\Models\User::class,
    //             'messageable_id' => 1,
    //         ]);

    //         Log::info("💬 Message stored from {$chatId} at {$timestamp}");
    //     } catch (\Throwable $e) {
    //         Log::error("❌ Failed to store message: " . $e->getMessage());
    //     }

    //     return response()->json(['status' => 'stored']);
    // }



//     protected function handleIncomingMessage(array $payload)
// {
//     $chatId   = data_get($payload, 'senderData.chatId');
//     $senderId = data_get($payload, 'senderData.senderId');
//     $text     = data_get($payload, 'messageData.textMessageData.textMessage');
//     $quoted   = data_get($payload, 'messageData.quotedMessage.message');
//     $quotedId = data_get($payload, 'messageData.quotedMessage.stanzaId');
//     $type     = data_get($payload, 'messageData.typeMessage', 'textMessage');
//     $timestamp = now();

//     Log::info("🔍 Looking up client by chatId: {$chatId}");

//     $recentOrders = [];
//     $reply = null;

//     try {
//         // 🔹 Extract phone number from chatId (e.g., "254751458911@s.whatsapp.net")
//         $phone = preg_replace('/@.*/', '', $chatId);

//         // 🔹 Normalize phone (convert 2547... → 07...)
//         if (Str::startsWith($phone, '254')) {
//             $phone = '0' . substr($phone, 3);
//         }

//         Log::info("📞 Normalized phone number from chatId: {$phone}");

//         // 🔹 Call external API
//         $response = Http::get("https://app.boxleocourier.com/api/contact-search/{$phone}");

//         Log::info("📡 Boxleo response for {$phone}: ", [
//             'response' => $response->json()
//         ]);

//         if ($response->ok() && count($response->json()) > 0) {
//             $clients = $response->json();

//             // Pick first client with sales, or fallback to first record
//             $client = collect($clients)->first(function ($c) {
//                 return !empty($c['sales']);
//             }) ?? $clients[0];

//             Log::info("✅ Client chosen: {$client['id']} - {$client['name']}");

//             // 🔹 Format recent orders (max 5)
//             $recentOrders = collect($client['sales'] ?? [])
//                 ->sortByDesc('created_at')
//                 ->take(5)
//                 ->map(function ($order) {
//                     return [
//                         'order_id'        => $order['id'],
//                         'order_no'        => $order['order_no'],
//                         'status'          => $order['status'],
//                         'delivery_status' => $order['delivery_status'],
//                         'total'           => $order['total_price'],
//                         'sub_total'       => $order['sub_total'],
//                         'payment_method'  => $order['payment_method'],
//                         'paid'            => $order['paid'],
//                         'delivery_date'   => $order['delivery_date'],
//                         'dispatched_on'   => $order['dispatched_on'],
//                         'customer_notes'  => $order['customer_notes'],
//                         'products'        => collect($order['products'] ?? [])->map(function ($product) {
//                             return [
//                                 'product_id'    => $product['id'],
//                                 'product_name'  => $product['product_name'],
//                                 'sku'           => $product['sku_no'],
//                                 'vendor_id'     => $product['vendor_id'],
//                                 'vendor_name'   => $product['user_id'], // If you want vendor name, you may need to fetch it separately
//                                 'quantity'      => $product['pivot']['quantity'] ?? 0,
//                                 'price'         => $product['pivot']['price'] ?? 0,
//                                 'total_price'   => $product['pivot']['total_price'] ?? 0,
//                                 'delivered'     => $product['pivot']['delivered'] ?? 0,
//                                 'bin'           => collect($product['bins'] ?? [])->map(function ($bin) {
//                                     return [
//                                         'bin_id'    => $bin['id'],
//                                         'code'      => $bin['code'],
//                                         'name'      => $bin['name'],
//                                         'onhand'    => $bin['pivot']['onhand'] ?? null,
//                                         'available' => $bin['pivot']['available_for_sale'] ?? null,
//                                         'commited'  => $bin['pivot']['commited'] ?? null,
//                                         'delivered' => $bin['pivot']['delivered'] ?? null,
//                                     ];
//                                 })->toArray(),
//                             ];
//                         })->toArray(),
//                         'client' => [
//                             'id'      => $order['client']['id'] ?? null,
//                             'name'    => $order['client']['name'] ?? null,
//                             'phone'   => $order['client']['phone'] ?? null,
//                             'address' => $order['client']['address'] ?? null,
//                             'city'    => $order['client']['city'] ?? null,
//                         ],
//                     ];
//                 })
//                 ->values()
//                 ->toArray();

//             Log::info("📦 Recent orders formatted", $recentOrders);
//             Log::warning("🚫 No client found for chatId: {$chatId}");
//         }

//         // 🔹 AI Service
//         $ai = new IntelligentSupportService();

//         if (!is_string($text) || trim($text) === '') {
//             throw new \Exception('Incoming message text is empty or invalid');
//         }

//         $result = $ai->handleCustomerMessage($text, $recentOrders);
//         $reply  = $result['reply'] ?? '[no reply]';
//         $actions = $result['actions'] ?? [];

//         Log::info("🤖 IntelligentSupportService reply: {$reply}", [
//             'actions' => $actions
//         ]);
//     } catch (\Throwable $e) {
//         Log::error("❌ Error handling message: " . $e->getMessage());
//         return [
//             'reply' => 'Sorry, I encountered an error processing your request. Please try again.',
//             'actions' => []
//         ];
//     }

    // 🔹 Send reply back to customer (if available)
//     if ($reply) {
//         Log::info("📤 Sending AI reply to {$chatId}: {$reply}");
//         try {
//             $this->whatsAppService->sendMessage($chatId, $reply, 35);
//         } catch (\Throwable $e) {
//             Log::error("❌ WhatsAppService error: " . $e->getMessage());
//         }
//     } else {
//         Log::info("ℹ️ No reply generated by AI for {$chatId}");
//     }

//     // 🔹 Save incoming message
//     try {
//         Message::create([
//             'chat_id' => $chatId,
//             'from' => $chatId,
//             'to' => 'system',
//             'content' => $text,
//             'wa_message_id' => data_get($payload, 'idMessage'),
//             'quoted_message_id' => $quotedId,
//             'quoted_message_text' => $quoted,
//             'type' => $type,
//             'timestamp' => $timestamp,
//             'messageable_type' => \App\Models\User::class,
//             'messageable_id' => 1,
//         ]);

//         Log::info("💬 Message stored from {$chatId} at {$timestamp}");
//     } catch (\Throwable $e) {
//         Log::error("❌ Failed to store message: " . $e->getMessage());
//     }

//     return response()->json(['status' => 'stored']);
// }

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
    

    // protected function handleStatusUpdate(array $payload)
    // {
    //     $idMessage = data_get($payload, 'idMessage');
    //     $status = data_get($payload, 'status');

    //     if (!$idMessage || !$status) {
    //         return response()->json(['error' => 'Missing status data'], 400);
    //     }

    //     // Try both 'external_message_id' and 'wa_message_id'
    //     $msg = Message::where('external_message_id', $idMessage)
    //         ->orWhere('wa_message_id', $idMessage)
    //         ->first();

    //     if ($msg) {
    //         $msg->status = $status;
    //         $msg->timestamp = now();

    //         if ($status === 'delivered') {
    //             $msg->delivered_at = now();
    //         }

    //         if ($status === 'read') {
    //             $msg->read_at = now();
    //         }

    //         if ($status === 'failed') {
    //             $msg->failed_at = now();
    //         }

    //         $msg->save();

    //         return response()->json(['status' => 'updated', 'id' => $msg->id]);
    //     }

    //     Log::warning("⚠️ Status update received for unknown message ID: {$idMessage}");

    //     return response()->json(['warning' => 'Message not found'], 404);
    // }


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
        Log::info('📞 Incoming WhatsApp call received', $payload)
        ;
        $from = $payload['from'] ?? null;

        if ($from) {
            // Send auto-reply
            $this->whatsAppService->sendMessage($from, "👋 Sorry, I am an AI agent and currently I handle all SMS queries only. Please send us a message here.", 35);
        }
    }
}
