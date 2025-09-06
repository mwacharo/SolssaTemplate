<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
            'full_name',
            'email',
            'phone',
            'alt_phone',
            'address',
            'city',
            'region',
            'country_id',
            'zone_id',
            'zipcode',
            'is_spam',
        ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
