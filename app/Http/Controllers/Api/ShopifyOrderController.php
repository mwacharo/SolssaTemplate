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
use App\Models\Shopify;

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
        ]);

        if ($validator->fails()) {
            Log::warning("[SHOPIFY] Validation failed for {$vendor->shopify_url}", $validator->errors()->toArray());
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        // Step 5: Save order with customer and items
        DB::beginTransaction();
        try {
            // Find or create customer
            $customer = $this->findOrCreateCustomer($normalized, $vendor);

            // Create order
            $order = Order::create([
                'order_no' => $normalized['order_no'],
                'customer_id' => $customer->id,
                'vendor_id' => $vendor->id,
                'country_id' => $vendor->country_id ?? null,
                'delivery_address' => $normalized['delivery_address'],
                'customer_notes' => $normalized['customer_notes'] ?? '',
                'source' => 'SHOPIFY',
                'platform' => 'SHOPIFY',

                // 'status' => 'pending',
                'delivery_status' => 'pending',
                'currency' => $normalized['currency'],
                'sub_total' => $normalized['sub_total'],
                'total_price' => $normalized['total_price'],
                'discount' => $normalized['discount'],
                'shipping_charges' => $normalized['shipping_charges'],
                'payment_method' => $normalized['payment_method'] ?? null,
                'paid' => $normalized['financial_status'] === 'paid',
                // Store full payload as JSON in a text/json column if available
                // 'payload' => json_encode($payload),
            ]);

            // Create order items
            foreach ($normalized['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $item['name'],
                    'sku' => $item['sku'],
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['price'] * $item['qty'],
                    'weight' => $item['weight'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    // Add any other fields your OrderItem model has
                ]);
            }

            DB::commit();

            Log::info("[SHOPIFY] Order created for {$vendor->shopify_url}", [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'customer_id' => $customer->id,
                'items_count' => count($normalized['items'])
            ]);

            return response()->json([
                'status' => 'success',
                'order_id' => $order->id,
                'order_no' => $order->order_no
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[SHOPIFY] Failed to create order for {$vendor->shopify_url}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * Find or create customer from normalized data
     */
    private function findOrCreateCustomer(array $normalized, Shopify $vendor): Customer
    {
        $customerData = [
            'name' => $normalized['customer_name'],
            'phone' => $normalized['customer_phone'] ?? null,
            'email' => $normalized['customer_email'] ?? null,
            'vendor_id' => $vendor->id,
            'country_id' => $vendor->country_id ?? null,
        ];

        // Try to find existing customer by email or phone
        $customer = Customer::where('vendor_id', $vendor->id)
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
            Log::info("[SHOPIFY] New customer created", ['customer_id' => $customer->id]);
        } else {
            // Update existing customer info if needed
            $customer->update(array_filter($customerData));
            Log::info("[SHOPIFY] Existing customer found", ['customer_id' => $customer->id]);
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
        );

        // Get customer phone (check multiple locations)
        $customerPhone = $customer['phone'] 
            ?? $shippingAddr['phone'] 
            ?? $billingAddr['phone'] 
            ?? '';

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
                'hmac' => $hmacHeader
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
                'received' => $hmacHeader,
                'calculated' => $calculatedHmac
            ]);
            return false;
        }

        return $vendor;
    }
}