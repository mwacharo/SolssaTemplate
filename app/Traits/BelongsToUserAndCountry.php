<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait BelongsToUserAndCountry
{
    public static function bootBelongsToUserAndCountry(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $user = Auth::user();
                
                // Set country_id if the model has this field and it's empty
                if ($model->shouldSetCountryId()) {
                    $model->country_id = $user->country_id;
                }
                
                // Set user_id if the model has this field and it's empty
                if ($model->shouldSetUserId()) {
                    $model->user_id = $user->id;
                }
            }
        });
    }
    
    protected function shouldSetCountryId(): bool
    {
        return property_exists($this, 'country_id') && 
               in_array('country_id', $this->fillable) && 
               empty($this->country_id);
    }
    
    protected function shouldSetUserId(): bool
    {
        return property_exists($this, 'user_id') && 
               in_array('user_id', $this->fillable) && 
               empty($this->user_id);
    }
}