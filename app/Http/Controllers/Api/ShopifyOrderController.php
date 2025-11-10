<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\OrderStatusTimestamp;
use App\Models\Shopify;
use App\Models\Product;
use Illuminate\Support\Facades\Http;


class ShopifyOrderController extends Controller
{
    /**
     * Handle Shopify Order Creation Webhook
     */
    public function handleWebhook(Request $request)
    {
        Log::info('[SHOPIFY] Incoming webhook', [
            'headers' => $request->headers->all(),
            'query' => $request->query(),
        ]);

        // Step 1: Verify webhook signature dynamically per vendor
        $vendor = $this->verifyShopifySignature($request);
        // Log vendor info (avoid logging sensitive fields like shopify_secret)
        Log::info('[SHOPIFY] Vendor details', [
            'vendor_id' => $vendor->vendor_id ?? $vendor->id ?? null,
            'shopify_url' => $vendor->shopify_url ?? null,
            'country_id' => $vendor->country_id ?? null,
            'created_at' => isset($vendor->created_at) ? $vendor->created_at->toDateTimeString() : null,
        ]);
        if (! $vendor) {
            Log::warning('[SHOPIFY] Invalid or unknown vendor signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }


        // Step 2: Parse payload
        $payload = $request->all();
        Log::info("[SHOPIFY] Webhook payload from {$vendor->shopify_url}", $payload);

        // Step 3: Normalize Shopify payload
        $normalized = $this->normalizeShopifyOrder($payload);

        // Step 4: Validate normalized data
        $validator = Validator::make($normalized, [
            'order_no' => 'required|string|max:100',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:20',
            'delivery_address' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'sub_total' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'shopify_id' => 'required', // Required for duplicate checking
        ]);

        if ($validator->fails()) {
            Log::warning("[SHOPIFY] Validation failed for {$vendor->shopify_url}", $validator->errors()->toArray());
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        // Step 5: Check for duplicate orders (idempotency)
        $existingOrder = Order::where('vendor_id', $vendor->vendor_id)
            ->where('order_no', $normalized['order_no'])
            ->first();

        if ($existingOrder) {
            Log::info("[SHOPIFY] Duplicate order detected", [
                'order_id' => $existingOrder->id,
                'order_no' => $normalized['order_no'],
                'shopify_id' => $normalized['shopify_id']
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Order already exists',
                'order_id' => $existingOrder->id,
                'order_no' => $existingOrder->order_no
            ], 200);
        }

        // Step 6: Save order with customer and items
        DB::beginTransaction();
        try {
            // Find or create customer
            $customer = $this->findOrCreateCustomer($normalized, $vendor);

            // Determine paid status
            $isPaid = in_array($normalized['financial_status'], ['paid', 'partially_paid']);

            // Create order
            $order = Order::create([
                'order_no' => $normalized['order_no'],
                'customer_id' => $customer->id,
                'vendor_id' => $vendor->vendor_id, // FIXED: was $vendor->id
                'country_id' => $vendor->country_id ?? 1, // Default to Kenya
                'delivery_address' => $normalized['delivery_address'],
                'customer_notes' => $normalized['customer_notes'] ?? '',
                'source' => 'SHOPIFY',
                'platform' => 'SHOPIFY',
                'currency' => $normalized['currency'],
                'sub_total' => $normalized['sub_total'],
                'total_price' => $normalized['total_price'],
                'shipping_charges' => $normalized['shipping_charges'],
                'paid' => $isPaid,
                'amount_paid' => $isPaid ? $normalized['total_price'] : 0,
                // Store Shopify metadata as JSON if you have a metadata/payload column
                // 'metadata' => json_encode([
                //     'shopify_id' => $normalized['shopify_id'],
                //     'shopify_order_number' => $normalized['shopify_order_number'],
                //     'financial_status' => $normalized['financial_status'],
                //     'payment_method' => $normalized['payment_method'],
                //     'created_at_shopify' => $normalized['created_at_shopify'],
                // ]),
            ]);

            // Create order items with product validation
            $createdItems = 0;
            $skippedItems = [];

            foreach ($normalized['items'] as $item) {
                // Try to find product by SKU
                $product = null;
                if (!empty($item['sku'])) {
                    $product = Product::where('sku', $item['sku'])
                        ->where('vendor_id', $vendor->vendor_id)
                        ->first();
                }

                // If product not found, log warning but continue
                if (!$product && !empty($item['sku'])) {
                    Log::warning("[SHOPIFY] Product not found for SKU", [
                        'sku' => $item['sku'],
                        'order_id' => $order->id,
                        'item_name' => $item['name']
                    ]);
                    $skippedItems[] = [
                        'sku' => $item['sku'],
                        'name' => $item['name'],
                        'reason' => 'Product not found in database'
                    ];
                }

                // Create order item (with or without product_id)
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product ? $product->id : null,
                    'sku' => $item['sku'] ?? '',
                    'name' => $item['name'], // ADDED: Include product name
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['price'] * $item['qty'],
                    'weight' => $item['weight'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'currency' => $normalized['currency'],
                ]);

                $createdItems++;
            }

            // Create initial status timestamp
            OrderStatusTimestamp::create([
                'order_id' => $order->id,
                'status_id' => 1, // Default: Pending (adjust based on your status table)
                'created_at' => now()
            ]);

            DB::commit();

            Log::info("[SHOPIFY] Order created successfully for {$vendor->shopify_url}", [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'customer_id' => $customer->id,
                'items_created' => $createdItems,
                'items_skipped' => count($skippedItems),
                'paid' => $isPaid
            ]);

            $response = [
                'status' => 'success',
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'items_created' => $createdItems
            ];

            if (!empty($skippedItems)) {
                $response['warnings'] = [
                    'message' => 'Some items were created without product linking',
                    'skipped_items' => $skippedItems
                ];
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[SHOPIFY] Failed to create order for {$vendor->shopify_url}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to process order'
            ], 500);
        }
    }

    /**
     * Find or create customer from normalized data
     */
    private function findOrCreateCustomer(array $normalized, Shopify $vendor): Customer
    {
        $customerData = [
            'full_name' => $normalized['customer_name'] ?? null, // FIXED: removed leading space
            'phone' => $normalized['customer_phone'] ?? null,
            'email' => $normalized['customer_email'] ?? null,
            'vendor_id' => $vendor->vendor_id, // FIXED: was $vendor->id
            'country_id' => $vendor->country_id ?? 1, // Default to Kenya
        ];

        // Remove empty values for cleaner data
        $customerData = array_filter($customerData, function($value) {
            return !is_null($value) && $value !== '';
        });

        // Ensure we have at least one contact method
        if (empty($customerData['email']) && empty($customerData['phone'])) {
            Log::warning("[SHOPIFY] Customer has no email or phone", [
                'customer_name' => $customerData['full_name'] ?? 'Unknown'
            ]);
        }

        // Try to find existing customer by email or phone
        $customer = Customer::where('vendor_id', $vendor->vendor_id)
            ->where(function($query) use ($customerData) {
                if (!empty($customerData['email'])) {
                    $query->where('email', $customerData['email']);
                }
                if (!empty($customerData['phone'])) {
                    $query->orWhere('phone', $customerData['phone']);
                }
            })
            ->first();

        // Create new customer if not found
        if (!$customer) {
            $customer = Customer::create($customerData);
            Log::info("[SHOPIFY] New customer created", [
                'customer_id' => $customer->id,
                'name' => $customer->full_name,
                'vendor_id' => $vendor->vendor_id
            ]);
        } else {
            // Update existing customer info if data has changed
            $updated = $customer->update($customerData);
            if ($updated) {
                Log::info("[SHOPIFY] Existing customer updated", [
                    'customer_id' => $customer->id,
                    'changes' => $customerData
                ]);
            } else {
                Log::info("[SHOPIFY] Existing customer found (no updates needed)", [
                    'customer_id' => $customer->id
                ]);
            }
        }

        return $customer;
    }

    /**
     * Normalize Shopify webhook payload to your internal format
     */
    private function normalizeShopifyOrder(array $data): array
    {
        // Extract line items with enhanced details
        $items = collect($data['line_items'] ?? [])->map(function ($item) {
            return [
                'sku' => $item['sku'] ?? '',
                'name' => $item['name'] ?? $item['title'] ?? 'Unnamed Item',
                'qty' => $item['quantity'] ?? 0,
                'price' => (float)($item['price'] ?? 0),
                'weight' => $item['grams'] ?? 0,
                'discount' => (float)($item['total_discount'] ?? 0),
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'is_physical' => $item['requires_shipping'] ?? true,
                'is_gift_card' => $item['gift_card'] ?? false,
            ];
        })->toArray();

        // Prioritize shipping address for delivery info
        $shippingAddr = $data['shipping_address'] ?? [];
        $billingAddr = $data['billing_address'] ?? [];
        $customer = $data['customer'] ?? [];

        // Build delivery address
        $deliveryAddress = implode(', ', array_filter([
            $shippingAddr['address1'] ?? '',
            $shippingAddr['address2'] ?? '',
            $shippingAddr['city'] ?? '',
            $shippingAddr['province'] ?? '',
            $shippingAddr['zip'] ?? '',
            $shippingAddr['country'] ?? '',
        ])) ?: 'N/A';

        // Get customer name (prioritize shipping recipient)
        $customerName = trim(
            ($shippingAddr['first_name'] ?? $customer['first_name'] ?? '') . ' ' . 
            ($shippingAddr['last_name'] ?? $customer['last_name'] ?? '')
        ) ?: 'Unknown Customer';

        // Get customer phone (check multiple locations)
        $customerPhone = $customer['phone'] 
            ?? $shippingAddr['phone'] 
            ?? $billingAddr['phone'] 
            ?? '';

        // Clean phone number (remove spaces, dashes)
        if ($customerPhone) {
            $customerPhone = preg_replace('/[^0-9+]/', '', $customerPhone);
        }

        // Extract payment method
        $paymentMethod = !empty($data['payment_gateway_names']) 
            ? $data['payment_gateway_names'][0] 
            : null;

        return [
            'order_no' => (string)($data['order_number'] ?? $data['name'] ?? $data['id']),
            'customer_name' => $customerName,
            'customer_email' => $data['email'] ?? $customer['email'] ?? null,
            'customer_phone' => $customerPhone,
            'delivery_address' => $deliveryAddress,
            'customer_notes' => $data['note'] ?? '',
            'items' => $items,
            
            // Financial details
            'sub_total' => (float)($data['current_subtotal_price'] ?? $data['subtotal_price'] ?? 0),
            'total_price' => (float)($data['current_total_price'] ?? $data['total_price'] ?? 0),
            'discount' => (float)($data['total_discounts'] ?? 0),
            'shipping_charges' => (float)($data['total_shipping_price_set']['shop_money']['amount'] ?? 0),
            'currency' => $data['currency'] ?? 'KES',
            
            // Payment info
            'financial_status' => $data['financial_status'] ?? 'pending',
            'payment_method' => $paymentMethod,
            
            // Additional metadata
            'shopify_id' => $data['id'] ?? null,
            'shopify_order_number' => $data['order_number'] ?? null,
            'created_at_shopify' => $data['created_at'] ?? null,
        ];
    }

    /**
     * Verify Shopify webhook HMAC dynamically per vendor.
     * Returns vendor model if valid, otherwise false.
     */
    private function verifyShopifySignature(Request $request): Shopify|bool
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');

        if (! $shopDomain || ! $hmacHeader) {
            Log::warning('[SHOPIFY] Missing required headers', [
                'shop' => $shopDomain,
                'hmac' => $hmacHeader ? 'present' : 'missing'
            ]);
            return false;
        }

        // Search for vendor with multiple URL formats
        $vendor = Shopify::where(function($query) use ($shopDomain) {
            $query->where('shopify_url', $shopDomain)
                  ->orWhere('shopify_url', 'https://' . $shopDomain)
                  ->orWhere('shopify_url', 'http://' . $shopDomain);
        })->first();

        if (! $vendor || ! $vendor->shopify_secret) {
            Log::warning("[SHOPIFY] Unknown shop: {$shopDomain}");
            return false;
        }

        // Verify HMAC using shopify_secret
        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $request->getContent(), $vendor->shopify_secret, true)
        );

        if (! hash_equals($hmacHeader, $calculatedHmac)) {
            Log::error("[SHOPIFY] Signature mismatch for {$shopDomain}", [
                'received' => substr($hmacHeader, 0, 20) . '...', // Don't log full HMAC
                'calculated' => substr($calculatedHmac, 0, 20) . '...'
            ]);
            return false;
        }

        Log::info("[SHOPIFY] Vendor verified successfully", [
            'vendor_id' => $vendor->vendor_id,
            'shop_domain' => $shopDomain
        ]);

        return $vendor;
    }



    public function update(Request $request)
    {
        $shopifyOrderId = $request->input('shopify_order_id');
        $trackingNumber = $request->input('tracking_number');
        $trackingUrl = $request->input('tracking_url');
        $courierName = $request->input('courier_name', 'Boxleo Courier');

        // 1️⃣ Build fulfillment payload
        $fulfillmentData = [
            'fulfillment' => [
                'tracking_number' => $trackingNumber,
                'tracking_company' => $courierName,
                'tracking_url' => $trackingUrl,
                'notify_customer' => true,
            ],
        ];

        // 2️⃣ Send fulfillment to Shopify
        $response = Http::withBasicAuth(env('SHOPIFY_API_KEY'), env('SHOPIFY_API_PASSWORD'))
            ->post("https://{your-store-name}.myshopify.com/admin/api/2025-01/orders/{$shopifyOrderId}/fulfillments.json", $fulfillmentData);

        Log::info('Shopify Fulfillment Update', [
            'order_id' => $shopifyOrderId,
            'response' => $response->json(),
        ]);

        return response()->json(['success' => true]);
    }
}