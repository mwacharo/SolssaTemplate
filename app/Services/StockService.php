<?php

namespace App\Services;

use App\Models\OrderStatusTimestamp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Apply stock changes based on order status.
     */
    public function applyForStatus(OrderStatusTimestamp $statusTimestamp): void
    {
        $statusTimestamp->loadMissing(
            'status',
            'order.orderItems.product.stocks'
        );

        $order = $statusTimestamp->order;
        $status = strtolower(optional($statusTimestamp->status)->name ?? '');

        if (!$order || !$status) {
            Log::warning('StockService: Missing order or status', [
                'status_timestamp_id' => $statusTimestamp->id
            ]);
            return;
        }

        foreach ($order->orderItems as $item) {

            $stock = $item->product
                ->stocks()
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                Log::warning('StockService: No stock record found', [
                    'product_id' => $item->product_id,
                    'order_id' => $item->order_id
                ]);
                continue;
            }

            DB::transaction(function () use ($status, $stock, $item) {

                match ($status) {

                    // ✅ Reserve only when scheduled
                    'scheduled' =>
                    $stock->commitStock($item->quantity),

                    // ✅ Release if cancelled or pending
                    'cancelled', 'pending' =>
                    $stock->releaseCommittedStock($item->quantity),

                    // ✅ Final sale
                    'delivered' =>
                    $stock->markAsDelivered($item->quantity),

                    // ✅ Returned in good condition
                    'returned' =>
                    $stock->increaseStock($item->quantity),

                    default => null,
                };
            });
        }
    }
}
