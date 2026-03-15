<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCountryAccount extends Model
{
    protected $fillable = [
        'user_id',
        'country_id',
        'client_name',
        'token',
        'phone_number',
        'alt_number',
        'country_code',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
