<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderAddress extends Model
{
    /** @use HasFactory<\Database\Factories\OrderAddressFactory> */
    use HasFactory;

    use SoftDeletes;


    protected $fillable = [
        'order_id',
        'type', // billing or shipping or pickup or drop or return
        'full_name',
        'email',
        'phone',
        'address',
        // 'city',
        'city_id',
        'region',
        'country_id',
        'zone_id',
        'zipcode',
    ];
}
