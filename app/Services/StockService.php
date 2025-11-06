<?php

namespace App\Services;

use App\Models\Product;
use App\Models\OrderItem;
use App\Models\HistoricalStockMovement;
use App\Models\OrderStatusTimestamp;
use Illuminate\Support\Facades\DB;
use App\StockMovementType;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Apply stock changes for a given status event.
     */
    public function applyForStatus(OrderStatusTimestamp $statusTimestamp): void
    {
        $statusTimestamp->loadMissing('status', 'order.orderItems.product');

        $order = $statusTimestamp->order;
        $statusName = strtolower(optional($statusTimestamp->status)->name ?? '');

        if (!$order || !$statusName) {
            Log::warning('StockService::applyForStatus - missing order or status', [
                'status_timestamp_id' => $statusTimestamp->id,
            ]);
            return;
        }

        $previous = $this->previousStatusTimestamp($statusTimestamp);

        if (
            $previous &&
            $this->shouldReverseTransition(
                optional($previous->status)->name,
                optional($statusTimestamp->status)->name
            )
        ) {
            $this->reverseMovementsForStatusTimestamp($previous, $statusTimestamp);
        }

        foreach ($order->orderItems as $item) {
            match ($statusName) {
                'new', 'received' => $this->reserveStock($item, $statusTimestamp->id),
                'pending', 'cancelled' => $this->revertStock($item, $statusTimestamp->id),
                'delivered' => $this->deductStock($item, $statusTimestamp->id),
                'returned' => $this->returnStock($item, $statusTimestamp->id),
                default => null,
            };
        }
    }

    protected function previousStatusTimestamp(OrderStatusTimestamp $statusTimestamp): ?OrderStatusTimestamp
    {
        return $statusTimestamp->order
            ->statusTimestamps()
            ->where('id', '<', $statusTimestamp->id)
            ->latest('id')
            ->first();
    }

    protected function shouldReverseTransition(?string $from, ?string $to): bool
    {
        $from = strtolower((string) $from);
        $to = strtolower((string) $to);

        return $from === 'received' && in_array($to, ['cancelled', 'pending']);
    }

    public function reverseMovementsForStatusTimestamp(OrderStatusTimestamp $previous, OrderStatusTimestamp $new): void
    {
        DB::transaction(function () use ($previous, $new) {
            $movements = HistoricalStockMovement::where('status_timestamp_id', $previous->id)->get();

            foreach ($movements as $movement) {
                $product = Product::lockForUpdate()->findOrFail($movement->product_id);
                $qty = $movement->quantity;
                $oppositeType = $this->oppositeMovementType($movement->movement_type);

                $exists = HistoricalStockMovement::where([
                    'product_id' => $movement->product_id,
                    'order_id' => $movement->order_id,
                    'status_timestamp_id' => $new->id,
                    'movement_type' => $oppositeType,
                ])->exists();

                if ($exists) {
                    continue;
                }

                $before = [
                    'current' => $product->current_stock,
                    'committed' => $product->committed_stock,
                    'defected' => $product->defected_stock,
                ];

                match ($oppositeType) {
                    StockMovementType::RETURNED->value => $product->increment('current_stock', $qty),
                    StockMovementType::RESERVED->value => $product->decrement('current_stock', $qty) && $product->increment('committed_stock', $qty),
                    StockMovementType::DEDUCTED->value => $product->decrement('committed_stock', $qty),
                    StockMovementType::DEFECTED->value => $product->increment('defected_stock', $qty),
                    default => null,
                };

                $after = [
                    'current' => $product->current_stock,
                    'committed' => $product->committed_stock,
                    'defected' => $product->defected_stock,
                ];

                HistoricalStockMovement::create([
                    'product_id' => $product->id,
                    'order_id' => $movement->order_id,
                    'status_timestamp_id' => $new->id,
                    'movement_type' => $oppositeType,
                    'quantity' => $qty,
                    'before_stock' => json_encode($before),
                    'after_stock' => json_encode($after),
                ]);
            }
        });
    }

    protected function oppositeMovementType(string $movementType): string
    {
        $movementType = strtoupper($movementType);

        return match ($movementType) {
            StockMovementType::RESERVED->value => StockMovementType::REVERTED->value,
            StockMovementType::DEDUCTED->value => StockMovementType::RETURNED->value,
            StockMovementType::RETURNED->value => StockMovementType::RESERVED->value,
            StockMovementType::DEFECTED->value => StockMovementType::RETURNED->value,
            default => StockMovementType::RETURNED->value,
        };
    }

    /**
     * Reserve (new/received)
     */
    public function reserveStock(OrderItem $item, int $statusTimestampId): void
    {
        Log::info('StockService::reserveStock called', [
            'product_id' => $item->product_id,
            'order_item_id' => $item->id,
            'order_id' => $item->order_id,
            'status_timestamp_id' => $statusTimestampId,
            'qty' => $item->quantity,
        ]);

        DB::transaction(function () use ($item, $statusTimestampId) {
            $product = $item->product()->lockForUpdate()->firstOrFail();
            $stock = $product->stocks()->lockForUpdate()->firstOrFail();
            $qty = $item->quantity;

            $already = HistoricalStockMovement::where([
                'product_id' => $product->id,
                'order_id' => $item->order_id,
                'status_timestamp_id' => $statusTimestampId,
                'movement_type' => StockMovementType::RESERVED->value,
            ])->exists();

            if ($already) return;

            if ($stock->current_stock < $qty) {
                Log::warning('reserveStock: insufficient stock', [
                    'product_id' => $product->id,
                    'current_stock' => $stock->current_stock,
                    'required_qty' => $qty,
                ]);
                throw new InsufficientStockException("Insufficient stock for product {$product->sku}");
            }

            $before = $stock->only(['current_stock', 'committed_stock', 'defected_stock']);

            $stock->decrement('current_stock', $qty);
            $stock->increment('committed_stock', $qty);
            $stock->refresh();

            HistoricalStockMovement::create([
                'product_id' => $product->id,
                'order_id' => $item->order_id,
                'status_timestamp_id' => $statusTimestampId,
                'movement_type' => StockMovementType::RESERVED->value,
                'quantity' => $qty,
                'before_stock' => json_encode($before),
                'after_stock'  => json_encode($stock->only(['current_stock', 'committed_stock', 'defected_stock'])),
            ]);
        });
    }

    public function deductStock(OrderItem $item, int $statusTimestampId): void
    {
        DB::transaction(function () use ($item, $statusTimestampId) {
            $product = $item->product()->lockForUpdate()->firstOrFail();
            $stock = $product->stocks()->lockForUpdate()->firstOrFail();
            $qty = $item->quantity;

            $already = HistoricalStockMovement::where([
                'product_id' => $product->id,
                'order_id' => $item->order_id,
                'status_timestamp_id' => $statusTimestampId,
                'movement_type' => StockMovementType::DEDUCTED->value,
            ])->exists();

            if ($already) return;

            $before = $stock->only(['current_stock', 'committed_stock', 'defected_stock']);

            $stock->decrement('committed_stock', $qty);
            $stock->refresh();

            HistoricalStockMovement::create([
                'product_id' => $product->id,
                'order_id' => $item->order_id,
                'status_timestamp_id' => $statusTimestampId,
                'movement_type' => StockMovementType::DEDUCTED->value,
                'quantity' => $qty,
                'before_stock' => json_encode($before),
                'after_stock' => json_encode($stock->only(['current_stock', 'committed_stock', 'defected_stock'])),
            ]);
        });
    }

    public function returnStock(OrderItem $item, int $statusTimestampId, bool $defective = false): void
    {
        DB::transaction(function () use ($item, $statusTimestampId, $defective) {
            $product = $item->product()->lockForUpdate()->firstOrFail();
            $stock = $product->stocks()->lockForUpdate()->firstOrFail();
            $qty = $item->quantity;
            $type = $defective ? StockMovementType::DEFECTED->value : StockMovementType::RETURNED->value;

            $already = HistoricalStockMovement::where([
                'product_id' => $product->id,
                'order_id' => $item->order_id,
                'status_timestamp_id' => $statusTimestampId,
                'movement_type' => $type,
            ])->exists();

            if ($already) return;

            $before = $stock->only(['current_stock', 'committed_stock', 'defected_stock']);

            if ($defective) {
                $stock->increment('defected_stock', $qty);
            } else {
                $stock->increment('current_stock', $qty);
            }

            $stock->refresh();

            HistoricalStockMovement::create([
                'product_id' => $product->id,
                'order_id' => $item->order_id,
                'status_timestamp_id' => $statusTimestampId,
                'movement_type' => $type,
                'quantity' => $qty,
                'before_stock' => json_encode($before),
                'after_stock' => json_encode($stock->only(['current_stock', 'committed_stock', 'defected_stock'])),
            ]);
        });
    }

    public function revertStock(OrderItem $item, int $statusTimestampId): void
    {
        DB::transaction(function () use ($item, $statusTimestampId) {
            $product = $item->product()->lockForUpdate()->firstOrFail();
            $stock = $product->stocks()->lockForUpdate()->firstOrFail();
            $qty = $item->quantity;

            $already = HistoricalStockMovement::where([
                'product_id' => $product->id,
                'order_id' => $item->order_id,
                'status_timestamp_id' => $statusTimestampId,
                'movement_type' => StockMovementType::REVERTED->value,
            ])->exists();

            if ($already) return;

            $before = $stock->only(['current_stock', 'committed_stock', 'defected_stock']);

            $stock->increment('current_stock', $qty);
            $stock->decrement('committed_stock', $qty);
            $stock->refresh();

            HistoricalStockMovement::create([
                'product_id' => $product->id,
                'order_id' => $item->order_id,
                'status_timestamp_id' => $statusTimestampId,
                'movement_type' => StockMovementType::REVERTED->value,
                'quantity' => $qty,
                'before_stock' => json_encode($before),
                'after_stock' => json_encode($stock->only(['current_stock', 'committed_stock', 'defected_stock'])),
            ]);
        });
    }
}
