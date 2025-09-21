<?php

namespace App\Services;

use App\Models\Product;
use App\Models\OrderItem;
use App\Models\HistoricalStockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function reserveStock(OrderItem $item): void
    {
        DB::transaction(function () use ($item) {
            $product = $item->product()->lockForUpdate()->first();

            if ($product->current_stock < $item->quantity) {
                throw new \Exception("Insufficient stock for product {$product->id}");
            }

            $product->decrement('current_stock', $item->quantity);
            $product->increment('committed_stock', $item->quantity);

            $this->logMovement($product, $item->order_id, 'RESERVED', $item->quantity);
        });
    }

    public function deductStock(OrderItem $item): void
    {
        DB::transaction(function () use ($item) {
            $product = $item->product()->lockForUpdate()->first();

            $product->decrement('committed_stock', $item->quantity);

            $this->logMovement($product, $item->order_id, 'DEDUCTED', $item->quantity);
        });
    }

    public function returnStock(OrderItem $item, bool $defective = false): void
    {
        DB::transaction(function () use ($item, $defective) {
            $product = $item->product()->lockForUpdate()->first();

            if ($defective) {
                $product->increment('defected_stock', $item->quantity);
                $this->logMovement($product, $item->order_id, 'DEFECTED', $item->quantity);
            } else {
                $product->increment('current_stock', $item->quantity);
                $this->logMovement($product, $item->order_id, 'RETURNED', $item->quantity);
            }
        });
    }

    protected function logMovement(Product $product, int $orderId, string $type, int $qty): void
    {
        HistoricalStockMovement::create([
            'product_id'   => $product->id,
            'order_id'     => $orderId,
            'movement_type'=> $type,
            'quantity'     => $qty,
            'before_stock' => $product->getOriginal('current_stock'),
            'after_stock'  => $product->current_stock,
            'created_at'   => now(),
        ]);
    }
}
