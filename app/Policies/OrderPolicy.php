<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Permission for listing orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'Admin',
            'Manager',
            'superAdmin',
            'Delivery Agent',
            'Delivery Man',
            'DeliveryAgent',
            'CallAgent',
            'Call Agent'
        ]);
    }

    /**
     * Permission for viewing a single order.
     */
    public function view(User $user, Order $order): bool
    {
        // Admins / Managers
        if ($user->hasAnyRole(['Admin', 'Manager', 'superAdmin'])) {
            return true;
        }

        // Delivery Agent (all variations)
        if ($user->hasAnyRole(['Delivery Agent', 'Delivery Man', 'DeliveryAgent'])) {
            return $order->assignments()
                ->whereIn('role', ['Delivery Agent', 'Delivery Man', 'DeliveryAgent'])
                ->where('user_id', $user->id)
                ->exists();
        }

        // CallAgent (all variations)
        if ($user->hasAnyRole(['CallAgent', 'Call Agent', 'call_agent'])) {
            return $order->assignments()
                ->whereIn('role', ['CallAgent', 'Call Agent', 'call_agent'])
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Creating orders.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'Admin',
            'Manager',
            'superAdmin',
            'CallAgent',
            'Call Agent',
            'Vendor',
        ]);
    }

    /**
     * Updating orders.
     */
    public function update(User $user, Order $order): bool
    {
        // Admin / Manager / superAdmin
        if ($user->hasAnyRole(['Admin', 'Manager', 'superAdmin'])) {
            return true;
        }

        // Delivery Agent (all variations)
        if ($user->hasAnyRole(['Delivery Agent', 'Delivery Man', 'DeliveryAgent'])) {
            return $order->assignments()
                ->whereIn('role', ['Delivery Agent', 'Delivery Man', 'DeliveryAgent'])
                ->where('user_id', $user->id)
                ->exists();
        }

        // CallAgent (all variations)
        if ($user->hasAnyRole(['CallAgent', 'Call Agent', 'call_agent'])) {
            return $order->assignments()
                ->whereIn('role', ['CallAgent', 'Call Agent', 'call_agent'])
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Only Admins can delete
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->hasAnyRole(['Admin', 'superAdmin']);
    }

    public function restore(User $user, Order $order): bool
    {
        return $user->hasAnyRole(['Admin', 'superAdmin']);
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $user->hasAnyRole(['Admin', 'superAdmin']);
    }



    // public function assignRider(User $user, Order $order): bool
    // {
    //     // Only admins/managers
    //     return $user->hasAnyRole(['Admin', 'Manager', 'superAdmin']);
    // }

    // public function assignAgent(User $user, Order $order): bool
    // {
    //     return $user->hasAnyRole(['Admin', 'Manager', 'superAdmin']);
    // }

    // public function updateStatus(User $user, Order $order): bool
    // {
    //     // Block vendors completely
    //     if ($user->hasRole('Vendor')) {
    //         return false;
    //     }

    //     return $user->hasAnyRole([
    //         'Admin',
    //         'Manager',
    //         'superAdmin',
    //         'Delivery Agent',
    //         'CallAgent'
    //     ]);
    // }

    // public function printWaybill(User $user, Order $order): bool
    // {
    //     // Vendors can only print their own
    //     if ($user->hasRole('Vendor')) {
    //         return $order->vendor_id === $user->id;
    //     }

    //     return true;
    // }
}
