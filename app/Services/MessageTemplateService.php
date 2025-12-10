<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;

use App\Models\Template;

class MessageTemplateService
{
    /**
     * Process template with placeholders
     */
    public function processTemplate(string $template, string $phone, array $clientData = []): string
    {
        Log::info('🔄 Processing template', [
            'template' => $template,
            'phone' => $phone,
            'client_data' => $clientData
        ]);

        // Merge client data with order data (pass order_id if available)
        $data = array_merge($clientData, $this->fetchOrderData($phone, $clientData['order_id'] ?? null));

        Log::info('📦 Merged data for placeholders', ['data' => $data]);

        return $this->processPlaceholders($template, $data);
    }

    /**
     * Fetch order data (searches local DB first, then external API)
     */
    protected function fetchOrderDatax(string $phone, array $clientData): array
    {
        $orderData = [
            'order_no' => '',
            'order_number' => '',
            'tracking_id' => '',
            'total_price' => '',
            'delivery_date' => '',
            'status' => '',
            'order_items' => '',
            'agent_name' => '',
            'vendor_name' => '',
            'website_url' => '',
            'zone' => '',
        ];

        // Find the most recent order from local database
        $order = $this->findLatestOrder($phone, $clientData['customer_id'] ?? null);

        if (!$order) {
            Log::debug('No orders found locally for phone', ['phone' => $phone]);



            return $orderData;
        }

        // Populate order data from local database
        $orderData['order_no'] = $order->order_no ?? $order->no ?? '';
        $orderData['order_number'] = $order->order_number ?? $order->number ?? '';
        $orderData['tracking_id'] = $order->tracking_id ?? $order->tracking_number ?? '';
        $orderData['total_price'] = $order->total_price ?? '';
        $orderData['delivery_date'] = $order->delivery_date ?? '';
        $orderData['status'] = $order->status ?? '';
        // $orderData['agent_name'] = $order->agent_name ?? '';
        // orderassignment 
        // assignments->role ='Delivery Agent'
        // assignments->role='CallAgent'
        // order vendor - vendor->name

        // $orderData['vendor_name'] = $order->vendor_name ?? '';
        $orderData['website_url'] = $order->website_url ?? '';
        $orderData['zone'] = $order->zone ?? '';
        $orderData['order_id'] = $order->id ?? null;

        // Format order items
        if (!empty($order->orderItems) && is_iterable($order->orderItems)) {
            $itemsList = [];
            foreach ($order->orderItems as $item) {
                $name = $item->name ?? $item->product_name ?? '';
                $qty = $item->quantity ?? $item->qty ?? 1;
                $itemsList[] = "{$qty} x {$name}";
            }
            $orderData['order_items'] = implode(', ', $itemsList);
        }

        Log::debug('Found order for phone', [
            'phone' => $phone,
            'order_id' => $order->id
        ]);

        return $orderData;
    }







    /**
     * Find latest order from local database
     */
    protected function findLatestOrder(string $phone, ?int $clientId): ?Order
    {
        $query = Order::with('orderItems');

        if ($clientId) {
            $order = $query->where('customer_id', $clientId)
                ->latest()
                ->first();

            if ($order) {
                return $order;
            }
        }

        // Try finding by phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        return Order::with('orderItems')
            ->whereHas('customer', function ($q) use ($cleanPhone) {
                $q->where(function ($subQ) use ($cleanPhone) {
                    $subQ->where('phone', 'LIKE', "%{$cleanPhone}%")
                        ->orWhere('alt_phone', 'LIKE', "%{$cleanPhone}%");
                });
            })
            ->latest()
            ->first();
    }

    /**
     * Process placeholders in template
     */
    protected function processPlaceholders(string $template, array $data): string
    {
        Log::info('🧩 Processing placeholders', [
            'template' => $template,
            'data_keys' => array_keys($data),
        ]);

        $message = $template;

        // First, handle complex loop structures like {{for product in products}}
        $message = $this->processLoops($message, $data);

        foreach ($data as $key => $value) {
            // Skip arrays - they should be handled by loops or ignored
            if (is_array($value)) {
                Log::debug("Skipping array placeholder: {$key}");
                continue;
            }

            // Handle null values
            if (is_null($value)) {
                $value = '';
            }

            // Handle boolean values
            if (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            }

            // Convert non-string values to string
            if (!is_string($value)) {
                $value = (string) $value;
            }

            // Replace placeholders (with and without spaces)
            $patterns = [
                '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                '/\{\{' . preg_quote($key, '/') . '\}\}/'
            ];

            foreach ($patterns as $pattern) {
                $message = preg_replace($pattern, $value, $message);
            }
        }

        // Remove any leftover unmatched placeholders
        $message = preg_replace('/\{\{[^}]*\}\}/', '', $message);

        // Clean up extra spaces and newlines
        $message = preg_replace('/\n\s*\n\s*\n/', "\n\n", $message); // Max 2 consecutive newlines
        $message = preg_replace('/ +/', ' ', $message); // Remove multiple spaces
        $message = trim($message);

        Log::info('✅ Finished processing template', [
            'final_message_length' => strlen($message)
        ]);

        return $message;
    }

    /**
     * Process loop structures in templates like {{for product in products}}
     */
    protected function processLoops(string $template, array $data): string
    {
        // Match patterns like: {{for product in products}} ... {{endfor}}
        $pattern = '/\{\{for\s+(\w+)\s+in\s+(\w+)\}\}(.*?)\{\{endfor\}\}/s';

        return preg_replace_callback($pattern, function ($matches) use ($data) {
            $itemVar = $matches[1];     // e.g., "product"
            $arrayKey = $matches[2];     // e.g., "products"
            $loopTemplate = $matches[3]; // The content inside the loop

            // Check if the array exists in data
            if (!isset($data[$arrayKey]) || !is_array($data[$arrayKey])) {
                Log::debug("Loop array not found: {$arrayKey}");
                return ''; // Remove the loop if data not found
            }

            $result = '';
            foreach ($data[$arrayKey] as $item) {
                $loopContent = $loopTemplate;

                // Replace {{product.property}} or {{product.pivot.quantity}}
                if (is_array($item)) {
                    // Handle nested properties like product.pivot.quantity
                    $loopContent = preg_replace_callback(
                        '/\{\{' . preg_quote($itemVar, '/') . '\.([a-zA-Z_][a-zA-Z0-9_\.]*)\}\}/',
                        function ($propMatch) use ($item) {
                            $propertyPath = explode('.', $propMatch[1]);
                            $value = $item;

                            // Navigate through nested properties
                            foreach ($propertyPath as $prop) {
                                if (is_array($value) && isset($value[$prop])) {
                                    $value = $value[$prop];
                                } else {
                                    return ''; // Property not found
                                }
                            }

                            // Convert value to string
                            if (is_array($value)) {
                                return json_encode($value);
                            }
                            if (is_bool($value)) {
                                return $value ? 'Yes' : 'No';
                            }
                            return (string) $value;
                        },
                        $loopContent
                    );
                }

                $result .= $loopContent;
            }

            return $result;
        }, $template);
    }





    public function generateMessage(
        string $phone,
        ?int $templateId = null,
        ?string $templateSlug = null,
        array $additionalData = []
    ): array {
        Log::info('🎯 Generating message', [
            'phone' => $phone,
            'template_id' => $templateId,
            'additional_data' => $additionalData
        ]);

        $template = $this->getTemplate($templateId, $templateSlug);

        if (!$template) {
            throw new \Exception('Template not found');
        }

        // ⭐ NEW: Use orderId if provided
        $orderId = $additionalData['order_id'] ?? null;

        // Fetch order data
        $orderData = $this->fetchOrderData($phone, $orderId);

        // Merge all data
        $clientData = array_merge($orderData, $additionalData);

        Log::info('📦 Merged data', ['data' => $clientData]);

        // Process template
        $message = $this->processTemplate($template->content, $phone, $clientData);

        return [
            'message' => $message,
            'template' => $template,
            'data_used' => $clientData
        ];
    }



    protected function fetchOrderData(string $phone, ?int $orderId = null): array
    {
        $orderData = [
            'order_no' => '',
            'order_number' => '',
            'tracking_id' => '',
            'total_price' => '',
            'delivery_date' => '',
            'status' => '',
            'order_items' => '',
            'agent_name' => '',
            'agent_phone' => '',
            'rider_name' => '',
            'rider_phone' => '',
            'vendor_name' => '',
            'zone' => '',
            'client_name' => '',
            'customer_name' => '',
            'full_name' => '',
        ];

        // ⭐ NEW: Find order by ID first (preferred)
        $order = null;

        if ($orderId) {
            $order = Order::with([
                'orderItems.product',
                'assignments.user',
                'vendor',
                'zone',
                'customer'
            ])->find($orderId);

            if ($order) {
                Log::info('✅ Found order by ID', ['order_id' => $orderId]);
            }
        }

        // ⭐ Fallback: Find most recent order by customer phone
        if (!$order) {
            $order = $this->findLatestOrderByPhone($phone);

            if ($order) {
                Log::info('✅ Found order by phone', ['phone' => $phone]);
            }
        }

        // ❌ No order found → return empty data structure
        if (!$order) {
            Log::warning('⚠️ No order found', [
                'orderId' => $orderId,
                'phone' => $phone
            ]);
            return $orderData;
        }

        // ⭐ Populate order-level fields
        $orderData['order_no'] = $order->order_no ?? '';
        $orderData['order_number'] = $order->order_number ?? $order->order_no ?? '';
        $orderData['tracking_id'] = $order->tracking_no ?? '';
        $orderData['total_price'] = $order->total_price ?? '';
        $orderData['delivery_date'] = $order->delivery_date ?? '';
        $orderData['status'] = $order->latest_status?->status?->name ?? '';
        $orderData['zone'] = $order->zone?->name ?? '';
        $orderData['vendor_name'] = $order->vendor?->name ?? '';

        // ⭐ Pull customer name from all possible sources
        $resolvedName =
            $order->customer?->full_name ??
            $order->customer?->name ??
            $order->customer?->client_name ??
            '';

        $orderData['client_name'] = $resolvedName;
        $orderData['customer_name'] = $resolvedName;
        $orderData['full_name'] = $resolvedName;

        // ⭐ Extract assigned agent & rider
        $callAgent = $order->assignments->firstWhere('role', 'CallAgent');
        $deliveryAgent = $order->assignments->firstWhere('role', 'Delivery Agent');

        $orderData['agent_name'] = $callAgent?->user?->name ?? '';
        $orderData['agent_phone'] = $callAgent?->user?->phone_number ?? '';
        $orderData['rider_name'] = $deliveryAgent?->user?->name ?? '';
        $orderData['rider_phone'] = $deliveryAgent?->user?->phone_number ?? '';

        // ⭐ Format order items reliably
        if ($order->orderItems && $order->orderItems->count() > 0) {
            $orderData['order_items'] = $order->orderItems->map(function ($item) {
                $name =
                    $item->name ??
                    $item->product?->product_name ??
                    $item->product?->name ??
                    $item->sku ??
                    'Item';

                $qty = $item->quantity ?? 1;

                return "{$qty} x {$name}";
            })->join(', ');



            // ⭐ Add products array for template loops
            $orderData['products'] = $order->orderItems->map(function ($item) {
                return [
                    'product_name' =>
                    $item->name ??
                        $item->product?->product_name ??
                        $item->product?->name ??
                        $item->sku ??
                        'Item',
                    'quantity' => $item->quantity ?? 1
                ];
            })->toArray();
        }

        Log::debug('📦 Order data prepared', [
            'order_no' => $orderData['order_no'],
            'items_count' => $order->orderItems?->count() ?? 0
        ]);

        return $orderData;
    }


    /**
     * Find latest order by phone
     */
    protected function findLatestOrderByPhone(string $phone): ?Order
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        return Order::with([
            'orderItems.product',
            'assignments.user',
            'vendor',
            'customer',
            'latest_status.status'
        ])
            ->whereHas('customer', function ($q) use ($cleanPhone) {
                $q->where(function ($subQ) use ($cleanPhone) {
                    $subQ->where('phone', 'LIKE', "%{$cleanPhone}%")
                        ->orWhere('alt_phone', 'LIKE', "%{$cleanPhone}%");
                });
            })
            ->latest()
            ->first();
    }



    /**
     * Get template by ID or slug
     */
    protected function getTemplate(?int $templateId, ?string $templateSlug)
    {
        // Try to find by ID first
        if ($templateId) {
            $template = Template::find($templateId);
            if ($template) {
                return $template;
            }
        }

        // Try to find by slug
        if ($templateSlug) {
            $template = Template::where('slug', $templateSlug)->first();
            if ($template) {
                return $template;
            }
        }

        // Fallback to default template
        return Template::where('is_default', true)->first()
            ?? Template::first();
    }


    protected function findCustomerByPhone(array $apiData, string $phone): ?array
    {
        $normalizedSearch = preg_replace('/[\s\+]/', '', $phone);

        foreach ($apiData as $customer) {
            $customerPhone = preg_replace('/[\s\+]/', '', $customer['phone'] ?? '');

            if ($customerPhone === $normalizedSearch) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * Get available placeholders for templates
     */
    public function getAvailablePlaceholders(string $templateType = 'general'): array
    {
        $placeholders = [
            'general' => [
                'customer_name',
                'client_name',
                'customer_phone',
                'customer_email'
            ],
            'order' => [
                'customer_name',
                'order_no',
                'order_number',
                'tracking_id',
                'total_price',
                'delivery_date',
                'status',
                'order_items',
                'agent_name',
                'vendor_name',
                'zone'
            ],
            'delivery' => [
                'customer_name',
                'order_no',
                'tracking_id',
                'delivery_date',
                'status',
                'agent_name',
                'zone'
            ]
        ];

        return $placeholders[$templateType] ?? $placeholders['general'];
    }
}
