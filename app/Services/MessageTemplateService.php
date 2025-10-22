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

        // Merge client data with order data
        $data = array_merge($clientData, $this->fetchOrderData($phone, $clientData));

        Log::info('📦 Merged data for placeholders', ['data' => $data]);

        return $this->processPlaceholders($template, $data);
    }

    /**
     * Fetch order data (searches local DB first, then external API)
     */
    protected function fetchOrderData(string $phone, array $clientData): array
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

            // Search external API
            $externalOrderData = $this->fetchExternalOrderData($phone);

            if ($externalOrderData) {
                Log::info('✅ External order data found', ['externalOrderData' => $externalOrderData]);
                return $externalOrderData;
            }

            return $orderData;
        }

        // Populate order data from local database
        $orderData['order_no'] = $order->order_no ?? $order->no ?? '';
        $orderData['order_number'] = $order->order_number ?? $order->number ?? '';
        $orderData['tracking_id'] = $order->tracking_id ?? $order->tracking_number ?? '';
        $orderData['total_price'] = $order->total_price ?? '';
        $orderData['delivery_date'] = $order->delivery_date ?? '';
        $orderData['status'] = $order->status ?? '';
        $orderData['agent_name'] = $order->agent_name ?? '';
        $orderData['vendor_name'] = $order->vendor_name ?? '';
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
     * Fetch order data from external Boxleo API
     */
    protected function fetchExternalOrderData(string $phone): ?array
    {
        try {
            Log::info('🌐 Calling Boxleo API', ['phone' => $phone]);

            // Call external API
            $response = Http::timeout(120)
                ->retry(3, 2000)
                ->get("https://app.boxleocourier.com/api/contact-search/{$phone}");

            if (!$response->successful()) {
                Log::warning("🚫 Boxleo API call not successful for {$phone}. Status: " . $response->status(), [
                    'body' => $response->body()
                ]);
                return null;
            }

            $apiData = $response->json();

            if (empty($apiData) || !is_array($apiData)) {
                Log::debug('No data returned from external API', ['phone' => $phone]);
                return null;
            }

            // Find customer with sales data
            $customerWithSales = $this->findCustomerWithSales($apiData, $phone);

            if (!$customerWithSales) {
                Log::debug('No customer with sales found in external API', ['phone' => $phone]);
                return null;
            }

            // Get the most recent sale
            $latestSale = $this->getLatestSale($customerWithSales['sales']);

            if (!$latestSale) {
                Log::debug('No sales found for customer', [
                    'phone' => $phone,
                    'customer_id' => $customerWithSales['id']
                ]);
                return null;
            }

            // Map external API data to your order structure
            return $this->mapExternalSaleToOrderData($latestSale, $customerWithSales);
        } catch (\Exception $e) {
            Log::error('Error fetching external order data', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Map external sale data to internal order structure
     */
    protected function mapExternalSaleToOrderData(array $sale, array $customer): array
    {
        // Format order items from products array
        $orderItemsList = [];
        $productsDetailed = [];

        foreach ($sale['products'] ?? [] as $product) {
            $pivot = $product['pivot'] ?? [];
            $quantity = $pivot['quantity'] ?? 1;
            $productName = $product['product_name'] ?? 'Unknown Product';

            // Format: "1 x Portable And Powerful Electric Saw"
            $orderItemsList[] = "{$quantity} x {$productName}";

            // Store detailed product info
            $productsDetailed[] = [
                'id' => $product['id'],
                'product_name' => $productName,
                'sku_no' => $product['sku_no'] ?? $pivot['sku_no'] ?? '',
                'bar_code' => $product['bar_code'] ?? null,
                'quantity' => $quantity,
                'price' => (float) ($pivot['price'] ?? 0),
                'total_price' => (float) ($pivot['total_price'] ?? 0),
                'quantity_sent' => $pivot['quantity_sent'] ?? 0,
                'quantity_delivered' => $pivot['quantity_delivered'] ?? 0,
                'quantity_returned' => $pivot['quantity_returned'] ?? 0,
                'quantity_remaining' => $pivot['quantity_remaining'] ?? 0,
                'quantity_tobe_delivered' => $pivot['quantity_tobe_delivered'] ?? 0,
                'delivered' => (bool) ($pivot['delivered'] ?? false),
                'sent' => (bool) ($pivot['sent'] ?? false),
            ];
        }

        // Format agent name
        $agentName = '';
        if (!empty($sale['agent_id'])) {
            $agentName = "Agent #{$sale['agent_id']}";
        }
        if (!empty($sale['rider_id'])) {
            $riderText = "Rider #{$sale['rider_id']}";
            $agentName = $agentName ? "{$agentName} / {$riderText}" : $riderText;
        }

        // Format vendor name
        $vendorName = '';
        if (!empty($sale['seller_id'])) {
            $vendorName = "Seller #{$sale['seller_id']}";
        }

        // Format zone
        $zone = '';
        if (!empty($sale['zone_id'])) {
            $zone = "Zone #{$sale['zone_id']}";
        }

        return [
            // Core fields matching your $orderData array
            'order_no' => $sale['order_no'] ?? '',
            'order_number' => $sale['order_no'] ?? '',
            'tracking_id' => $sale['tracking_no'] ?? $sale['waybill_no'] ?? '',
            'total_price' => $sale['total_price'] ?? '',
            'delivery_date' => $sale['delivery_date'] ?? '',
            'status' => $sale['status'] ?? '',
            'order_items' => implode(', ', $orderItemsList),
            'agent_name' => $agentName,
            'vendor_name' => $vendorName,
            'website_url' => '',
            'zone' => $zone,

            // Additional fields
            'order_id' => $sale['id'],
            'external_order_id' => $sale['id'],
            'customer_name' => $customer['name'] ?? '',
            'client_name' => $customer['name'] ?? '',
            'customer_phone' => $customer['phone'] ?? '',
            'customer_email' => $customer['email'] ?? '',
            'customer_address' => $customer['address'] ?? '',
            'customer_city' => $customer['city'] ?? '',
            'delivery_status' => $sale['delivery_status'] ?? '',
            'payment_method' => $sale['payment_method'] ?? '',
            'products' => $productsDetailed,
            'data_source' => 'external_api',
        ];
    }

    /**
     * Find customer with sales data from API response
     */
    protected function findCustomerWithSales(array $apiData, string $phone): ?array
    {
        $normalizedSearch = preg_replace('/[\s\+]/', '', $phone);

        foreach ($apiData as $customer) {
            $customerPhone = preg_replace('/[\s\+]/', '', $customer['phone'] ?? '');

            if ($customerPhone === $normalizedSearch && !empty($customer['sales'])) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * Get the most recent sale from sales array
     */
    protected function getLatestSale(array $sales): ?array
    {
        if (empty($sales)) {
            return null;
        }

        usort($sales, function ($a, $b) {
            $dateA = strtotime($a['created_at'] ?? $a['updated_at'] ?? '1970-01-01');
            $dateB = strtotime($b['created_at'] ?? $b['updated_at'] ?? '1970-01-01');
            return $dateB - $dateA;
        });

        return $sales[0];
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

    /**
     * Generate personalized message using template
     * 
     * @param string $phone Customer phone number
     * @param int|null $templateId Specific template ID
     * @param string|null $templateSlug Template slug (e.g., 'order_followup')
     * @param array $additionalData Extra data to merge with fetched data
     * @return array ['message' => string, 'template' => MessageTemplate]
     */
    public function generateMessage(
        string $phone,
        ?int $templateId = null,
        ?string $templateSlug = null,
        array $additionalData = []
    ): array {
        Log::info('🎯 Generating message', [
            'phone' => $phone,
            'template_id' => $templateId,
            'template_slug' => $templateSlug,
            'additional_data' => $additionalData
        ]);

        // Fetch the template
        $template = $this->getTemplate($templateId, $templateSlug);

        if (!$template) {
            throw new \Exception('Template not found');
        }

        Log::info('📄 Template loaded', [
            'template_name' => $template->name,
            'template_content' => $template->content
        ]);

        // Fetch customer data from external API
        $clientData = $this->fetchFromExternalApi($phone);

        // Merge with additional data
        $clientData = array_merge($clientData ?? [], $additionalData);

        // Process the template with all data
        $message = $this->processTemplate($template->content, $phone, $clientData);

        Log::info('✅ Message generated successfully', [
            'template_used' => $template->name,
            'message_length' => strlen($message)
        ]);

        return [
            'message' => $message,
            'template' => $template,
            'data_used' => $clientData
        ];
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

    /**
     * Fetch customer data from external Boxleo API
     */
    protected function fetchFromExternalApi(string $phone): ?array
    {
        try {
            // Cache for 10 minutes to avoid repeated API calls
            $cacheKey = "external_client_{$phone}";

            return Cache::remember($cacheKey, 600, function () use ($phone) {
                Log::info('🌐 Fetching customer from Boxleo API', ['phone' => $phone]);

                $response = Http::timeout(10)
                    ->get("https://app.boxleocourier.com/api/contact-search/{$phone}");

                if (!$response->successful()) {
                    Log::warning('External API request failed', [
                        'phone' => $phone,
                        'status' => $response->status()
                    ]);
                    return null;
                }

                $apiData = $response->json();

                if (empty($apiData) || !is_array($apiData)) {
                    Log::debug('No customer data returned from external API', ['phone' => $phone]);
                    return null;
                }

                // Find customer by phone
                $customer = $this->findCustomerByPhone($apiData, $phone);

                if ($customer) {
                    Log::info('✅ Customer found in external API', [
                        'customer_id' => $customer['id'] ?? null,
                        'customer_name' => $customer['name'] ?? null
                    ]);

                    return [
                        'customer_name' => $customer['name'] ?? 'Customer',
                        'client_name' => $customer['name'] ?? 'Customer',
                        'customer_email' => $customer['email'] ?? '',
                        'customer_phone' => $customer['phone'] ?? '',
                        'customer_alt_phone' => $customer['alt_phone'] ?? '',
                        'customer_address' => $customer['address'] ?? '',
                        'customer_city' => $customer['city'] ?? '',
                        'customer_id' => $customer['id'] ?? null,
                        'external_customer_id' => $customer['id'] ?? null,
                        'seller_id' => $customer['seller_id'] ?? null,
                    ];
                }

                return null;
            });
        } catch (\Exception $e) {
            Log::error('Error fetching from external API', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find customer by phone number in API data
     */
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