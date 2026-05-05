<?php

namespace App\Policies;

use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductStockPolicy
{

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inventory_view');
    }

    public function view(User $user, ProductStock $productStock): bool
    {
        if ($user->hasPermissionTo('inventory_view')) {
            return true;
        }

        if ($user->hasPermissionTo('inventory_view_own')) {
            $vendorId = $productStock->relationLoaded('product')
                ? $productStock->product?->vendor_id
                : $productStock->loadMissing('product')->product?->vendor_id;

            return (int) $vendorId === (int) $user->id;
        }

        return false;
    }

    /**
     * Stock creation is an admin-only operation.
     * inventory_adjust exists only on Admin in the permissions JSON.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('inventory_adjust');
    }

    /**
     * Stock updates via the product update endpoint:
     * - Admin only. inventory_adjust_own (Vendor) is intentionally
     *   excluded here — vendors adjust stock through their own
     *   dedicated inventory endpoint, not through product update.
     */
    public function update(User $user, ProductStock $productStock): bool
    {
        return $user->hasPermissionTo('inventory_adjust');
    }

    public function delete(User $user, ProductStock $productStock): bool
    {
        return $user->hasPermissionTo('inventory_adjust');
    }

    public function restore(User $user, ProductStock $productStock): bool
    {
        return $user->hasPermissionTo('inventory_adjust');
    }

    public function forceDelete(User $user, ProductStock $productStock): bool
    {
        return $user->hasPermissionTo('inventory_adjust');
    }
}
