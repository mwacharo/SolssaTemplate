<?php

namespace App\Services;

use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;

class WarehouseTransferService
{
    /**
     * Transfer stock from one warehouse/vendor to another.
     *
     * @param int $productId
     * @param int $sourceWarehouseId
     * @param int $destinationWarehouseId
     * @param int $quantity
     * @param int|null $vendorId (optional if vendor transfer)
     * @throws \Exception
     */
    public function transfer(
        int $productId,
        int $sourceWarehouseId,
        int $destinationWarehouseId,
        int $quantity,
        ?int $vendorId = null
    ): void {
        DB::transaction(function () use (
            $productId,
            $sourceWarehouseId,
            $destinationWarehouseId,
            $quantity,
            $vendorId
        ) {
            // ✅ 1. Get Source Stock
            $sourceStock = ProductStock::where('product_id', $productId)
                ->where('warehouse_id', $sourceWarehouseId)
                ->firstOrFail();

            // ✅ 2. Ensure enough stock
            if ($sourceStock->current_stock < $quantity) {
                throw new \Exception("Insufficient stock in source warehouse/vendor.");
            }

            // ✅ 3. Decrease Source
            $sourceStock->decreaseStock($quantity);

            // ✅ 4. Increase Destination
            $destinationStock = ProductStock::firstOrCreate(
                [
                    'product_id'   => $productId,
                    'warehouse_id' => $destinationWarehouseId,
                ],
                [
                    'current_stock'   => 0,
                    'committed_stock' => 0,
                    'defected_stock'  => 0,
                    'historical_stock'=> 0,
                    'stock_threshold' => 5, // default low stock threshold
                ]
            );

            $destinationStock->increaseStock($quantity);

            // ✅ 5. Log Activity
            activity('stock_transfer')
                ->performedOn($destinationStock)
                ->withProperties([
                    'product_id' => $productId,
                    'from' => $sourceWarehouseId,
                    'to' => $destinationWarehouseId,
                    'quantity' => $quantity,
                    'vendor_id' => $vendorId,
                ])
                ->log("Transferred {$quantity} units from warehouse {$sourceWarehouseId} to warehouse {$destinationWarehouseId}");
        });
    }
}
