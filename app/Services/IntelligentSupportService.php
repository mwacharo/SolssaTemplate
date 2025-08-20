<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// === Replace with your real models ===
use App\Models\User;         // customer table or your Customer model
use App\Models\Order;
use App\Models\Product;
use App\Models\Country;
use App\Models\Message;      // stored inbound/outbound messages
use App\Models\PaymentLink;  // optional model to track links

class IntelligentSupportService
{
    // ====== Tunables / Policies ======
    const NON_COLLECTION_THRESHOLD = 3;   // e.g., if customer has >= 3 past uncollected orders
    const MAX_PRODUCTS_RETURNED    = 5;
    const MAX_ORDERS_RETURNED      = 5;

    public function __construct(
        protected ?string $llmApiKey = null,
        protected ?string $llmModel  = 'gpt-4o-mini', // any JSON-capable model
    ) {
        $this->llmApiKey = $this->llmApiKey ?? env('OPENAI_API_KEY');
    }

    /**
     * Main entry point. Pass customerId (or phone), raw message text, and optional attachments.
     * Returns a structured array: ['reply' => string, 'actions' => [...]].
     *
     * @param int|string $customerId
     * @param string $text
     * @param array $attachments [ ['type'=>'image|link|file', 'url'=>...], ... ]
     */
    public function handleCustomerMessage(string $text, array $recentOrders = [], array $attachments = []): array
    {
        Log::info('IntelligentSupportService: handleCustomerMessage called', [
            'text' => $text,
            'recentOrders_count' => count($recentOrders),
            'attachments' => $attachments,
        ]);

        $orderDetails = '';

        if (!empty($recentOrders)) {
            Log::info('IntelligentSupportService: Processing recent orders', [
                'orders' => $recentOrders,
            ]);
            $orderDetails .= "Here are the customer's recent orders:\n";
            foreach ($recentOrders as $order) {
                Log::debug('Order details', ['order' => $order]);
                $orderDetails .= "- Order #{$this->getOrderProp($order, 'order_no')}: {$this->getOrderProp($order, 'status')} (Delivery: {$this->getOrderProp($order, 'delivery_date')})\n";
                // Vendor
                if (!empty($this->getOrderProp($order, 'vendor')['name'])) {
                    $orderDetails .= "  Vendor: {$this->getOrderProp($order, 'vendor')['name']}\n";
                } else {
                    $orderDetails .= "  Vendor: N/A\n";
                }
                // Rider
                if (!empty($this->getOrderProp($order, 'rider')['name'])) {
                    $orderDetails .= "  Rider: {$this->getOrderProp($order, 'rider')['name']}\n";
                } else {
                    $orderDetails .= "  Rider: N/A\n";
                }
                // Agent
                if (!empty($this->getOrderProp($order, 'agent')['name'])) {
                    $orderDetails .= "  Agent: {$this->getOrderProp($order, 'agent')['name']}\n";
                } else {
                    $orderDetails .= "  Agent: N/A\n";
                }
                // Client
                if (!empty($this->getOrderProp($order, 'client')['name'])) {
                    $orderDetails .= "  Client: {$this->getOrderProp($order, 'client')['name']}";
                    if (!empty($this->getOrderProp($order, 'client')['phone_number'])) {
                        $orderDetails .= " (Phone: {$this->getOrderProp($order, 'client')['phone_number']})";
                    }
                    $orderDetails .= "\n";
                } else {
                    $orderDetails .= "  Client: N/A\n";
                }
                // Items
                $orderDetails .= "  Items:\n";
                $orderItems = $this->getOrderProp($order, 'order_items');
                if (!empty($orderItems)) {
                    foreach ($orderItems as $item) {
                        $itemName = is_array($item) ? ($item['name'] ?? 'Item') : ($item->name ?? 'Item');
                        $itemQty = is_array($item) ? ($item['quantity'] ?? 1) : ($item->quantity ?? 1);
                        $orderDetails .= "    - {$itemName} x{$itemQty}\n";
                    }
                } else {
                    $orderDetails .= "    - No items found\n";
                }
            }
            $orderDetails .= "\n";

            $customer = $this->getOrderProp($recentOrders[0], 'client') ?? null; 
            Log::info('IntelligentSupportService: Extracted customer from recentOrders', [
                'customer' => $customer,
            ]);

            $company = Country::with('waybillSettings')->first();
            Log::info('IntelligentSupportService: Loaded company info', [
                'company' => $company,
            ]);
            $history = $this->getRecentMessageHistory($customer, 20);
            Log::info('IntelligentSupportService: Loaded message history', [
                'history_count' => count($history),
            ]);

            $nlu = $this->extractIntentEntities($text, $attachments, $customer, $recentOrders, $company);
            Log::info('IntelligentSupportService: NLU result', [
                'nlu' => $nlu,
            ]);

            $policy = $this->evaluatePolicy($customer);
            Log::info('IntelligentSupportService: Policy evaluation', [
                'policy' => $policy,
            ]);

            $reply   = null;
            $actions = [];

            foreach ($nlu['intents'] as $intent) {
                $name        = $intent['name'] ?? 'unknown';
                $confidence  = (float) ($intent['confidence'] ?? 0);
                $entities    = $nlu['entities'] ?? [];

                Log::info('IntelligentSupportService: Processing intent', [
                    'intent' => $name,
                    'confidence' => $confidence,
                    'entities' => $entities,
                ]);

                if ($confidence < 0.35) {
                    Log::info('IntelligentSupportService: Skipping low confidence intent', [
                        'intent' => $name,
                        'confidence' => $confidence,
                    ]);
                    continue;
                }

                switch ($name) {
                    case 'ask_company_location':
                        Log::info('IntelligentSupportService: Handling ask_company_location');
                        [$reply, $actions] = $this->handleCountryLocation($company);
                        break 2;
                    case 'ask_order_price':
                        Log::info('IntelligentSupportService: Handling ask_order_price');
                        [$reply, $actions] = $this->handleOrderPrice($entities, $recentOrders, $policy);
                        break 2;
                    case 'ask_order_status_or_delivery':
                        Log::info('IntelligentSupportService: Handling ask_order_status_or_delivery');
                        [$reply, $actions] = $this->handleOrderStatus($entities, $recentOrders, $policy);
                        break 2;
                    case 'reference_previous_call':
                        Log::info('IntelligentSupportService: Handling reference_previous_call');
                        [$reply, $actions] = $this->handlePreviousCall($history, $recentOrders, $policy);
                        break 2;
                    case 'confirm_address_flow':
                        Log::info('IntelligentSupportService: Handling confirm_address_flow');
                        [$reply, $actions] = $this->handleAddressConfirmation($entities, $customer, $recentOrders, $policy);
                        break 2;
                    case 'request_callback':
                        Log::info('IntelligentSupportService: Handling request_callback');
                        [$reply, $actions] = $this->handleCallbackRequest($customer, $entities);
                        break 2;
                    case 'product_info':
                        Log::info('IntelligentSupportService: Handling product_info');
                        [$reply, $actions] = $this->handleProductInfo($entities);
                        break 2;
                    case 'change_order':
                        Log::info('IntelligentSupportService: Handling change_order');
                        [$reply, $actions] = $this->handleOrderChange($entities, $recentOrders);
                        break 2;
                    case 'share_media_or_link':
                        Log::info('IntelligentSupportService: Handling share_media_or_link');
                        [$reply, $actions] = $this->handleMediaOrLink($attachments, $entities, $recentOrders);
                        break 2;
                    case 'payment_required':
                        Log::info('IntelligentSupportService: Handling payment_required');
                        [$reply, $actions] = $this->handlePaymentRequired($customer, $policy, $recentOrders);
                        break 2;
                    case 'small_talk_busy':
                        Log::info('IntelligentSupportService: Handling small_talk_busy');
                        [$reply, $actions] = $this->handleBusySmallTalk($customer);
                        break 2;
                    default:
                        Log::info('IntelligentSupportService: Handling fallback');
                        [$reply, $actions] = $this->handleFallback($customer, $company, $recentOrders);
                        break 2;
                }
            }

            Log::info('IntelligentSupportService: Final reply and actions', [
                'reply' => $reply,
                'actions' => $actions,
            ]);

            // Ensure $reply is a string to avoid array to string conversion error
            if (!is_string($reply)) {
                $reply = is_array($reply) ? json_encode($reply, JSON_UNESCAPED_UNICODE) : strval($reply);
            }
            $this->storeOutboundMessage($customer, $reply, $actions);
            return [
                'reply' => $reply,
                'actions' => $actions,
            ];
        }

        Log::info('IntelligentSupportService: No recent orders found');
        return [
            'reply' => "No recent orders found. Please share your order number or let us know how we can assist you.",
            'actions' => [],
        ];
    }

    // ======================= NLU (LLM + fallback) =======================

    protected function extractIntentEntities(string $text, array $attachments, $customer, $recentOrders, $company): array
    {
        // If you prefer: add quick keyword rules for speed; fall back to LLM if ambiguous
        $ruleHit = $this->ruleBasedIntent($text, $attachments);
        if ($ruleHit) return $ruleHit;

        $companyBlock = $company ? [
            'name'     => $company->name,
            'location' => $company->location,
            'phone'    => $company->phone,
            'email'    => $company->email,
        ] : null;

        $ordersBlock = collect($recentOrders)->take(3)->map(function ($o) {
            return [
                'order_no'      => $this->getOrderProp($o, 'order_no'),
                'status'        => $this->getOrderProp($o, 'status'),
                'delivery_date' => $this->getOrderProp($o, 'delivery_date') ? Carbon::parse($this->getOrderProp($o, 'delivery_date'))->toDateString() : null,
                'total_price'   => $this->getOrderProp($o, 'total_price'),
            ];
        })->values()->all();

        $sys = <<<SYS
You are an intent extractor for a courier company's assistant. 
Return STRICT JSON with keys: intents (array of {name, confidence 0-1}), entities (object).
INTENTS:
- ask_company_location
- ask_order_price
- ask_order_status_or_delivery
- reference_previous_call
- confirm_address_flow
- request_callback
- product_info
- change_order
- share_media_or_link
- payment_required
- small_talk_busy
If unclear, return one intent "payment_required" ONLY if policy text suggests prepayment is needed. Otherwise pick the closest above.
ENTITIES can include:
- order_no (string)
- address (string)
- product_name (string)
- change_request (string)
- scheduled_time (string)
- link_url (string)
- media_type (image|video|link)
Return JSON only.
SYS;

        $customerData = null;
        if (is_array($customer)) {
            $customerData = [
                'id'    => $customer['id'] ?? null,
                'name'  => $customer['name'] ?? null,
                'phone' => $customer['phone'] ?? $customer['phone_number'] ?? null,
            ];
        } elseif (is_object($customer)) {
            $customerData = [
                'id'    => $customer->id ?? null,
                'name'  => $customer->name ?? null,
                'phone' => $customer->phone ?? $customer->phone_number ?? null,
            ];
        }

        $usr = [
            'message' => $text,
            'attachments' => $attachments,
            'company' => $companyBlock,
            'recent_orders_preview' => $ordersBlock,
            'customer' => $customerData,
        ];

        try {
            $payload = [
                'model' => $this->llmModel,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.1,
                'messages' => [
                    ['role' => 'system', 'content' => $sys],
                    ['role' => 'user', 'content' => json_encode($usr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)],
                ],
            ];

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->llmApiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', $payload);

            if ($res->successful()) {
                $raw = $res->json()['choices'][0]['message']['content'] ?? '{}';
                $parsed = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && !empty($parsed['intents'])) {
                    return [
                        'intents'  => $parsed['intents'],
                        'entities' => $parsed['entities'] ?? [],
                    ];
                }
            }

            Log::warning('LLM NLU failed, falling back to rules', ['status' => $res->status(), 'body' => $res->body()]);
        } catch (\Throwable $e) {
            Log::error('NLU exception', ['e' => $e->getMessage()]);
        }

        // Fallback to rule-based
        return $this->ruleBasedIntent($text, $attachments) ?? [
            'intents'  => [['name' => 'ask_order_status_or_delivery', 'confidence' => 0.4]],
            'entities' => [],
        ];
    }

    protected function ruleBasedIntent(string $text, array $attachments): ?array
    {
        $t = mb_strtolower($text);

        if (preg_match('/where.*(office|location|are you)/', $t)) {
            return ['intents' => [['name' => 'ask_company_location', 'confidence' => 0.9]], 'entities' => []];
        }
        if (preg_match('/(price|how much).*(order|#)/', $t)) {
            preg_match('/#?(\d{3,})/', $t, $m);
            return ['intents' => [['name' => 'ask_order_price', 'confidence' => 0.8]], 'entities' => ['order_no' => $m[1] ?? null]];
        }
        if (preg_match('/(status|delivery|when).*order/', $t)) {
            preg_match('/#?(\d{3,})/', $t, $m);
            return ['intents' => [['name' => 'ask_order_status_or_delivery', 'confidence' => 0.75]], 'entities' => ['order_no' => $m[1] ?? null]];
        }
        if (preg_match('/you.*(called|call).*earlier|missed call/', $t)) {
            return ['intents' => [['name' => 'reference_previous_call', 'confidence' => 0.9]], 'entities' => []];
        }
        if (preg_match('/(confirm|change).*address|deliver to/', $t)) {
            return ['intents' => [['name' => 'confirm_address_flow', 'confidence' => 0.7]], 'entities' => []];
        }
        if (preg_match('/call.*back|please call|ring me/', $t)) {
            return ['intents' => [['name' => 'request_callback', 'confidence' => 0.85]], 'entities' => []];
        }
        if (preg_match('/(info|details|usage|source).*product|about .*lipstick|foundation|sku/i', $t)) {
            return ['intents' => [['name' => 'product_info', 'confidence' => 0.7]], 'entities' => []];
        }
        if (preg_match('/(change|edit|modify).*order|add item|remove item/', $t)) {
            return ['intents' => [['name' => 'change_order', 'confidence' => 0.7]], 'entities' => []];
        }
        if (!empty($attachments)) {
            return ['intents' => [['name' => 'share_media_or_link', 'confidence' => 0.85]], 'entities' => ['media_type' => $attachments[0]['type'] ?? 'unknown', 'link_url' => $attachments[0]['url'] ?? null]];
        }
        if (preg_match('/busy|will call|hold on|later/i', $t)) {
            return ['intents' => [['name' => 'small_talk_busy', 'confidence' => 0.8]], 'entities' => []];
        }

        return null;
    }

    // ======================= Policy evaluation =======================
    protected function evaluatePolicy($customer): array
    {
        $customerId = null;
        if (is_array($customer)) {
            $customerId = $customer['id'] ?? null;
        } elseif (is_object($customer)) {
            $customerId = $customer->id ?? null;
        }

        if (!$customerId) {
            return [
                'uncollected_count' => 0,
                'prepay_required'   => false,
            ];
        }

        // Example: count past "uncollected" orders
        $uncollectedCount = Order::whereHas('client', function ($q) use ($customerId) {
            $q->where('id', $customerId);
            })
            ->whereIn('status', ['Uncollected', 'Returned', 'Delivery Failed'])
            ->count();

        $prepayRequired = $uncollectedCount >= self::NON_COLLECTION_THRESHOLD;

        return [
            'uncollected_count' => $uncollectedCount,
            'prepay_required'   => $prepayRequired,
        ];
    }

    // ======================= Handlers =======================

    protected function handleCountryLocation(?Country $company): array
    {
        if (!$company) {
            return ["I'm missing our company profile at the moment. Could you share the area you're in so I can guide you to the nearest branch?", []];
        }
        $reply = "Our company is **{$company->name}**. Main office: **{$company->location}**. You can reach us at **{$company->phone}** or **{$company->email}**. How can I help with your delivery today?";
        return [$reply, []];
    }

    protected function handleOrderPrice(array $entities, $recentOrders, array $policy): array
    {
        $order = $this->findOrderByEntityOrRecent($entities, $recentOrders);
        if (!$order) {
            return ["Could you share the order number so I can confirm the exact amount?", []];
        }

        // Safely get order properties
        $orderNo    = $this->getOrderProp($order, 'order_no');
        $totalPrice = $this->getOrderProp($order, 'total_price');

        $lines = [];
        $lines[] = "Order **#{$orderNo}** total: **" . $this->formatMoney($totalPrice) . "**.";
        if ($policy['prepay_required']) {
            $pay = $this->issuePaymentLink($order);
            $lines[] = "Because of previous uncollected orders, we'll need **prepayment** before dispatch. You can pay securely here: {$pay['url']}.";
            $actions = [
                ['type' => 'payment_link', 'order_no' => $orderNo, 'url' => $pay['url'], 'expires_at' => $pay['expires_at']]
            ];
            return [implode(' ', $lines), $actions];
        }

        return [implode(' ', $lines), []];
    }

    protected function handleOrderStatus(array $entities, $recentOrders, array $policy): array
    {
        $order = $this->findOrderByEntityOrRecent($entities, $recentOrders);
        if (!$order) {
            return ["Please share your order number so I can check the latest status and confirm delivery details.", []];
        }

        $orderNo = $this->getOrderProp($order, 'order_no');
        $status = $this->getOrderProp($order, 'status');
        $deliveryDate = $this->getOrderProp($order, 'delivery_date');
        $eta = $deliveryDate ? Carbon::parse($deliveryDate)->toFormattedDateString() : 'TBD';

        $reply = "Order **#{$orderNo}** is currently **{$status}**. Estimated delivery: **{$eta}**.";
        if ($policy['prepay_required'] && !in_array($status, ['Delivered', 'Cancelled'])) {
            $pay = $this->issuePaymentLink($order);
            $reply .= " Due to prior uncollected orders, **prepayment** is required. Pay here: {$pay['url']}.";
            return [$reply, [['type' => 'payment_link', 'order_no' => $orderNo, 'url' => $pay['url']]]];
        }
        $reply .= " Would you like me to confirm your delivery address now?";
        return [$reply, []];
    }

    protected function handlePreviousCall(array $history, $recentOrders, array $policy): array
    {
        $lastCall = collect($history)->firstWhere('type', 'call') ?? collect($history)->first();
        $order    = collect($recentOrders)->first();

        $reply = "Yes, we reached out earlier";
        if ($lastCall) {
            $when = Carbon::parse($lastCall['created_at'])->diffForHumans();
            $reply .= " ({$when}).";
        } else {
            $reply .= ".";
        }

        if ($order) {
            $reply .= " Regarding order **#{$this->getOrderProp($order, 'order_no')}** (status: **{$this->getOrderProp($order, 'status')}**). Can I confirm your delivery address to proceed?";
        } else {
            $reply .= " Could you share the order number so I can confirm details and the delivery address?";
        }

        return [$reply, []];
    }

    protected function handleAddressConfirmation(array $entities, $customer, $recentOrders, array $policy): array
    {
        $order = $this->findOrderByEntityOrRecent($entities, $recentOrders);
        if (!$order) {
            return ["Please share the order number to confirm the delivery address.", []];
        }

        $currentAddress = $this->getOrderProp($order, 'delivery_address');
        if (!$currentAddress && $customer) {
            $currentAddress = is_array($customer) ? 
                ($customer['address'] ?? null) : 
                ($customer->address ?? null);
        }

        if ($currentAddress) {
            $reply = "I have the address as: **{$currentAddress}**. Is this correct? Reply **YES** to confirm or share the new address.";
        } else {
            $reply = "I don't have a delivery address on file. Please share the exact location (estate/road/building/floor/landmark).";
        }

        return [$reply, [['type' => 'await_confirmation', 'order_no' => $this->getOrderProp($order, 'order_no')]]];
    }

    protected function handleCallbackRequest($customer, array $entities): array
    {
        $time = $entities['scheduled_time'] ?? null;
        $task = $this->createCallbackTask($customer, $time);
        $reply = $time
            ? "Got it. I've scheduled a callback **{$time}**. You'll receive a call from our team."
            : "Sure — I've requested a callback. Our team will reach out shortly. If you prefer a specific time, let me know.";
        return [$reply, [['type' => 'schedule_callback', 'task_id' => $task['id'], 'scheduled_time' => $task['scheduled_time']]]];
    }

    protected function handleProductInfo(array $entities): array
    {
        $nameQ = $entities['product_name'] ?? null;
        $products = $this->searchProducts($nameQ);
        if ($products->isEmpty()) {
            return ["I couldn't find that product. Could you share the exact product name or a photo/link?", []];
        }

        $lines = ["Here's what I found:"];
        foreach ($products as $p) {
            $lines[] = "• **{$p->name}** — {$this->truncate($p->description)} Source: " . ($p->source ?? 'N/A') . ". Usage: " . ($p->usage ?? 'N/A') . ". Price: " . $this->formatMoney($p->price) . ".";
        }
        return [implode("\n", $lines), []];
    }

    protected function handleOrderChange(array $entities, $recentOrders): array
    {
        $order = $this->findOrderByEntityOrRecent($entities, $recentOrders);
        if (!$order) {
            return ["Please share the order number and the exact change you'd like (add/remove item, change quantity/address).", []];
        }
        $change = $entities['change_request'] ?? 'your requested update';
        // TODO: validate if order is still editable per your business rules
        return ["Noted for order **#{$this->getOrderProp($order, 'order_no')}**: {$change}. I'll update it and confirm shortly. If there's a price change, I'll share the new total.", [['type' => 'order_change_request', 'order_no' => $this->getOrderProp($order, 'order_no'), 'change' => $change]]];
    }

    protected function handleMediaOrLink(array $attachments, array $entities, $recentOrders): array
    {
        $att = $attachments[0] ?? null;
        if (!$att) return ["I received your attachment. Could you confirm if this is for a new order or updating an existing one (share order #)?", []];

        if ($att['type'] === 'image') {
            // Hook: image recognition service here (extract product / packaging / address labels)
            return ["Thanks for the photo. Do you want to **order this item** or **update an existing order**? If update, please share the order number.", [['type' => 'ingest_image', 'url' => $att['url']]]];
        }

        if ($att['type'] === 'link') {
            // TikTok or product links → fetch metadata via your link inspector
            return ["Got the link. Should I add this item to your order or create a new order from it? If this is for an existing order, please share the order number.", [['type' => 'ingest_link', 'url' => $att['url']]]];
        }

        return ["Attachment received. How would you like me to proceed?", []];
    }

    protected function handlePaymentRequired($customer, array $policy, $recentOrders): array
    {
        $order = collect($recentOrders)->first();
        if (!$order) {
            return ["You have several uncollected orders. Before we dispatch new items, we require **prepayment**. Would you like me to create a payment link now?", []];
        }
        $pay = $this->issuePaymentLink($order);
        $reply = "Due to previous uncollected orders (**{$policy['uncollected_count']}**), **prepayment** is required. Pay for order **#{$this->getOrderProp($order, 'order_no')}** here: {$pay['url']}.";
        return [$reply, [['type' => 'payment_link', 'order_no' => $this->getOrderProp($order, 'order_no'), 'url' => $pay['url']]]];
    }

    protected function handleBusySmallTalk($customer): array
    {
        $reply = "No worries — I'll hold. When you're ready, just send **order number** or say **deliver** and I'll handle it. If you prefer, I can **schedule a callback** at your best time.";
        return [$reply, []];
    }

    protected function handleFallback($customer, $company, $recentOrders): array
    {
        $intro = $company ? "You're chatting with **{$company->name}** support." : "You're chatting with our support assistant.";
        $reply = "{$intro} I can help with **delivery status**, **address confirmation**, **pricing**, **product details**, and **payments**. Please share your **order number** or tell me what you'd like to do.";
        return [$reply, []];
    }

    // ======================= Helpers =======================

    protected function getOrderProp($order, $key)
    {
        if (is_array($order)) {
            return $order[$key] ?? null;
        } elseif (is_object($order)) {
            return $order->$key ?? null;
        }
        return null;
    }

    protected function findCustomer($customerId)
    {
        return is_numeric($customerId)
            ? User::find($customerId)
            : User::where('phone', $customerId)->orWhere('email', $customerId)->first();
    }

    protected function getRecentOrdersForCustomer($customer, int $limit)
    {
        if (!$customer) return collect();
        $customerId = is_array($customer) ? ($customer['id'] ?? null) : ($customer->id ?? null);
        if (!$customerId) return collect();
        
        return Order::where('customer_id', $customerId)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    protected function getRecentMessageHistory($customer, int $limit)
    {
        if (!$customer) {
            Log::info('getRecentMessageHistory: No customer provided');
            return [];
        }

        // Convert array to object if necessary
        if (is_array($customer)) {
            $customer = (object) $customer;
        }

        // Extract phone numbers in all possible formats
        $phoneRaw = $customer->phone_number ?? $customer->phone ?? null;
        $phoneFormats = [];

        if ($phoneRaw) {
            // E.g. 254751458911
            $phoneFormats[] = $phoneRaw;
            // E.g. 254751458911@c.us
            if (!str_ends_with($phoneRaw, '@c.us')) {
                $phoneFormats[] = $phoneRaw . '@c.us';
            }
            // E.g. +254751458911
            if (!str_starts_with($phoneRaw, '+')) {
                $phoneFormats[] = '+' . $phoneRaw;
            }
            // Remove @c.us if present for plain number
            if (str_ends_with($phoneRaw, '@c.us')) {
                $plain = substr($phoneRaw, 0, -5);
                $phoneFormats[] = $plain;
                $phoneFormats[] = '+' . $plain;
            }
        }

        // Remove duplicates
        $phoneFormats = array_unique($phoneFormats);

        Log::info('getRecentMessageHistory: Fetching messages', [
            'customer_id' => $customer->id ?? null,
            'phone_formats' => $phoneFormats,
            'limit' => $limit,
        ]);

        $messages = Message::where(function ($q) use ($customer, $phoneFormats) {
                $q->where(function ($sub) use ($customer) {
                    $sub->where('messageable_id', $customer->id ?? null)
                        ->where('messageable_type', User::class);
                });
                if (!empty($phoneFormats)) {
                    foreach ($phoneFormats as $phone) {
                        $q->orWhere('to', $phone)
                          ->orWhere('from', $phone);
                    }
                }
            })
            ->latest('created_at')
            ->limit($limit)
            ->get([
                'id',
                'messageable_type',
                'messageable_id',
                'channel',
                'recipient_name',
                'recipient_phone',
                'content',
                'status',
                'sent_at',
                'response_payload',
                'created_at',
                'updated_at',
                'deleted_at',
                'from',
                'to',
                'body',
                'message_type',
                'media_url',
                'media_mime_type',
                'message_status',
                'external_message_id',
                'reply_to_message_id',
                'error_message',
                'timestamp',
                'direction',
                'delivered_at',
                'read_at',
                'failed_at'
            ])
            ->toArray();

        Log::info('getRecentMessageHistory: Messages fetched', [
            'count' => count($messages),
            'sample' => $messages[0] ?? null,
        ]);
        return $messages;
    }

    protected function findOrderByEntityOrRecent(array $entities, $recentOrders)
    {
        $orderNo = $entities['order_no'] ?? null;
        if ($orderNo) {
            return Order::where('order_no', $orderNo)->first();
        }
        return collect($recentOrders)->first();
    }

    protected function searchProducts(?string $query)
    {
        $q = Product::query();
        if ($query) {
            $q->where(function ($w) use ($query) {
                $w->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });
        }
        return $q->limit(self::MAX_PRODUCTS_RETURNED)->get();
    }

    protected function issuePaymentLink($order): array
    {
        // TODO: integrate real PG (M-Pesa STK, Flutterwave, Stripe, PayPal, etc.)
        $orderId = is_array($order) ? ($order['id'] ?? null) : ($order->id ?? null);
        $totalPrice = $this->getOrderProp($order, 'total_price');
        
        $fakeId = 'PAY' . now()->format('YmdHis') . $orderId;
        $url    = url("/pay/{$fakeId}");
        $expiresAt = now()->addHours(12)->toDateTimeString();

        if ($orderId) {
            PaymentLink::create([
                'order_id'   => $orderId,
                'code'       => $fakeId,
                'url'        => $url,
                'amount'     => $totalPrice,
                'expires_at' => $expiresAt,
                'status'     => 'pending',
            ]);
        }

        return ['id' => $fakeId, 'url' => $url, 'expires_at' => $expiresAt];
    }

    protected function createCallbackTask($customer, ?string $when): array
    {
        // TODO: save to tasks table / ticketing / CRM
        $id = 'CB' . now()->timestamp;
        $scheduled = $when ?: now()->addHours(2)->toDateTimeString();
        // persist...
        return ['id' => $id, 'scheduled_time' => $scheduled];
    }

    protected function storeOutboundMessage($customer, string $reply, array $actions = []): void
    {
        if (!$customer) return;
        
        $customerId = is_array($customer) ? ($customer['id'] ?? null) : ($customer->id ?? null);
        $customerName = is_array($customer) ? ($customer['name'] ?? null) : ($customer->name ?? null);
        $customerPhone = is_array($customer) ? 
            ($customer['phone'] ?? $customer['phone_number'] ?? null) : 
            ($customer->phone ?? $customer->phone_number ?? null);
        
        if (!$customerId) return;
        
        try {
            Message::create([
                'messageable_type'   => User::class,
                'messageable_id'     => $customerId,
                'channel'            => 'support',
                'recipient_name'     => $customerName,
                'recipient_phone'    => $customerPhone,
                'content'            => $reply,
                'status'             => 'sent',
                'sent_at'            => now(),
                'response_payload'   => json_encode($actions),
                'created_at'         => now(),
                'updated_at'         => now(),
                'direction'          => 'outgoing',
                'message_type'       => 'text',
                'message_status'     => 'pending',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to store outbound message', ['e' => $e->getMessage()]);
        }
    }

    protected function formatMoney($amount): string
    {
        if ($amount === null) return 'N/A';
        return 'KES ' . number_format((float)$amount, 2);
    }

    protected function truncate(?string $text, int $len = 140): string
    {
        if (!$text) return 'N/A';
        return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
    }
}