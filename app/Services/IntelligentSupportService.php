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
// use App\Models\PaymentLink;  // optional model to track links

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
    // public function handleCustomerMessage(string $text, array $recentOrders = [], array $attachments = []): array
    // {
    //     Log::info('IntelligentSupportService: handleCustomerMessage called', [
    //         'text' => $text,
    //         'recentOrders_count' => count($recentOrders),
    //         'attachments' => $attachments,
    //     ]);

    //     if (preg_match('/busy|will call|hold on|later/i', $t)) {
    //         return ['intents' => [['name' => 'small_talk_busy', 'confidence' => 0.8]], 'entities' => []];
    //     }

    //     return null;
    // }

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

    // ======================= Enhanced Handlers =======================

    protected function handleCountryLocation(?Country $company): array
    {
        if (!$company) {
            return ["I'm missing our company profile at the moment. Could you share the area you're in so I can guide you to the nearest branch?", []];
        }

        // Extract waybill settings if available
        $waybill = $company->waybillSettings ?? null;
        $officeName = $waybill?->name ?? $company->name;
        $officeAddress = $waybill?->address ?? $company->location;
        $officePhone = $waybill?->phone ?? $company->phone;
        $officeEmail = $waybill?->email ?? $company->email;

        $reply = "Our company is **{$company->name}**. Main office: **{$officeName}**, address: **{$officeAddress}**. You can reach us at **{$officePhone}** or **{$officeEmail}**. How can I help with your delivery today?";
        return [$reply, []];
    }

    protected function handleOrderPrice(array $entities, $recentOrders, array $policy): array
    {
        $order = $this->findOrderByEntityOrRecent($entities, $recentOrders);
        if (!$order) {
            return ["Could you share the order number so I can confirm the exact amount?", []];
        }

        // Safely get order properties
        $orderNo = $this->getOrderProp($order, 'order_no');
        $totalPrice = $this->getOrderProp($order, 'total_price');
        $status = $this->getOrderProp($order, 'status');
        $paymentStatus = $this->getOrderProp($order, 'payment_status');

        $lines = [];
        $lines[] = "Order **#{$orderNo}** total: **" . $this->formatMoney($totalPrice) . "**.";
        
        // Add payment status context
        if ($paymentStatus) {
            $lines[] = "Payment status: **{$paymentStatus}**.";
        }

        if ($policy['prepay_required'] && !in_array($status, ['Delivered', 'Cancelled']) && $paymentStatus !== 'paid') {
            $pay = $this->issuePaymentLink($order);
            $lines[] = "Because of previous uncollected orders ({$policy['uncollected_count']}), we'll need **prepayment** before dispatch. You can pay securely here: {$pay['url']}.";
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
        $deliveryAddress = $this->getOrderProp($order, 'delivery_address');
        $rider = $this->getOrderProp($order, 'rider');
        $deliveryAttempts = $this->getOrderProp($order, 'delivery_attempts', 0);
        
        $eta = $deliveryDate ? Carbon::parse($deliveryDate)->toFormattedDateString() : 'TBD';

        $reply = "Order **#{$orderNo}** is currently **{$status}**. Estimated delivery: **{$eta}**.";
        
        // Add contextual information based on status
        if ($status === 'Out for Delivery' && $rider) {
            $riderData = $this->normalizeRelationshipData($rider);
            $reply .= " Your rider **{$riderData['name']}** is on the way.";
            if ($riderData['phone']) {
                $reply .= " Contact: **{$riderData['phone']}**.";
            }
        }
        
        if ($deliveryAttempts > 0) {
            $reply .= " Previous delivery attempts: **{$deliveryAttempts}**.";
        }
        
        if ($deliveryAddress) {
            $reply .= " Delivery address: **{$this->truncate($deliveryAddress, 60)}**.";
        }

        if ($policy['prepay_required'] && !in_array($status, ['Delivered', 'Cancelled'])) {
            $pay = $this->issuePaymentLink($order);
            $reply .= " Due to prior uncollected orders, **prepayment** is required. Pay here: {$pay['url']}.";
            return [$reply, [['type' => 'payment_link', 'order_no' => $orderNo, 'url' => $pay['url']]]];
        }
        
        $reply .= " Would you like me to confirm your delivery address?";
        return [$reply, []];
    }

    protected function handlePreviousCall(array $history, $recentOrders, array $policy): array
    {
        $lastCall = collect($history)->firstWhere('type', 'call') ?? collect($history)->first();
        $order = collect($recentOrders)->first();

        $reply = "Yes, we reached out earlier";
        if ($lastCall) {
            $when = Carbon::parse($lastCall['created_at'])->diffForHumans();
            $reply .= " ({$when}).";
        } else {
            $reply .= ".";
        }

        if ($order) {
            $orderNo = $this->getOrderProp($order, 'order_no');
            $status = $this->getOrderProp($order, 'status');
            $reply .= " Regarding order **#{$orderNo}** (status: **{$status}**).";
            
            // Add specific context based on order status
            if ($status === 'Pending') {
                $reply .= " Can I confirm your delivery address to proceed with dispatch?";
            } elseif ($status === 'Out for Delivery') {
                $reply .= " Your order is currently out for delivery. Any specific concerns?";
            } elseif (in_array($status, ['Uncollected', 'Delivery Failed'])) {
                $reply .= " There was an issue with delivery. Shall we reschedule or arrange pickup?";
            } else {
                $reply .= " How can I assist you with this order?";
            }
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

        $orderNo = $this->getOrderProp($order, 'order_no');
        $currentAddress = $this->getOrderProp($order, 'delivery_address');
        $deliveryAttempts = $this->getOrderProp($order, 'delivery_attempts', 0);
        
        if (!$currentAddress && $customer) {
            $currentAddress = is_array($customer) ? 
                ($customer['address'] ?? null) : 
                ($customer->address ?? null);
        }

        if ($currentAddress) {
            $reply = "I have the address for order **#{$orderNo}** as: **{$currentAddress}**.";
            if ($deliveryAttempts > 0) {
                $reply .= " (Previous attempts: {$deliveryAttempts})";
            }
            $reply .= " Is this correct? Reply **YES** to confirm or share the new address.";
        } else {
            $reply = "I don't have a delivery address on file for order **#{$orderNo}**. Please share the exact location (estate/road/building/floor/landmark).";
        }

        return [$reply, [['type' => 'await_confirmation', 'order_no' => $orderNo]]];
    }

    protected function handleCallbackRequest($customer, array $entities): array
    {
        $time = $entities['scheduled_time'] ?? null;
        $customerName = is_array($customer) ? ($customer['name'] ?? 'Customer') : ($customer->name ?? 'Customer');
        
        $task = $this->createCallbackTask($customer, $time);
        $reply = $time
            ? "Got it, **{$customerName}**. I've scheduled a callback for **{$time}**. You'll receive a call from our team."
            : "Sure, **{$customerName}** — I've requested a callback. Our team will reach out shortly. If you prefer a specific time, let me know.";
        return [$reply, [['type' => 'schedule_callback', 'task_id' => $task['id'], 'scheduled_time' => $task['scheduled_time']]]];
    }

    protected function handleProductInfo(array $entities): array
    {
        $nameQ = $entities['product_name'] ?? null;
        $products = $this->searchProducts($nameQ);
        if ($products->isEmpty()) {
            return ["I couldn't find that product. Could you share the exact product name, SKU, or a photo/link?", []];
        }

        $lines = ["Here's what I found:"];
        foreach ($products as $p) {
            $lines[] = "• **{$p->name}** — {$this->truncate($p->description)} Source: " . ($p->source ?? 'N/A') . ". Usage: " . ($p->usage ?? 'N/A') . ". Price: " . $this->formatMoney($p->price) . ".";
        }
        $lines[] = "Would you like to place an order or need more details about any of these products?";
        return [implode("\n", $lines), []];
    }

    protected function handleOrderChange(array $entities, $recentOrders): array
    {
        $order = $this->findOrderByEntityOrRecent($entities, $recentOrders);
        if (!$order) {
            return ["Please share the order number and the exact change you'd like (add/remove item, change quantity/address).", []];
        }
        
        $orderNo = $this->getOrderProp($order, 'order_no');
        $status = $this->getOrderProp($order, 'status');
        $change = $entities['change_request'] ?? 'your requested update';
        
        // Check if order is still editable
        if (in_array($status, ['Delivered', 'Cancelled', 'Out for Delivery'])) {
            return ["Sorry, order **#{$orderNo}** (status: **{$status}**) cannot be modified at this stage. Would you like to place a new order instead?", []];
        }
        
        return ["Noted for order **#{$orderNo}**: {$change}. I'll update it and confirm shortly. If there's a price change, I'll share the new total.", [['type' => 'order_change_request', 'order_no' => $orderNo, 'change' => $change]]];
    }

    protected function handleMediaOrLink(array $attachments, array $entities, $recentOrders): array
    {
        $att = $attachments[0] ?? null;
        if (!$att) return ["I received your attachment. Could you confirm if this is for a new order or updating an existing one (share order #)?", []];

        $recentOrderNo = collect($recentOrders)->first() ? $this->getOrderProp(collect($recentOrders)->first(), 'order_no') : null;

        if ($att['type'] === 'image') {
            $reply = "Thanks for the photo. Do you want to **order this item** or **update an existing order**?";
            if ($recentOrderNo) {
                $reply .= " If this is for order **#{$recentOrderNo}**, just confirm and I'll add it.";
            }
            return [$reply, [['type' => 'ingest_image', 'url' => $att['url']]]];
        }

        if ($att['type'] === 'link') {
            $reply = "Got the link. Should I add this item to your order or create a new order from it?";
            if ($recentOrderNo) {
                $reply .= " If this is for order **#{$recentOrderNo}**, let me know.";
            }
            return [$reply, [['type' => 'ingest_link', 'url' => $att['url']]]];
        }

        return ["Attachment received. How would you like me to proceed?", []];
    }

    protected function handlePaymentRequired($customer, array $policy, $recentOrders): array
    {
        $order = collect($recentOrders)->first();
        $customerName = is_array($customer) ? ($customer['name'] ?? 'Customer') : ($customer->name ?? 'Customer');
        
        if (!$order) {
            return ["Hello **{$customerName}**, you have several uncollected orders. Before we dispatch new items, we require **prepayment**. Would you like me to create a payment link now?", []];
        }
        
        $orderNo = $this->getOrderProp($order, 'order_no');
        $pay = $this->issuePaymentLink($order);
        $reply = "Hello **{$customerName}**, due to previous uncollected orders (**{$policy['uncollected_count']}**), **prepayment** is required. Pay for order **#{$orderNo}** here: {$pay['url']}.";
        return [$reply, [['type' => 'payment_link', 'order_no' => $orderNo, 'url' => $pay['url']]]];
    }

    protected function handleBusySmallTalk($customer): array
    {
        $customerName = is_array($customer) ? ($customer['name'] ?? 'Customer') : ($customer->name ?? 'Customer');
        $reply = "No worries, **{$customerName}** — I'll hold. When you're ready, just send **order number** or say **deliver** and I'll handle it. If you prefer, I can **schedule a callback** at your best time.";
        return [$reply, []];
    }

    protected function handleFallback($customer, $company, $recentOrders): array
    {
        $companyName = $company ? $company->name : 'our company';
        $customerName = is_array($customer) ? ($customer['name'] ?? 'Customer') : ($customer->name ?? 'Customer');
        
        $intro = "Hello **{$customerName}**, you're chatting with **{$companyName}** support.";
        $reply = "{$intro} I can help with **delivery status**, **address confirmation**, **pricing**, **product details**, and **payments**.";
        
        // Add context about recent orders
        if (!empty($recentOrders)) {
            $recentOrder = collect($recentOrders)->first();
            $orderNo = $this->getOrderProp($recentOrder, 'order_no');
            $status = $this->getOrderProp($recentOrder, 'status');
            $reply .= " I see your recent order **#{$orderNo}** (status: **{$status}**).";
        }
        
        $reply .= " Please share your **order number** or tell me what you'd like to do.";
        return [$reply, []];
    }

    // ======================= Helper Methods =======================

    protected function getOrderProp($order, $key, $default = null)
    {
        if (is_array($order)) {
            return $order[$key] ?? $default;
        } elseif (is_object($order)) {
            return $order->$key ?? $default;
        }
        return $default;
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
            ->with(['vendor', 'rider', 'agent', 'client', 'orderItems.product'])
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
                'id', 'messageable_type', 'messageable_id', 'channel', 'recipient_name', 
                'recipient_phone', 'content', 'status', 'sent_at', 'response_payload', 
                'created_at', 'updated_at', 'deleted_at', 'from', 'to', 'body', 
                'message_type', 'media_url', 'media_mime_type', 'message_status', 
                'external_message_id', 'reply_to_message_id', 'error_message', 
                'timestamp', 'direction', 'delivered_at', 'read_at', 'failed_at'
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
            return Order::where('order_no', $orderNo)->with(['vendor', 'rider', 'agent', 'client', 'orderItems.product'])->first();
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
        $url = url("/pay/{$fakeId}");
        $expiresAt = now()->addHours(12)->toDateTimeString();

        if ($orderId) {
            // PaymentLink::create([
            //     'order_id'   => $orderId,
            //     'code'       => $fakeId,
            //     'url'        => $url,
            //     'amount'     => $totalPrice,
            //     'expires_at' => $expiresAt,
            //     'status'     => 'pending',
            // ]);
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

    /**
     * Main entry point. Pass customerId (or phone), raw message text, and optional attachments.
     * Returns a structured array: ['reply' => string, 'actions' => [...]].
     *
     * @param string $text
     * @param array $recentOrders
     * @param array $attachments
     * @return array
     */
    // public function handleCustomerMessage(string $text, array $recentOrders = [], array $attachments = []): array
    // {
    //     Log::info('IntelligentSupportService: handleCustomerMessage called', [
    //         'text' => $text,
    //         'recentOrders_count' => count($recentOrders),
    //         'attachments' => $attachments,
    //     ]);

    //     if (empty($recentOrders)) {
    //         Log::info('IntelligentSupportService: No recent orders found');
    //         return [
    //             'reply' => "No recent orders found. Please share your order number or let us know how we can assist you.",
    //             'actions' => [],
    //         ];
    //     }

    //     $customer = $this->getOrderProp($recentOrders[0], 'client') ?? null; 
    //     Log::info('IntelligentSupportService: Extracted customer from recentOrders', [
    //         'customer' => $customer,
    //     ]);

    //     $company = Country::with('waybillSettings')->first();
    //     Log::info('IntelligentSupportService: Loaded company info', [
    //         'company' => $company,
    //     ]);

    //     $history = $this->getRecentMessageHistory($customer, 20);
    //     Log::info('IntelligentSupportService: Loaded message history', [
    //         'history_count' => count($history),
    //     ]);

    //     $policy = $this->evaluatePolicy($customer);
    //     Log::info('IntelligentSupportService: Policy evaluation', [
    //         'policy' => $policy,
    //     ]);

    //     // Use hybrid approach: Quick rule-based check first, then AI reasoning
    //     $quickIntent = $this->quickRuleBasedIntent($text, $attachments);

    //     if ($quickIntent && $quickIntent['confidence'] > 0.85) {
    //         // High confidence rule-based match, process directly
    //         Log::info('IntelligentSupportService: High confidence rule match', $quickIntent);
    //         return $this->processIntent($quickIntent, $text, $customer, $recentOrders, $company, $history, $policy, $attachments);
    //     }

    //     // Use AI for complex reasoning with full context
    //     $aiResponse = $this->getHybridAiResponse($text, $customer, $recentOrders, $company, $history, $policy, $attachments);

    //     if ($aiResponse) {
    //         Log::info('IntelligentSupportService: AI response generated', [
    //             'response_length' => strlen($aiResponse['reply']),
    //             'actions_count' => count($aiResponse['actions']),
    //         ]);

    //         $this->storeOutboundMessage($customer, $aiResponse['reply'], $aiResponse['actions']);
    //         return $aiResponse;
    //     }

    //     // Fallback to rule-based processing
    //     $fallbackIntent = $this->ruleBasedIntent($text, $attachments) ?? [
    //         'name' => 'ask_order_status_or_delivery',
    //         'confidence' => 0.4,
    //         'entities' => [],
    //     ];

    //     Log::info('IntelligentSupportService: Using fallback intent', $fallbackIntent);
    //     return $this->processIntent($fallbackIntent, $text, $customer, $recentOrders, $company, $history, $policy, $attachments);
    // }

    /**
     * Enhanced hybrid AI response with comprehensive context and reasoning
     */
    protected function getHybridAiResponse(string $text, $customer, array $recentOrders, $company, array $history, array $policy, array $attachments): ?array
    {
        try {
            $contextualInfo = $this->buildComprehensiveContext($customer, $recentOrders, $company, $history, $policy, $attachments);

            $systemPrompt = $this->buildEnhancedSystemPrompt($contextualInfo);
            $userMessage = $this->buildContextualUserMessage($text, $contextualInfo, $attachments);

            $payload = [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.2,
                'max_tokens' => 800,
            ];

            Log::info('IntelligentSupportService: AI payload prepared', [
                'model' => $payload['model'],
                'system_prompt_length' => strlen($systemPrompt),
                'user_message_length' => strlen($userMessage),
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', $payload);

            Log::info('IntelligentSupportService: AI response received', [
                'status' => $response->status(),
                'response_size' => strlen($response->body())
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                Log::info('IntelligentSupportService: AI content extracted', ['content_length' => strlen($content)]);

                return $this->parseAiResponse($content, $recentOrders, $customer, $policy);
            }

            Log::error('AI response failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Hybrid AI response exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Build comprehensive context from all available information
     */
    protected function buildComprehensiveContext($customer, array $recentOrders, $company, array $history, array $policy, array $attachments): array
    {
        $context = [
            'timestamp' => now()->toISOString(),
            'customer' => $this->normalizeCustomerData($customer),
            'company' => $this->normalizeCompanyData($company),
            'policy' => $policy,
            'attachments' => $attachments,
        ];

        // Enhanced order details with relationships
        $context['recent_orders'] = collect($recentOrders)->map(function ($order) {
            return [
                'id' => $this->getOrderProp($order, 'id'),
                'order_no' => $this->getOrderProp($order, 'order_no'),
                'status' => $this->getOrderProp($order, 'status'),
                'total_price' => $this->getOrderProp($order, 'total_price'),
                'delivery_date' => $this->getOrderProp($order, 'delivery_date'),
                'delivery_address' => $this->getOrderProp($order, 'delivery_address'),
                'created_at' => $this->getOrderProp($order, 'created_at'),
                'payment_status' => $this->getOrderProp($order, 'payment_status'),
                'vendor' => $this->normalizeRelationshipData($this->getOrderProp($order, 'vendor')),
                'rider' => $this->normalizeRelationshipData($this->getOrderProp($order, 'rider')),
                'agent' => $this->normalizeRelationshipData($this->getOrderProp($order, 'agent')),
                'client' => $this->normalizeRelationshipData($this->getOrderProp($order, 'client')),
                'items' => $this->normalizeOrderItems($this->getOrderProp($order, 'order_items')),
                'tracking_info' => $this->getOrderProp($order, 'tracking_info'),
                'notes' => $this->getOrderProp($order, 'notes'),
                'delivery_attempts' => $this->getOrderProp($order, 'delivery_attempts', 0),
            ];
        })->toArray();

        // Recent interaction history with sentiment analysis
        $context['conversation_history'] = collect($history)->take(10)->map(function ($msg) {
            return [
                'timestamp' => $msg['created_at'] ?? $msg['timestamp'],
                'direction' => $msg['direction'] ?? ($msg['messageable_id'] ? 'outgoing' : 'incoming'),
                'content' => $msg['content'] ?? $msg['body'],
                'channel' => $msg['channel'] ?? 'unknown',
                'status' => $msg['status'] ?? $msg['message_status'],
            ];
        })->toArray();

        // Customer insights
        $context['customer_insights'] = [
            'total_orders' => $this->getCustomerOrderCount($customer),
            'average_order_value' => $this->getCustomerAverageOrderValue($customer),
            'preferred_delivery_times' => $this->getCustomerPreferredDeliveryTimes($customer),
            'communication_preference' => $this->getCustomerCommunicationPreference($customer),
            'loyalty_tier' => $this->getCustomerLoyaltyTier($customer),
        ];

        return $context;
    }

    /**
     * Build enhanced system prompt with business rules and context awareness
     */
    protected function buildEnhancedSystemPrompt(array $context): string
    {
        $companyName = $context['company']['name'] ?? 'the company';
        $prepayRequired = $context['policy']['prepay_required'] ?? false;
        $uncollectedCount = $context['policy']['uncollected_count'] ?? 0;

        return <<<PROMPT
You are an intelligent customer support assistant for {$companyName}, a courier and delivery company. You have access to comprehensive customer and order information.

CURRENT CONTEXT:
- Customer: {$context['customer']['name']} (ID: {$context['customer']['id']})
- Phone: {$context['customer']['phone']}
- Recent Orders: {$this->countArray($context['recent_orders'])} orders available
- Policy Status: {($prepayRequired ? "PREPAYMENT REQUIRED ({$uncollectedCount} uncollected orders)" : "Standard service")}
- Conversation History: {$this->countArray($context['conversation_history'])} recent messages
- Customer Tier: {$context['customer_insights']['loyalty_tier']}

RESPONSE GUIDELINES:
1. **Be contextually aware**: Reference specific order numbers, dates, and details when relevant
2. **Be proactive**: Anticipate customer needs based on order status and history
3. **Handle payments intelligently**: If prepayment is required, explain why and provide clear next steps
4. **Address delivery concerns**: Use tracking info, delivery attempts, and rider details
5. **Maintain professional tone**: Be helpful, empathetic, and solution-oriented
6. **Provide actionable information**: Include specific timelines, contact details, and next steps

BUSINESS RULES:
- Orders with "Uncollected", "Returned", or "Delivery Failed" status may require prepayment for future orders
- Always confirm delivery addresses for pending orders
- Provide accurate pricing information from order data
- Escalate complex issues appropriately
- Offer alternative solutions when primary options aren't available

RESPONSE FORMAT:
Provide a clear, helpful response that addresses the customer's query using the available context. Be specific about order details, timelines, and next steps.

If actions are needed (payment links, callbacks, address confirmations), mention them naturally in your response.
PROMPT;
    }

    /**
     * Helper to count arrays for prompt interpolation
     */
    protected function countArray($arr): int
    {
        return is_array($arr) ? count($arr) : (is_object($arr) && method_exists($arr, 'count') ? $arr->count() : 0);
    }

    /**
     * Build contextual user message with all relevant information
     */
    protected function buildContextualUserMessage(string $originalText, array $context, array $attachments): string
    {
        $message = "CUSTOMER MESSAGE: \"{$originalText}\"\n\n";

        // Add recent orders context
        if (!empty($context['recent_orders'])) {
            $message .= "RECENT ORDERS:\n";
            foreach ($context['recent_orders'] as $order) {
                $message .= "• Order #{$order['order_no']}: {$order['status']} - {$this->formatMoney($order['total_price'])}\n";
                $message .= "  Delivery: {$order['delivery_date']}, Address: {$order['delivery_address']}\n";
                if (!empty($order['vendor']['name'])) {
                    $message .= "  Vendor: {$order['vendor']['name']}\n";
                }
                if (!empty($order['rider']['name'])) {
                    $message .= "  Rider: {$order['rider']['name']} ({$order['rider']['phone']})\n";
                }
                if (!empty($order['items'])) {
                    $message .= "  Items: " . collect($order['items'])->pluck('name')->implode(', ') . "\n";
                }
                $message .= "\n";
            }
        }

        // Add conversation context
        if (!empty($context['conversation_history'])) {
            $message .= "RECENT CONVERSATION:\n";
            foreach (array_slice($context['conversation_history'], 0, 3) as $msg) {
                $direction = $msg['direction'] === 'outgoing' ? 'Support' : 'Customer';
                $timestamp = Carbon::parse($msg['timestamp'])->diffForHumans();
                $message .= "• {$direction} ({$timestamp}): " . $this->truncate($msg['content'], 100) . "\n";
            }
            $message .= "\n";
        }

        // Add policy context
        if ($context['policy']['prepay_required']) {
            $message .= "POLICY NOTE: Customer has {$context['policy']['uncollected_count']} uncollected orders. Prepayment required for new orders.\n\n";
        }

        // Add attachments
        if (!empty($attachments)) {
            $message .= "ATTACHMENTS: Customer shared " . count($attachments) . " attachment(s)\n";
            foreach ($attachments as $att) {
                $message .= "• {$att['type']}: {$att['url']}\n";
            }
            $message .= "\n";
        }

        $message .= "Please provide a helpful, contextual response addressing the customer's needs.";

        return $message;
    }

    /**
     * Parse AI response and extract actions
     */
    protected function parseAiResponse(string $content, array $recentOrders, $customer, array $policy): array
    {
        $actions = [];
        $reply = $content;

        // Extract payment links if mentioned
        if (strpos($content, 'payment') !== false && $policy['prepay_required']) {
            $order = collect($recentOrders)->first();
            if ($order) {
                $paymentLink = $this->issuePaymentLink($order);
                $actions[] = [
                    'type' => 'payment_link',
                    'order_no' => $this->getOrderProp($order, 'order_no'),
                    'url' => $paymentLink['url'],
                    'expires_at' => $paymentLink['expires_at']
                ];

                // Add payment link to reply if not already present
                if (strpos($reply, 'http') === false) {
                    $reply .= " You can pay here: {$paymentLink['url']}";
                }
            }
        }

        // Extract callback requests
        if (preg_match('/call.*back|callback|call you/i', $content)) {
            $callbackTask = $this->createCallbackTask($customer, null);
            $actions[] = [
                'type' => 'schedule_callback',
                'task_id' => $callbackTask['id'],
                'scheduled_time' => $callbackTask['scheduled_time']
            ];
        }

        // Extract address confirmation needs
        if (preg_match('/confirm.*address|address.*confirm/i', $content)) {
            $order = collect($recentOrders)->first();
            if ($order) {
                $actions[] = [
                    'type' => 'await_confirmation',
                    'order_no' => $this->getOrderProp($order, 'order_no')
                ];
            }
        }

        return [
            'reply' => $reply,
            'actions' => $actions
        ];
    }

    /**
     * Quick rule-based intent detection for high-confidence cases
     */
    protected function quickRuleBasedIntent(string $text, array $attachments): ?array
    {
        $t = mb_strtolower($text);

        // High confidence patterns
        $patterns = [
            'ask_company_location' => [
                'patterns' => ['/where.*(office|location|are you)/'],
                'confidence' => 0.9
            ],
            'ask_order_price' => [
                'patterns' => ['/how much.*(cost|price)/', '/(price|cost).*order/'],
                'confidence' => 0.85
            ],
            'request_callback' => [
                'patterns' => ['/call.*back/', '/please call/', '/ring me/'],
                'confidence' => 0.9
            ],
            'share_media_or_link' => [
                'patterns' => [],
                'confidence' => !empty($attachments) ? 0.95 : 0
            ]
        ];

        foreach ($patterns as $intent => $config) {
            if ($config['confidence'] > 0.85) {
                if ($intent === 'share_media_or_link' && !empty($attachments)) {
                    return [
                        'name' => $intent,
                        'confidence' => $config['confidence'],
                        'entities' => [
                            'media_type' => $attachments[0]['type'] ?? 'unknown',
                            'media_url' => $attachments[0]['url'] ?? null
                        ]
                    ];
                }

                foreach ($config['patterns'] as $pattern) {
                    if (preg_match($pattern, $t)) {
                        return [
                            'name' => $intent,
                            'confidence' => $config['confidence'],
                            'entities' => []
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Process intent with full context
     */
    protected function processIntent(array $intent, string $text, $customer, array $recentOrders, $company, array $history, array $policy, array $attachments): array
    {
        $name = $intent['name'];
        $entities = $intent['entities'] ?? [];

        Log::info('IntelligentSupportService: Processing intent', [
            'intent' => $name,
            'confidence' => $intent['confidence'] ?? 0,
            'entities' => $entities,
        ]);

        switch ($name) {
            case 'ask_company_location':
                [$reply, $actions] = $this->handleCountryLocation($company);
                break;
            case 'ask_order_price':
                [$reply, $actions] = $this->handleOrderPrice($entities, $recentOrders, $policy);
                break;
            case 'ask_order_status_or_delivery':
                [$reply, $actions] = $this->handleOrderStatus($entities, $recentOrders, $policy);
                break;
            case 'reference_previous_call':
                [$reply, $actions] = $this->handlePreviousCall($history, $recentOrders, $policy);
                break;
            case 'confirm_address_flow':
                [$reply, $actions] = $this->handleAddressConfirmation($entities, $customer, $recentOrders, $policy);
                break;
            case 'request_callback':
                [$reply, $actions] = $this->handleCallbackRequest($customer, $entities);
                break;
            case 'product_info':
                [$reply, $actions] = $this->handleProductInfo($entities);
                break;
            case 'change_order':
                [$reply, $actions] = $this->handleOrderChange($entities, $recentOrders);
                break;
            case 'share_media_or_link':
                [$reply, $actions] = $this->handleMediaOrLink($attachments, $entities, $recentOrders);
                break;
            case 'payment_required':
                [$reply, $actions] = $this->handlePaymentRequired($customer, $policy, $recentOrders);
                break;
            case 'small_talk_busy':
                [$reply, $actions] = $this->handleBusySmallTalk($customer);
                break;
            default:
                [$reply, $actions] = $this->handleFallback($customer, $company, $recentOrders);
                break;
        }

        // Ensure reply is string
        if (!is_string($reply)) {
            $reply = is_array($reply) ? json_encode($reply, JSON_UNESCAPED_UNICODE) : strval($reply);
        }

        $this->storeOutboundMessage($customer, $reply, $actions);
        return [
            'reply' => $reply,
            'actions' => $actions,
        ];
    }

    // ======================= Data Normalization Helpers =======================

    protected function normalizeCustomerData($customer): array
    {
        if (is_array($customer)) {
            return [
                'id' => $customer['id'] ?? null,
                'name' => $customer['name'] ?? 'Customer',
                'phone' => $customer['phone'] ?? $customer['phone_number'] ?? null,
                'email' => $customer['email'] ?? null,
                'address' => $customer['address'] ?? null,
            ];
        } elseif (is_object($customer)) {
            return [
                'id' => $customer->id ?? null,
                'name' => $customer->name ?? 'Customer',
                'phone' => $customer->phone ?? $customer->phone_number ?? null,
                'email' => $customer->email ?? null,
                'address' => $customer->address ?? null,
            ];
        }
        return ['id' => null, 'name' => 'Customer', 'phone' => null, 'email' => null, 'address' => null];
    }

    protected function normalizeCompanyData($company): array
    {
        if (!$company) return ['name' => 'Company', 'location' => null, 'phone' => null, 'email' => null];

        $waybill = $company->waybillSettings ?? null;
        return [
            'name' => $company->name,
            'location' => $waybill?->address ?? $company->location,
            'phone' => $waybill?->phone ?? $company->phone,
            'email' => $waybill?->email ?? $company->email,
        ];
    }

    protected function normalizeRelationshipData($relation): ?array
    {
        if (!$relation) return null;

        if (is_array($relation)) {
            return [
                'id' => $relation['id'] ?? null,
                'name' => $relation['name'] ?? null,
                'phone' => $relation['phone'] ?? $relation['phone_number'] ?? null,
            ];
        } elseif (is_object($relation)) {
            return [
                'id' => $relation->id ?? null,
                'name' => $relation->name ?? null,
                'phone' => $relation->phone ?? $relation->phone_number ?? null,
            ];
        }
        return null;
    }

    protected function normalizeOrderItems($items): array
    {
        if (!$items) return [];

        return collect($items)->map(function ($item) {
            if (is_array($item)) {
                return [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? 'Item',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? null,
                ];
            } elseif (is_object($item)) {
                return [
                    'id' => $item->id ?? null,
                    'name' => $item->name ?? 'Item',
                    'quantity' => $item->quantity ?? 1,
                    'price' => $item->price ?? null,
                ];
            }
            return ['id' => null, 'name' => 'Item', 'quantity' => 1, 'price' => null];
        })->toArray();
    }

    // ======================= Customer Insight Helpers =======================

    protected function getCustomerOrderCount($customer): int
    {
        if (!$customer) return 0;
        $customerId = is_array($customer) ? ($customer['id'] ?? null) : ($customer->id ?? null);
        if (!$customerId) return 0;

        return Order::whereHas('client', function ($q) use ($customerId) {
            $q->where('id', $customerId);
        })->count();
    }

    protected function getCustomerAverageOrderValue($customer): float
    {
        if (!$customer) return 0.0;
        $customerId = is_array($customer) ? ($customer['id'] ?? null) : ($customer->id ?? null);
        if (!$customerId) return 0.0;

        return Order::whereHas('client', function ($q) use ($customerId) {
            $q->where('id', $customerId);
        })->avg('total_price') ?? 0.0;
    }

    protected function getCustomerPreferredDeliveryTimes($customer): ?string
    {
        // Implement based on your business logic
        return null;
    }

    protected function getCustomerCommunicationPreference($customer): string
    {
        // Implement based on your business logic
        return 'whatsapp'; // Default
    }

    protected function getCustomerLoyaltyTier($customer): string
    {
        if (!$customer) return 'new';

        $orderCount = $this->getCustomerOrderCount($customer);
        $avgOrderValue = $this->getCustomerAverageOrderValue($customer);

        if ($orderCount >= 10 && $avgOrderValue >= 5000) return 'platinum';
        if ($orderCount >= 5 && $avgOrderValue >= 2000) return 'gold';
        if ($orderCount >= 2) return 'silver';
        return 'bronze';
    }

    // ======================= Legacy methods (keeping existing functionality) =======================

    // protected function extractIntentEntities(string $text, array $attachments, $customer, $recentOrders, $company): array
    // {
    //     // Keep existing method for backward compatibility
    //     return $this->getHybridIntent($text, $attachments, $customer, $recentOrders, $company);
    // }

    // protected function getHybridIntent(string $text, array $attachments, $customer, $recentOrders, $company): array
    // {
    //     $ruleHit = $this->ruleBasedIntent($text, $attachments);
    //     if ($ruleHit) return $ruleHit;

    //     // Fallback to basic intent
    //     return [
    //         'intents' => [['name' => 'ask_order_status_or_delivery', 'confidence' => 0.4]],
    //         'entities' => [],
    //     ];
    // }

    // protected function ruleBasedIntent(string $text, array $attachments): ?array
    // {
    //     $t = mb_strtolower($text);

    //     if (preg_match('/where.*(office|location|are you)/', $t)) {
    //         return ['intents' => [['name' => 'ask_company_location', 'confidence' => 0.9]], 'entities' => []];
    //     }
    //     if (preg_match('/(price|how much).*(order|#)/', $t)) {
    //         preg_match('/#?(\d{3,})/', $t, $m);
    //         return ['intents' => [['name' => 'ask_order_price', 'confidence' => 0.8]], 'entities' => ['order_no' => $m[1] ?? null]];
    //     }
    //     if (preg_match('/(status|delivery|when).*order/', $t)) {
    //         preg_match('/#?(\d{3,})/', $t, $m);
    //         return ['intents' => [['name' => 'ask_order_status_or_delivery', 'confidence' => 0.75]], 'entities' => ['order_no' => $m[1] ?? null]];
    //     }
    //     if (preg_match('/you.*(called|call).*earlier|missed call/', $t)) {
    //         return ['intents' => [['name' => 'reference_previous_call', 'confidence' => 0.9]], 'entities' => []];
    //     }
    //     if (preg_match('/(confirm|change).*address|deliver to/', $t)) {
    //         return ['intents' => [['name' => 'confirm_address_flow', 'confidence' => 0.7]], 'entities' => []];
    //     }
    //     if (preg_match('/call.*back|please call|ring me/', $t)) {
    //         return ['intents' => [['name' => 'request_callback', 'confidence' => 0.85]], 'entities' => []];
    //     }
    //     if (preg_match('/(info|details|usage|source).*product|about .*lipstick|foundation|sku/i', $t)) {
    //         return ['intents' => [['name' => 'product_info', 'confidence' => 0.7]], 'entities' => []];
    //     }
    //     if (preg_match('/(change|edit|modify).*order|add item|remove item/', $t)) {
    //         return ['intents' => [['name' => 'change_order', 'confidence' => 0.7]], 'entities' => []];
    //     }
    //     if (!empty($attachments)) {
    //         return ['intents' => [['name' => 'share_media_or_link', 'confidence' => 0.85]], 'entities' => ['media_type' => $attachments[0]['type'] ?? 'unknown', 'link_url' => $attachments[0]['url'] ?? null]];
    //     }
    //     if (preg_match('/(pay|payment|prepay).*order|order.*pay/', $t)) {
    //         return ['intents' => [['name' => 'payment_required', 'confidence' => 0.8]], 'entities' => []];
    //     }
    //     if (preg_match('/(busy|swamped|overwhelmed|tied up|occupied)/', $t)) {
    //         return ['intents' => [['name' => 'small_talk_busy', 'confidence' => 0.75]], 'entities' => []];
    //     }
    //     // Fallback to generic intent
    //     return ['intents' => [['name' => 'ask_order_status_or_delivery', 'confidence' => 0.4]], 'entities' => []];
    // }



     /**
     * Build comprehensive context from all available information
     */
    // protected function buildComprehensiveContext($customer, array $recentOrders, $company, array $history, array $policy, array $attachments): array
    // {
    //     $context = [
    //         'timestamp' => now()->toISOString(),
    //         'customer' => $this->normalizeCustomerData($customer),
    //         'company' => $this->normalizeCompanyData($company),
    //         'policy' => $policy,
    //         'attachments' => $attachments,
    //     ];

    //     // Enhanced order details with relationships
    //     $context['recent_orders'] = collect($recentOrders)->map(function ($order) {
    //         return [
    //             'id' => $this->getOrderProp($order, 'id'),
    //             'order_no' => $this->getOrderProp($order, 'order_no'),
    //             'status' => $this->getOrderProp($order, 'status'),
    //             'total_price' => $this->getOrderProp($order, 'total_price'),
    //             'delivery_date' => $this->getOrderProp($order, 'delivery_date'),
    //             'delivery_address' => $this->getOrderProp($order, 'delivery_address'),
    //             'created_at' => $this->getOrderProp($order, 'created_at'),
    //             'payment_status' => $this->getOrderProp($order, 'payment_status'),
    //             'vendor' => $this->normalizeRelationshipData($this->getOrderProp($order, 'vendor')),
    //             'rider' => $this->normalizeRelationshipData($this->getOrderProp($order, 'rider')),
    //             'agent' => $this->normalizeRelationshipData($this->getOrderProp($order, 'agent')),
    //             'client' => $this->normalizeRelationshipData($this->getOrderProp($order, 'client')),
    //             'items' => $this->normalizeOrderItems($this->getOrderProp($order, 'order_items')),
    //             'tracking_info' => $this->getOrderProp($order, 'tracking_info'),
    //             'notes' => $this->getOrderProp($order, 'notes'),
    //             'delivery_attempts' => $this->getOrderProp($order, 'delivery_attempts', 0),
    //         ];
    //     })->toArray();

    //     // Recent interaction history with sentiment analysis
    //     $context['conversation_history'] = collect($history)->take(10)->map(function ($msg) {
    //         return [
    //             'timestamp' => $msg['created_at'] ?? $msg['timestamp'],
    //             'direction' => $msg['direction'] ?? ($msg['messageable_id'] ? 'outgoing' : 'incoming'),
    //             'content' => $msg['content'] ?? $msg['body'],
    //             'channel' => $msg['channel'] ?? 'unknown',
    //             'status' => $msg['status'] ?? $msg['message_status'],
    //         ];
    //     })->toArray();

    //     // Customer insights
    //     $context['customer_insights'] = [
    //         'total_orders' => $this->getCustomerOrderCount($customer),
    //         'average_order_value' => $this->getCustomerAverageOrderValue($customer),
    //         'preferred_delivery_times' => $this->getCustomerPreferredDeliveryTimes($customer),
    //         'communication_preference' => $this->getCustomerCommunicationPreference($customer),
    //         'loyalty_tier' => $this->getCustomerLoyaltyTier($customer),
    //     ];

    //     return $context;
    // }

    /**
     * Build enhanced system prompt with business rules and context awareness
     */
//     protected function buildEnhancedSystemPrompt(array $context): string
//     {
//         $companyName = $context['company']['name'] ?? 'the company';
//         $prepayRequired = $context['policy']['prepay_required'] ?? false;
//         $uncollectedCount = $context['policy']['uncollected_count'] ?? 0;

//         return <<<PROMPT
// You are an intelligent customer support assistant for {$companyName}, a courier and delivery company. You have access to comprehensive customer and order information.

// CURRENT CONTEXT:
// - Customer: {$context['customer']['name']} (ID: {$context['customer']['id']})
// - Phone: {$context['customer']['phone']}
// - Recent Orders: {$this->countArray($context['recent_orders'])} orders available
// - Policy Status: {($prepayRequired ? "PREPAYMENT REQUIRED ({$uncollectedCount} uncollected orders)" : "Standard service")}
// - Conversation History: {$this->countArray($context['conversation_history'])} recent messages
// - Customer Tier: {$context['customer_insights']['loyalty_tier']}

// RESPONSE GUIDELINES:
// 1. **Be contextually aware**: Reference specific order numbers, dates, and details when relevant
// 2. **Be proactive**: Anticipate customer needs based on order status and history
// 3. **Handle payments intelligently**: If prepayment is required, explain why and provide clear next steps
// 4. **Address delivery concerns**: Use tracking info, delivery attempts, and rider details
// 5. **Maintain professional tone**: Be helpful, empathetic, and solution-oriented
// 6. **Provide actionable information**: Include specific timelines, contact details, and next steps

// BUSINESS RULES:
// - Orders with "Uncollected", "Returned", or "Delivery Failed" status may require prepayment for future orders
// - Always confirm delivery addresses for pending orders
// - Provide accurate pricing information from order data
// - Escalate complex issues appropriately
// - Offer alternative solutions when primary options aren't available

// RESPONSE FORMAT:
// Provide a clear, helpful response that addresses the customer's query using the available context. Be specific about order details, timelines, and next steps.

// If actions are needed (payment links, callbacks, address confirmations), mention them naturally in your response.
// PROMPT;
//     }

//     /**
//      * Build contextual user message with all relevant information
//      */
//     protected function buildContextualUserMessage(string $originalText, array $context, array $attachments): string
//     {
//         $message = "CUSTOMER MESSAGE: \"{$originalText}\"\n\n";

//         // Add recent orders context
//         if (!empty($context['recent_orders'])) {
//             $message .= "RECENT ORDERS:\n";
//             foreach ($context['recent_orders'] as $order) {
//                 $message .= "• Order #{$order['order_no']}: {$order['status']} - {$this->formatMoney($order['total_price'])}\n";
//                 $message .= "  Delivery: {$order['delivery_date']}, Address: {$order['delivery_address']}\n";
//                 if (!empty($order['vendor']['name'])) {
//                     $message .= "  Vendor: {$order['vendor']['name']}\n";
//                 }
//                 if (!empty($order['rider']['name'])) {
//                     $message .= "  Rider: {$order['rider']['name']} ({$order['rider']['phone']})\n";
//                 }
//                 if (!empty($order['items'])) {
//                     $message .= "  Items: " . collect($order['items'])->pluck('name')->implode(', ') . "\n";
//                 }
//                 $message .= "\n";
//             }
//         }

//         // Add conversation context
//         if (!empty($context['conversation_history'])) {
//             $message .= "RECENT CONVERSATION:\n";
//             foreach (array_slice($context['conversation_history'], 0, 3) as $msg) {
//                 $direction = $msg['direction'] === 'outgoing' ? 'Support' : 'Customer';
//                 $timestamp = Carbon::parse($msg['timestamp'])->diffForHumans();
//                 $message .= "• {$direction} ({$timestamp}): " . $this->truncate($msg['content'], 100) . "\n";
//             }
//             $message .= "\n";
//         }

//         // Add policy context
//         if ($context['policy']['prepay_required']) {
//             $message .= "POLICY NOTE: Customer has {$context['policy']['uncollected_count']} uncollected orders. Prepayment required for new orders.\n\n";
//         }

//         // Add attachments
//         if (!empty($attachments)) {
//             $message .= "ATTACHMENTS: Customer shared " . count($attachments) . " attachment(s)\n";
//             foreach ($attachments as $att) {
//                 $message .= "• {$att['type']}: {$att['url']}\n";
//             }
//             $message .= "\n";
//         }

//         $message .= "Please provide a helpful, contextual response addressing the customer's needs.";

//         return $message;
//     }

//     /**
//      * Parse AI response and extract actions
//      */
//     protected function parseAiResponse(string $content, array $recentOrders, $customer, array $policy): array
//     {
//         $actions = [];
//         $reply = $content;

//         // Extract payment links if mentioned
//         if (strpos($content, 'payment') !== false && $policy['prepay_required']) {
//             $order = collect($recentOrders)->first();
//             if ($order) {
//                 $paymentLink = $this->issuePaymentLink($order);
//                 $actions[] = [
//                     'type' => 'payment_link',
//                     'order_no' => $this->getOrderProp($order, 'order_no'),
//                     'url' => $paymentLink['url'],
//                     'expires_at' => $paymentLink['expires_at']
//                 ];
                
//                 // Add payment link to reply if not already present
//                 if (strpos($reply, 'http') === false) {
//                     $reply .= " You can pay here: {$paymentLink['url']}";
//                 }
//             }
//         }

//         // Extract callback requests
//         if (preg_match('/call.*back|callback|call you/i', $content)) {
//             $callbackTask = $this->createCallbackTask($customer, null);
//             $actions[] = [
//                 'type' => 'schedule_callback',
//                 'task_id' => $callbackTask['id'],
//                 'scheduled_time' => $callbackTask['scheduled_time']
//             ];
//         }

//         // Extract address confirmation needs
//         if (preg_match('/confirm.*address|address.*confirm/i', $content)) {
//             $order = collect($recentOrders)->first();
//             if ($order) {
//                 $actions[] = [
//                     'type' => 'await_confirmation',
//                     'order_no' => $this->getOrderProp($order, 'order_no')
//                 ];
//             }
//         }

//         return [
//             'reply' => $reply,
//             'actions' => $actions
//         ];
//     }

//     /**
//      * Quick rule-based intent detection for high-confidence cases
//      */
//     protected function quickRuleBasedIntent(string $text, array $attachments): ?array
//     {
//         $t = mb_strtolower($text);

//         // High confidence patterns
//         $patterns = [
//             'ask_company_location' => [
//                 'patterns' => ['/where.*(office|location|are you)/'],
//                 'confidence' => 0.9
//             ],
//             'ask_order_price' => [
//                 'patterns' => ['/how much.*(cost|price)/', '/(price|cost).*order/'],
//                 'confidence' => 0.85
//             ],
//             'request_callback' => [
//                 'patterns' => ['/call.*back/', '/please call/', '/ring me/'],
//                 'confidence' => 0.9
//             ],
//             'share_media_or_link' => [
//                 'patterns' => [],
//                 'confidence' => !empty($attachments) ? 0.95 : 0
//             ]
//         ];

//         foreach ($patterns as $intent => $config) {
//             if ($config['confidence'] > 0.85) {
//                 if ($intent === 'share_media_or_link' && !empty($attachments)) {
//                     return [
//                         'name' => $intent,
//                         'confidence' => $config['confidence'],
//                         'entities' => [
//                             'media_type' => $attachments[0]['type'] ?? 'unknown',
//                             'media_url' => $attachments[0]['url'] ?? null
//                         ]
//                     ];
//                 }
                
//                 foreach ($config['patterns'] as $pattern) {
//                     if (preg_match($pattern, $t)) {
//                         return [
//                             'name' => $intent,
//                             'confidence' => $config['confidence'],
//                             'entities' => []
//                         ];
//                     }
//                 }
//             }
//         }

//         return null;
//     }

//     /**
//      * Process intent with full context
//      */
//     protected function processIntent(array $intent, string $text, $customer, array $recentOrders, $company, array $history, array $policy, array $attachments): array
//     {
//         $name = $intent['name'];
//         $entities = $intent['entities'] ?? [];

//         Log::info('IntelligentSupportService: Processing intent', [
//             'intent' => $name,
//             'confidence' => $intent['confidence'] ?? 0,
//             'entities' => $entities,
//         ]);

//         switch ($name) {
//             case 'ask_company_location':
//                 [$reply, $actions] = $this->handleCountryLocation($company);
//                 break;
//             case 'ask_order_price':
//                 [$reply, $actions] = $this->handleOrderPrice($entities, $recentOrders, $policy);
//                 break;
//             case 'ask_order_status_or_delivery':
//                 [$reply, $actions] = $this->handleOrderStatus($entities, $recentOrders, $policy);
//                 break;
//             case 'reference_previous_call':
//                 [$reply, $actions] = $this->handlePreviousCall($history, $recentOrders, $policy);
//                 break;
//             case 'confirm_address_flow':
//                 [$reply, $actions] = $this->handleAddressConfirmation($entities, $customer, $recentOrders, $policy);
//                 break;
//             case 'request_callback':
//                 [$reply, $actions] = $this->handleCallbackRequest($customer, $entities);
//                 break;
//             case 'product_info':
//                 [$reply, $actions] = $this->handleProductInfo($entities);
//                 break;
//             case 'change_order':
//                 [$reply, $actions] = $this->handleOrderChange($entities, $recentOrders);
//                 break;
//             case 'share_media_or_link':
//                 [$reply, $actions] = $this->handleMediaOrLink($attachments, $entities, $recentOrders);
//                 break;
//             case 'payment_required':
//                 [$reply, $actions] = $this->handlePaymentRequired($customer, $policy, $recentOrders);
//                 break;
//             case 'small_talk_busy':
//                 [$reply, $actions] = $this->handleBusySmallTalk($customer);
//                 break;
//             default:
//                 [$reply, $actions] = $this->handleFallback($customer, $company, $recentOrders);
//                 break;
//         }

//         // Ensure reply is string
//         if (!is_string($reply)) {
//             $reply = is_array($reply) ? json_encode($reply, JSON_UNESCAPED_UNICODE) : strval($reply);
//         }

//         $this->storeOutboundMessage($customer, $reply, $actions);
//         return [
//             'reply' => $reply,
//             'actions' => $actions,
//         ];
//     }

//     // ======================= Data Normalization Helpers =======================

//     protected function normalizeCustomerData($customer): array
//     {
//         if (is_array($customer)) {
//             return [
//                 'id' => $customer['id'] ?? null,
//                 'name' => $customer['name'] ?? 'Customer',
//                 'phone' => $customer['phone'] ?? $customer['phone_number'] ?? null,
//                 'email' => $customer['email'] ?? null,
//                 'address' => $customer['address'] ?? null,
//             ];
//         } elseif (is_object($customer)) {
//             return [
//                 'id' => $customer->id ?? null,
//                 'name' => $customer->name ?? 'Customer',
//                 'phone' => $customer->phone ?? $customer->phone_number ?? null,
//                 'email' => $customer->email ?? null,
//                 'address' => $customer->address ?? null,
//             ];
//         }
//         return ['id' => null, 'name' => 'Customer', 'phone' => null, 'email' => null, 'address' => null];
//     }

//     protected function normalizeCompanyData($company): array
//     {
//         if (!$company) return ['name' => 'Company', 'location' => null, 'phone' => null, 'email' => null];
        
//         $waybill = $company->waybillSettings ?? null;
//         return [
//             'name' => $company->name,
//             'location' => $waybill?->address ?? $company->location,
//             'phone' => $waybill?->phone ?? $company->phone,
//             'email' => $waybill?->email ?? $company->email,
//         ];
//     }

//     protected function normalizeRelationshipData($relation): ?array
//     {
//         if (!$relation) return null;
        
//         if (is_array($relation)) {
//             return [
//                 'id' => $relation['id'] ?? null,
//                 'name' => $relation['name'] ?? null,
//                 'phone' => $relation['phone'] ?? $relation['phone_number'] ?? null,
//             ];
//         } elseif (is_object($relation)) {
//             return [
//                 'id' => $relation->id ?? null,
//                 'name' => $relation->name ?? null,
//                 'phone' => $relation->phone ?? $relation->phone_number ?? null,
//             ];
//         }
//         return null;
//     }

//     protected function normalizeOrderItems($items): array
//     {
//         if (!$items) return [];
        
//         return collect($items)->map(function ($item) {
//             if (is_array($item)) {
//                 return [
//                     'id' => $item['id'] ?? null,
//                     'name' => $item['name'] ?? 'Item',
//                     'quantity' => $item['quantity'] ?? 1,
//                     'price' => $item['price'] ?? null,
//                 ];
//             } elseif (is_object($item)) {
//                 return [
//                     'id' => $item->id ?? null,
//                     'name' => $item->name ?? 'Item',
//                     'quantity' => $item->quantity ?? 1,
//                     'price' => $item->price ?? null,
//                 ];
//             }
//             return ['id' => null, 'name' => 'Item', 'quantity' => 1, 'price' => null];
//         })->toArray();
//     }

//     // ======================= Customer Insight Helpers =======================

//     protected function getCustomerOrderCount($customer): int
//     {
//         if (!$customer) return 0;
//         $customerId = is_array($customer) ? ($customer['id'] ?? null) : ($customer->id ?? null);
//         if (!$customerId) return 0;
        
//         return Order::whereHas('client', function ($q) use ($customerId) {
//             $q->where('id', $customerId);
//         })->count();
//     }

//     protected function getCustomerAverageOrderValue($customer): float
//     {
//         if (!$customer) return 0.0;
//         $customerId = is_array($customer) ? ($customer['id'] ?? null) : ($customer->id ?? null);
//         if (!$customerId) return 0.0;
        
//         return Order::whereHas('client', function ($q) use ($customerId) {
//             $q->where('id', $customerId);
//         })->avg('total_price') ?? 0.0;
//     }

//     protected function getCustomerPreferredDeliveryTimes($customer): ?string
//     {
//         // Implement based on your business logic
//         return null;
//     }

//     protected function getCustomerCommunicationPreference($customer): string
//     {
//         // Implement based on your business logic
//         return 'whatsapp'; // Default
//     }

//     protected function getCustomerLoyaltyTier($customer): string
//     {
//         if (!$customer) return 'new';
        
//         $orderCount = $this->getCustomerOrderCount($customer);
//         $avgOrderValue = $this->getCustomerAverageOrderValue($customer);
        
//         if ($orderCount >= 10 && $avgOrderValue >= 5000) return 'platinum';
//         if ($orderCount >= 5 && $avgOrderValue >= 2000) return 'gold';
//         if ($orderCount >= 2) return 'silver';
//         return 'bronze';
//     }

    // ======================= Legacy methods (keeping existing functionality) =======================

    protected function extractIntentEntities(string $text, array $attachments, $customer, $recentOrders, $company): array
    {
        // Keep existing method for backward compatibility
        return $this->getHybridIntent($text, $attachments, $customer, $recentOrders, $company);
    }

    protected function getHybridIntent(string $text, array $attachments, $customer, $recentOrders, $company): array
    {
        $ruleHit = $this->ruleBasedIntent($text, $attachments);
        if ($ruleHit) return $ruleHit;

        // Fallback to basic intent
        return [
            'intents' => [['name' => 'ask_order_status_or_delivery', 'confidence' => 0.4]],
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
        if (preg_match('/(pay|payment|prepay).*order|order.*pay/', $t)) {
            return ['intents' => [['name' => 'payment_required', 'confidence' => 0.8]], 'entities' => []];
        }
        if (preg_match('/(busy|swamped|overwhelmed|tied up|occupied)/', $t)) {
            return ['intents' => [['name' => 'small_talk_busy', 'confidence' => 0.75]], 'entities' => []];      
        }
        // Fallback to generic intent
        return ['intents' => [['name' => 'ask_order_status_or_delivery', 'confidence' => 0.4]], 'entities' => []];
    }   
}

   