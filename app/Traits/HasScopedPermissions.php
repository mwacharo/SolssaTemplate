<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasScopedPermissions
{
    /**
     * Apply scope filters based on user permissions
     */
    public function scopeForUser(Builder $query, $user, string $resource = null)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        // Admin gets everything
        if ($user->hasRole('Admin')) {
            return $query;
        }

        $resource = $resource ?: $this->getResourceName();
        
        // Apply vendor scope
        if ($this->shouldApplyVendorScope($user, $resource)) {
            return $this->applyVendorScope($query, $user);
        }
        
        // Apply delivery agent scope
        if ($this->shouldApplyDeliveryScope($user, $resource)) {
            return $this->applyDeliveryScope($query, $user);
        }
        
        return $query;
    }

    /**
     * Check if vendor scope should be applied
     */
    protected function shouldApplyVendorScope($user, string $resource): bool
    {
        return $user->hasRole('Vendor') && 
               $user->hasPermissionTo("{$resource}_view_own");
    }

    /**
     * Check if delivery agent scope should be applied
     */
    protected function shouldApplyDeliveryScope($user, string $resource): bool
    {
        return $user->hasRole('DeliveryAgent') && 
               $user->hasPermissionTo("{$resource}_view_assigned");
    }

    /**
     * Apply vendor-specific filters
     */
    protected function applyVendorScope(Builder $query, $user)
    {
        $resourceName = $this->getResourceName();
        
        switch ($resourceName) {
            case 'orders':
                return $query->whereHas('items.product', function ($q) use ($user) {
                    $q->where('vendor_id', $user->vendor_id);
                });
                
            case 'products':
                return $query->where('vendor_id', $user->vendor_id);
                
            case 'inventory':
                return $query->whereHas('product', function ($q) use ($user) {
                    $q->where('vendor_id', $user->vendor_id);
                });
                
            default:
                return $query->where('user_id', $user->id);
        }
    }

    /**
     * Apply delivery agent specific filters
     */
    protected function applyDeliveryScope(Builder $query, $user)
    {
        return $query->where('delivery_agent_id', $user->id);
    }

    /**
     * Get the resource name from the model
     */
    protected function getResourceName(): string
    {
        $className = class_basename($this);
        return strtolower($className) . 's';
    }
}