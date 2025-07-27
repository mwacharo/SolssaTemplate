<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait BelongsToUserAndCountry
{
    public static function bootBelongsToUserAndCountry(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                if (property_exists($model, 'country_id') && empty($model->country_id)) {
                    $model->country_id = Auth::user()->country_id;
                }
            }
        });
    }
}
