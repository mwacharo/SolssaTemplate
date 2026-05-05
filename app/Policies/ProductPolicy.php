<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{


    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('products_view')
            || $user->hasPermissionTo('products_view_own');
    }

    public function view(User $user, Product $product): bool
    {
        if ($user->hasPermissionTo('products_view')) {
            return true;
        }

        // Own-only permission: vendor may only see their own product
        if ($user->hasPermissionTo('products_view_own')) {
            return (int) $product->vendor_id === (int) $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('products_create')
            || $user->hasPermissionTo('products_create_own');
    }

    public function update(User $user, Product $product): bool
    {
        // Broad edit permission (Admin, Manager, CallAgent)
        if ($user->hasPermissionTo('products_edit')) {
            return true;
        }

        // Own-only edit permission (Vendor)
        if ($user->hasPermissionTo('products_edit_own')) {
            return (int) $product->vendor_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Only Admin has products_delete.
     * Vendor has products_delete_own but that is intentionally
     * NOT honoured here — soft-delete via the API is Admin-only.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products_delete');
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products_delete');
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products_delete');
    }
}
