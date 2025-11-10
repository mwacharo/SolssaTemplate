<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderStatusTimestamp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Order\DuplicateOrderService;
// use App\Services\Order\StockReservationService;
use App\Services\StockService;

class OrderCreationService
{
    protected $duplicateOrderService;
    protected $stockService;

    public function __construct(
        DuplicateOrderService $duplicateOrderService,
        // StockReservationService $stockService
        StockService $stockService

    ) {
        $this->duplicateOrderService = $duplicateOrderService;
        $this->stockService = $stockService;
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            // ✅ Step 1: Validate duplicates
            if ($this->duplicateOrderService->isDuplicate($data)) {
                throw new \Exception('Duplicate order detected for this client and items.');
            }

            // ✅ Step 2: Ensure products exist and stock available
            foreach ($data['order_items'] as $item) {
                $product = Product::where('sku', $item['sku'])->firstOrFail();
                if ($product->current_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for SKU {$item['sku']}");
                }
            }

            // ✅ Step 3: Create or get customer
            $customer = Customer::firstOrCreate(
                ['phone' => $data['customer']['phone']],
                $data['customer']
            );
            $data['customer_id'] = $customer->id;

            // ✅ Step 4: Create order
            $order = Order::create($data);
            Log::info('Order created', ['order_id' => $order->id]);

            // ✅ Step 5: Create items & reserve stock
            foreach ($data['order_items'] as $item) {
                $product = Product::where('sku', $item['sku'])->first();
                $item['product_id'] = $product->id;
                $orderItem = $order->orderItems()->create($item);

                $this->stockService->reserve($product, $item['quantity'], $order->id);
            }

            // ✅ Step 6: Create status timestamp
            OrderStatusTimestamp::create([
                'order_id' => $order->id,
                'status_id' => $order->status_id ?? 1,
            ]);

            DB::commit();
            return $order->load(['orderItems', 'customer']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
