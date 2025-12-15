<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Get orders by vendor ID and status
     */
    public function getOrdersByVendorAndStatus($vendorId, $statuses = ['pending', 'picked', 'delivered'])
    {
        return Order::where('vendor_id', $vendorId)
            ->whereIn('status', $statuses)
            ->get();
    }

    /**
     * Save order data from Google Sheets
     */
    // public function saveOrderData(array $orderData, int $userId, $sheet): int

    public function saveOrderData($orderData, $userId, $sheet)
    {

        $syncedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($orderData as $row) {
                // Skip invalid rows
                if (empty($row['order_no']) || empty($row['phone'])) {
                    Log::warning('Skipping invalid row', ['row' => $row]);
                    continue;
                }

                // 1️⃣ Create or update customer
                $customer = $this->createOrUpdateCustomer($row, $userId, $sheet);

                // 2️⃣ Create or find order
                $order = $this->createOrder($row, $customer, $userId, $sheet);

                // 3️⃣ Create related order products
                if (!empty($row['products'])) {
                    $this->createOrderProducts($row['products'], $order, $sheet);
                }

                // 4️⃣ Add initial status record (if new)
                if ($order->wasRecentlyCreated) {
                    $order->statusTimestamps()->create([
                        'status_id' => 1, // Example: Scheduled
                        'status_notes' => 'Imported via Google Sheet',
                    ]);
                }

                $syncedCount++;
            }

            DB::commit();
            Log::info("✅ Successfully synced {$syncedCount} orders from Google Sheet");

            return $syncedCount;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Order import failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create or update customer
     */
    private function createOrUpdateCustomer(array $data, int $userId, $sheet): Customer
    {
        return Customer::updateOrCreate(
            ['phone' => $data['phone']],
            [
                'user_id' => $userId,
                'branch_id' => $sheet->branch_id,
                'vendor_id' => $sheet->vendor_id,
                'full_name' => $data['full_name'] ?? 'Unknown',
                'email' => $data['email'] ?? null,
                'alt_phone' => $data['alt_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'zone_id' => $data['zone_id'] ?? null,
                'country_id' => $sheet->country_id,
            ]
        );
    }

    /**
     * Create or return existing order
     */
    private function createOrder(array $data, Customer $customer, int $userId, $sheet): Order
    {
        // Fast lookup for existing order
        $existingOrder = Order::where('order_no', $data['order_no'])->first();
        if ($existingOrder) {
            return $existingOrder;
        }

        return Order::create([
            'order_no'          => $data['order_no'],
            'customer_id'       => $customer->id,
            'vendor_id'         => $sheet->vendor_id,
            'country_id'        => $sheet->country_id,
            'warehouse_id'      => $sheet->warehouse_id ?? 1,
            'user_id'           => $userId,
            'platform'          => 'sheets_import',
            'source'            => 'google_sheet',
            'total_price'       => $data['cod_amount'] ?? 0,
            'sub_total'         => $data['cod_amount'] ?? 0,
            'shipping_charges'  => $data['shipping_charges'] ?? 0,
            'delivery_date'     => $data['delivery_date'] ?? null,
            'customer_notes'    => $data['special_instruction'] ?? null,
            'distance'          => $data['distance'] ?? null,
            'currency'          => $data['currency'] ?? 'KSH',
            'paid'              => false,
        ]);
    }

    /**
     * Create related order products efficiently
     */
    private function createOrderProducts(array $products, Order $order, $sheet): void
    {
        if (empty($products)) {
            return;
        }

        // Group products by name or SKU to avoid duplicates
        $grouped = collect($products)
            ->groupBy(fn($p) => $p['sku_number'] ?? strtolower(trim($p['product_name'])))
            ->map(function ($items) {
                $first = $items->first();
                $quantity = $items->sum(fn($i) => (int)($i['quantity'] ?? 1));
                $price = (float)($first['price'] ?? 0);

                return [
                    'product_name' => trim($first['product_name']),
                    'sku_number' => $first['sku_number'] ?? null,
                    'quantity' => $quantity,
                    'price' => $price,
                ];
            })
            ->values();

        $orderItems = [];

        foreach ($grouped as $p) {
            try {
                // 1️⃣ Try to find existing product by SKU first
                $product = null;

                if (!empty($p['sku_number'])) {
                    $product = Product::where('sku', $p['sku_number'])
                        ->where('vendor_id', $sheet->vendor_id)
                        ->first();
                }

                // 2️⃣ If no SKU or product not found, try by product name
                if (!$product) {
                    $product = Product::where('product_name', $p['product_name'])
                        ->where('vendor_id', $sheet->vendor_id)
                        ->first();
                }

                // 3️⃣ If still not found, create a new one with auto-generated SKU
                if (!$product) {
                    $newSku = $p['sku_number'] ?? $this->generateSku($p['product_name'], $sheet->vendor_id);

                    $product = Product::create([
                        'sku'          => $newSku,
                        'vendor_id'    => $sheet->vendor_id,
                        'product_name' => $p['product_name'],
                    ]);
                }

                // 4️⃣ Build order item
                $orderItems[] = [
                    'order_id'    => $order->id,
                    'product_id'  => $product->id,
                    'quantity'    => $p['quantity'],
                    'unit_price'  => $p['price'],
                    'total_price' => $p['quantity'] * $p['price'],
                    'currency'    => 'KSH',
                    // 'vendor_id'   => $sheet->vendor_id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            } catch (\Throwable $e) {
                Log::error('Failed to prepare order product', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($orderItems) {
            OrderItem::insert($orderItems);
            Log::info("Inserted " . count($orderItems) . " items for Order #{$order->order_no}");
        }
    }

    /**
     * 🔢 Helper: Generate a SKU based on product name + vendor
     */
    private function generateSku(string $productName, int $vendorId): string
    {
        $prefix = strtoupper(substr(preg_replace('/\s+/', '', $productName), 0, 3)); // first 3 letters
        $random = strtoupper(substr(md5(uniqid()), 0, 5)); // random 5 chars
        return "{$prefix}-{$vendorId}-{$random}";
    }
}
