<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\Merchant; // <— Add this

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

        // Step 1: Verify webhook signature dynamically per merchant
        $merchant = $this->verifyShopifySignature($request);
        if (! $merchant) {
            Log::warning('[SHOPIFY] Invalid or unknown merchant signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Step 2: Optional internal key/secret validation
        $apiKey = $request->query('key');
        $apiSecret = $request->query('secret');
        if ($apiKey !== config('services.shopify.key') || $apiSecret !== config('services.shopify.secret')) {
            Log::warning("[SHOPIFY] Unauthorized request from {$merchant->shopify_domain}");
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Step 3: Parse payload
        $payload = $request->all();
        Log::info("[SHOPIFY] Webhook payload from {$merchant->shopify_domain}", $payload);

        // Step 4: Normalize Shopify payload
        $normalized = $this->normalizeShopifyOrder($payload);

        // Step 5: Validate normalized data
        $validator = Validator::make($normalized, [
            'order_no' => 'required|string|max:100',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'address' => 'required|string|max:500',
            'items' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            Log::warning("[SHOPIFY] Validation failed for {$merchant->shopify_domain}", $validator->errors()->toArray());
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        // Step 6: Save order
        try {
            $order = Order::create([
                'order_no' => $normalized['order_no'],
                'customer_name' => $normalized['customer_name'],
                'customer_phone' => $normalized['customer_phone'] ?? '',
                'delivery_address' => $normalized['address'],
                'source' => 'SHOPIFY',
                'merchant_id' => $merchant->id,
                'status' => 'NEW',
                'payload' => json_encode($payload),
            ]);

            Log::info("[SHOPIFY] Order created for {$merchant->shopify_domain}", ['order_id' => $order->id]);

            return response()->json(['status' => 'success', 'order_id' => $order->id], 201);

        } catch (\Exception $e) {
            Log::error("[SHOPIFY] Failed to create order for {$merchant->shopify_domain}", [
                'error' => $e->getMessage()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Normalize Shopify webhook payload to your internal format
     */
    private function normalizeShopifyOrder(array $data): array
    {
        $items = collect($data['line_items'] ?? [])->map(function ($item) {
            return [
                'sku' => $item['sku'] ?? '',
                'name' => $item['name'] ?? '',
                'qty' => $item['quantity'] ?? 0,
                'price' => $item['price'] ?? 0,
            ];
        })->toArray();

        return [
            'order_no' => (string)($data['id'] ?? uniqid('SHOPIFY_')),
            'customer_name' => trim(($data['customer']['first_name'] ?? '') . ' ' . ($data['customer']['last_name'] ?? '')),
            'customer_phone' => $data['customer']['phone'] ?? '',
            'address' => $data['shipping_address']['address1'] ?? 'N/A',
            'items' => $items,
        ];
    }

    /**
     * Verify Shopify webhook HMAC dynamically per merchant.
     * Returns Merchant model if valid, otherwise false.
     */
    private function verifyShopifySignature(Request $request): Merchant|bool
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

        $merchant = Merchant::where('shopify_domain', $shopDomain)->first();
        if (! $merchant || ! $merchant->shopify_webhook_secret) {
            Log::warning("[SHOPIFY] Unknown shop: {$shopDomain}");
            return false;
        }

        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $request->getContent(), $merchant->shopify_webhook_secret, true)
        );

        if (! hash_equals($hmacHeader, $calculatedHmac)) {
            Log::error("[SHOPIFY] Signature mismatch for {$shopDomain}", [
                'received' => $hmacHeader,
                'calculated' => $calculatedHmac
            ]);
            return false;
        }

        return $merchant;
    }
}
