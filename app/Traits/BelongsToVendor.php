<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToVendor
{
    /**
     * Boot the trait and apply vendor scoping
     */
    protected static function bootBelongsToVendor(): void
    {
        static::addGlobalScope('vendor', function (Builder $builder) {
            $user = Auth::user();

            // If user is logged in and has the Vendor role, restrict queries
            if ($user && $user->hasRole('Vendor')) {
                $builder->where('vendor_id', $user->id);
            }
        });

        // Automatically assign vendor_id on create
        static::creating(function ($model) {
            $user = Auth::user();

            if ($user && $user->hasRole('Vendor') && empty($model->vendor_id)) {
                $model->vendor_id = $user->id;
            }
        });
    }

    /**
     * Vendor relationship
     */
    public function vendor()
    {
        return $this->belongsTo(\App\Models\User::class, 'vendor_id');
    }
}
