<?php

namespace App\Services;

use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;

class VendorTransferService
{
    /**
     * Transfer stock from one vendor to another.
     *
     * @param int $productId
     * @param int $sourceVendorId
     * @param int $destinationVendorId
     * @param int $quantity
     * @throws \Exception
     */
    public function transfer(
        int $productId,
        int $sourceVendorId,
        int $destinationVendorId,
        int $quantity
    ): void {
        DB::transaction(function () use (
            $productId,
            $sourceVendorId,
            $destinationVendorId,
            $quantity
        ) {
            // ✅ 1. Get Source Vendor Stock
            $sourceStock = ProductStock::where('product_id', $productId)
                ->where('vendor_id', $sourceVendorId)
                ->firstOrFail();

            // ✅ 2. Ensure enough stock
            if ($sourceStock->current_stock < $quantity) {
                throw new \Exception("Insufficient stock for vendor {$sourceVendorId}.");
            }

            // ✅ 3. Decrease Source Vendor
            $sourceStock->decreaseStock($quantity);

            // ✅ 4. Increase Destination Vendor
            $destinationStock = ProductStock::firstOrCreate(
                [
                    'product_id' => $productId,
                    'vendor_id'  => $destinationVendorId,
                ],
                [
                    'current_stock'   => 0,
                    'committed_stock' => 0,
                    'defected_stock'  => 0,
                    'historical_stock'=> 0,
                    'stock_threshold' => 5,
                ]
            );

            $destinationStock->increaseStock($quantity);

            // ✅ 5. Log Activity
            activity('vendor_transfer')
                ->performedOn($destinationStock)
                ->withProperties([
                    'product_id' => $productId,
                    'from_vendor' => $sourceVendorId,
                    'to_vendor' => $destinationVendorId,
                    'quantity' => $quantity,
                ])
                ->log("Transferred {$quantity} units from vendor {$sourceVendorId} to vendor {$destinationVendorId}");
        });
    }
}
