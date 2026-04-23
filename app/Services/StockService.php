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
        $status = optional($statusTimestamp->status)->name ?? '';

        if (!$order || !$status) {
            Log::warning('StockService: Missing order or status', [
                'status_timestamp_id' => $statusTimestamp->id
            ]);
            return;
        }

        // ─────────────────────────────
        // RESOLVE PREVIOUS STATUS
        // ─────────────────────────────
        $previousStatus = $order->statusTimestamps()
            ->where('id', '<', $statusTimestamp->id)
            ->latest('id')
            ->value('status_id');

        $previousStatusName = $previousStatus
            ? \App\Models\Status::find($previousStatus)?->name
            : null;

        // ─────────────────────────────
        // WRAP ENTIRE LOOP IN ONE TRANSACTION
        // ─────────────────────────────
        DB::transaction(function () use ($status, $order, $statusTimestamp, $previousStatusName) {

            foreach ($order->orderItems as $item) {

                // ─────────────────────────────
                // SCOPE STOCK TO WAREHOUSE + LOCK
                // ─────────────────────────────
                $stock = $item->product
                    ->stocks()
                    ->where('warehouse_id', $order->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    Log::warning('StockService: No stock record found', [
                        'product_id'   => $item->product_id,
                        'order_id'     => $item->order_id,
                        'warehouse_id' => $order->warehouse_id,
                    ]);
                    continue;
                }

                match ($status) {

                    // Reserve stock when scheduled
                    'Scheduled' =>
                    $stock->commitStock($item->quantity),

                    // Only release if stock was actually committed (prev status was Scheduled)
                    'Cancelled', 'Pending' =>
                    $previousStatusName === 'Scheduled'
                        ? $stock->releaseCommittedStock($item->quantity)
                        : null,

                    // Final sale — use delivered_quantity for partial deliveries
                    'Delivered' =>
                    $stock->markAsDelivered($item->quantity),

                    // Stock returned in good condition
                    'Return' =>
                    $stock->markAsReturned($item->quantity),


                    // Undispatched orders release committed stock
                    // item returned to warehouse and made available for other orders
                    'Undispatched' =>
                    $stock->releaseCommittedStock($item->quantity),

                    // Rescheduled  no changes on stock 


                    default => null,
                };
            }
        });
    }
}
