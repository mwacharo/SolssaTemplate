<?php

namespace App\Services;

use App\Models\Template;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Contact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MessageTemplateService
{
    /**
     * Generate personalized message from template
     *
     * @param string $phone Phone number to lookup
     * @param int|null $templateId Specific template ID (optional)
     * @param string|null $templateSlug Template slug like 'order_followup', 'delivery_reminder' (optional)
     * @param array $additionalData Extra data to merge with placeholders
     * @return array ['message' => string, 'template' => MessageTemplate, 'data' => array]
     */
    public function generateMessage(
        string $phone,
        ?int $templateId = null,
        ?string $templateSlug = null,
        array $additionalData = []
    ): array {
        // 1. Get the message template
        $template = $this->getTemplate($templateId, $templateSlug);

        if (!$template) {
            Log::warning('No template found', [
                'template_id' => $templateId,
                'template_slug' => $templateSlug
            ]);
            throw new \Exception('Message template not found');
        }

        // 2. Fetch client/customer data
        $clientData = $this->fetchClientData($phone);

        // 3. Fetch order data if available
        $orderData = $this->fetchOrderData($phone, $clientData);

        // 4. Merge all data
        $placeholderData = array_merge(
            $clientData,
            $orderData,
            $additionalData
        );

        // 5. Process template with placeholders
        $personalizedMessage = $this->processPlaceholders(
            $template->content,
            $placeholderData
        );

        Log::info('Generated personalized message', [
            'phone' => $phone,
            'template_id' => $template->id,
            'template_name' => $template->name
        ]);

        return [
            'message' => $personalizedMessage,
            'template' => $template,
            'data' => $placeholderData
        ];
    }

    /**
     * Get template by ID or slug
     */
    protected function getTemplate(?int $templateId, ?string $templateSlug): ?Template
    {
        if ($templateId) {
            return Template::where('id', $templateId)
                // ->where('is_active', true)
                ->first();
        }

        if ($templateSlug) {
            return Template::where('slug', $templateSlug)
                ->where('is_active', true)
                ->first();
        }

        // Fallback to default template
        return Template::where('is_default', true)
            // ->where('is_active', true)
            ->first();
    }

    /**
     * Fetch client data from internal database and external API
     */
    protected function fetchClientData(string $phone): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $data = [
            'customer_name' => 'Customer',
            'client_name' => 'Customer',
            'customer_phone' => $cleanPhone,
            'customer_email' => '',
        ];

        // Try internal database first
        $client = $this->findClientInDatabase($cleanPhone);

        if ($client) {
            $data['customer_name'] = $client->name ?? 'Customer';
            $data['client_name'] = $client->name ?? 'Customer';
            $data['customer_email'] = $client->email ?? '';
            $data['customer_id'] = $client->id ?? null;

            Log::debug('Found client in internal database', ['client_id' => $client->id]);
            return $data;
        }

        // Try external API if not found internally
        $externalData = $this->fetchFromExternalApi($cleanPhone);

        if ($externalData) {
            $data = array_merge($data, $externalData);
            Log::debug('Found client from external API', ['phone' => $cleanPhone]);
        } else {
            Log::debug('Client not found anywhere', ['phone' => $cleanPhone]);
        }

        return $data;
    }

    /**
     * Find client in internal database
     */
    protected function findClientInDatabase(string $phone): ?object
    {
        // Check Client model
        $client = Customer::where(function ($q) use ($phone) {
            $q->where('phone', 'LIKE', "%{$phone}%")
                ->orWhere('alt_phone', 'LIKE', "%{$phone}%");
        })->first();

        if ($client) {
            return $client;
        }

        // Check Contact model
        $contact = Contact::where(function ($q) use ($phone) {
            $q->where('phone', 'LIKE', "%{$phone}%")
                ->orWhere('alt_phone', 'LIKE', "%{$phone}%");
        })->first();

        return $contact;
    }

    /**
     * Fetch data from external Boxleo Courier API
     */
    protected function fetchFromExternalApi(string $phone): ?array
    {
        try {
            // Cache for 10 minutes to avoid repeated API calls
            $cacheKey = "external_client_{$phone}";

            return Cache::remember($cacheKey, 600, function () use ($phone) {
                $response = Http::timeout(10)
                    ->get("https://app.boxleocourier.com/api/contact-search/{$phone}");

                if ($response->successful()) {
                    $apiData = $response->json();

                    // Map API response to our placeholder format
                    // return [
                    //     'customer_name' => $apiData['name'] ?? $apiData['customer_name'] ?? 'Customer',
                    //     'client_name' => $apiData['name'] ?? $apiData['customer_name'] ?? 'Customer',
                    //     'customer_email' => $apiData['email'] ?? '',
                    //     'customer_address' => $apiData['address'] ?? '',
                    //     'external_customer_id' => $apiData['id'] ?? null,
                    // ];


                    // Since the API returns an array of customer records, you'll need to handle multiple customers
                    // or specify which customer you want to extract

                    // Option 1: Extract data from the first customer in the array
                    $customer = $apiData[0] ?? null;

                    if ($customer) {
                        return [
                            'customer_name' => $customer['name'] ?? 'Customer',
                            'client_name' => $customer['name'] ?? 'Customer',
                            'customer_email' => $customer['email'] ?? '',
                            'customer_phone' => $customer['phone'] ?? '',
                            'customer_alt_phone' => $customer['alt_phone'] ?? '',
                            'customer_address' => $customer['address'] ?? '',
                            'customer_city' => $customer['city'] ?? '',
                            'external_customer_id' => $customer['id'] ?? null,
                            'seller_id' => $customer['seller_id'] ?? null,
                        ];
                    }

                    // Option 2: If you need to find a specific customer by phone number
                    function findCustomerByPhone($apiData, $phoneNumber)
                    {
                        // Normalize phone number (remove spaces, +, etc.)
                        $normalizedSearch = preg_replace('/[\s\+]/', '', $phoneNumber);

                        foreach ($apiData as $customer) {
                            $customerPhone = preg_replace('/[\s\+]/', '', $customer['phone'] ?? '');

                            if ($customerPhone === $normalizedSearch) {
                                return [
                                    'customer_name' => $customer['name'] ?? 'Customer',
                                    'client_name' => $customer['name'] ?? 'Customer',
                                    'customer_email' => $customer['email'] ?? '',
                                    'customer_phone' => $customer['phone'] ?? '',
                                    'customer_alt_phone' => $customer['alt_phone'] ?? '',
                                    'customer_address' => $customer['address'] ?? '',
                                    'customer_city' => $customer['city'] ?? '',
                                    'external_customer_id' => $customer['id'] ?? null,
                                    'seller_id' => $customer['seller_id'] ?? null,
                                ];
                            }
                        }

                        return null; // Customer not found
                    }

                    // Option 3: Extract all customers
                    function extractAllCustomers($apiData)
                    {
                        $customers = [];

                        foreach ($apiData as $customer) {
                            $customers[] = [
                                'customer_name' => $customer['name'] ?? 'Customer',
                                'client_name' => $customer['name'] ?? 'Customer',
                                'customer_email' => $customer['email'] ?? '',
                                'customer_phone' => $customer['phone'] ?? '',
                                'customer_alt_phone' => $customer['alt_phone'] ?? '',
                                'customer_address' => $customer['address'] ?? '',
                                'customer_city' => $customer['city'] ?? '',
                                'external_customer_id' => $customer['id'] ?? null,
                                'seller_id' => $customer['seller_id'] ?? null,
                                'has_sales' => !empty($customer['sales']),
                                'sales_count' => count($customer['sales'] ?? []),
                            ];
                        }

                        return $customers;
                    }
                }

                Log::warning('External API call failed', [
                    'phone' => $phone,
                    'status' => $response->status()
                ]);

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
 * Fetch order data from external API
 */
protected function fetchExternalOrderData(string $phone): ?array
{
    try {
        // Call your external API (adjust URL and method as needed)
        $response = Http::get('https://your-api-endpoint.com/customers', [
            'phone' => $phone
        ]);

        if (!$response->successful()) {
            Log::warning('External API request failed', [
                'phone' => $phone,
                'status' => $response->status()
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
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * Find customer with sales data from API response
 */
protected function findCustomerWithSales(array $apiData, string $phone): ?array
{
    $normalizedSearch = preg_replace('/[\s\+]/', '', $phone);

    foreach ($apiData as $customer) {
        $customerPhone = preg_replace('/[\s\+]/', '', $customer['phone'] ?? '');
        
        // Check if phone matches and customer has sales
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

    // Sort by created_at or updated_at descending
    usort($sales, function ($a, $b) {
        $dateA = strtotime($a['created_at'] ?? $a['updated_at'] ?? '1970-01-01');
        $dateB = strtotime($b['created_at'] ?? $b['updated_at'] ?? '1970-01-01');
        return $dateB - $dateA;
    });

    return $sales[0];
}

/**
 * Map external sale data to order data structure
 */
protected function mapExternalSaleToOrderData(array $sale, array $customer): array
{
    $orderData = [
        'order_no' => $sale['order_no'] ?? '',
        'order_number' => $sale['order_no'] ?? '',
        'tracking_id' => $sale['tracking_no'] ?? '',
        'total_price' => $sale['total_price'] ?? '',
        'delivery_date' => $sale['delivery_date'] ?? '',
        'status' => $sale['status'] ?? '',
        'delivery_status' => $sale['delivery_status'] ?? '',
        'agent_name' => '', // Not available in API response
        'vendor_name' => '', // Could map from seller_id if you have a lookup
        'website_url' => '',
        'zone' => '', // Could map from zone_id if you have a lookup
        'order_id' => $sale['id'] ?? null,
        'external_order_id' => $sale['id'] ?? null,
        'customer_name' => $customer['name'] ?? '',
        'customer_city' => $customer['city'] ?? '',
        'customer_address' => $customer['address'] ?? '',
    ];

    // Format order items from products
    if (!empty($sale['products']) && is_array($sale['products'])) {
        $itemsList = [];
        foreach ($sale['products'] as $product) {
            $name = $product['product_name'] ?? '';
            $qty = $product['pivot']['quantity'] ?? 1;
            $itemsList[] = "{$qty} x {$name}";
        }
        $orderData['order_items'] = implode(', ', $itemsList);
    }

    Log::debug('Mapped external order data', [
        'order_no' => $orderData['order_no'],
        'customer_name' => $orderData['customer_name']
    ]);

    return $orderData;
}

    /**
     * Find latest order for client
     */
    protected function findLatestOrder(string $phone, ?int $clientId): ?Order
    {
        $query = Order::with('orderItems');

        if ($clientId) {
            // First try with client_id
            $order = $query->where('customer_id', $clientId)
                ->latest()
                ->first();

            if ($order) {
                return $order;
            }
        }

        // Try finding by phone number through client relationship
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        return Order::with('orderItems')
            ->whereHas('customer', function ($q) use ($cleanPhone) {
                $q->where(function ($subQ) use ($cleanPhone) {
                    $subQ->where('phone_number', 'LIKE', "%{$cleanPhone}%")
                        ->orWhere('alt_phone_number', 'LIKE', "%{$cleanPhone}%");
                });
            })
            ->latest()
            ->first();
    }

  
   
protected function processPlaceholders(string $template, array $data): string
{
    Log::info('🧩 [processPlaceholders] Starting placeholder processing', [
        'template' => $template,
        'data_keys' => array_keys($data),
    ]);

    $message = $template;

    foreach ($data as $key => $value) {
        // Log the raw key-value pair
        Log::debug("Processing placeholder: {{$key}}", [
            'key' => $key,
            'value_type' => gettype($value),
            'value' => $value,
        ]);

        // Handle null values
        if (is_null($value)) {
            Log::debug("Placeholder {{$key}} is null — replaced with empty string");
            $value = '';
        }

        // Convert non-string values
        if (!is_string($value)) {
            $value = (string) $value;
            Log::debug("Converted {{$key}} to string", ['converted_value' => $value]);
        }

        // Prepare regex patterns
        $patterns = [
            '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
            '/\{\{' . preg_quote($key, '/') . '\}\}/'
        ];

        // Replace all matching patterns
        foreach ($patterns as $pattern) {
            $newMessage = preg_replace($pattern, $value, $message);
            if ($newMessage !== $message) {
                Log::debug("Replaced {{$key}} in message", [
                    'pattern' => $pattern,
                    'replacement' => $value,
                ]);
                $message = $newMessage;
            }
        }
    }

    // Remove any leftover placeholders
    $messageBeforeClean = $message;
    $message = preg_replace('/\{\{[^}]*\}\}/', '', $message);

    if ($message !== $messageBeforeClean) {
        Log::debug('Removed unmatched placeholders');
    }

    // Clean up extra spaces
    $cleanedMessage = preg_replace('/\s+/', ' ', trim($message));

    if ($cleanedMessage !== $message) {
        Log::debug('Cleaned up extra whitespace');
    }

    $message = $cleanedMessage;

    Log::info('✅ [processPlaceholders] Finished processing template', [
        'final_message' => $message,
    ]);

    return $message;
}



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
