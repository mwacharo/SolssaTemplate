<?php

namespace App\Policies;

use App\Models\User;

class VendorPolicy
{
    /**
     * Can the user view the vendor listing?
     * - Admins: can view all vendors
     * - Vendors: can view only themselves
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Vendor');
    }

    /**
     * Can the user view a specific vendor?
     * - Admins: can view all vendors
     * - Vendors: can view only themselves
     */
    public function view(User $user, User $vendor): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Vendor')) {
            return $user->id === $vendor->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Admin'); // only Admins can create vendors
    }

    public function update(User $user, User $vendor): bool
    {
        return $user->hasRole('Admin');
    }

    public function delete(User $user, User $vendor): bool
    {
        return $user->hasRole('Admin');
    }

    public function restore(User $user, User $vendor): bool
    {
        return $user->hasRole('Admin');
    }

    public function forceDelete(User $user, User $vendor): bool
    {
        return $user->hasRole('Admin');
    }
}
