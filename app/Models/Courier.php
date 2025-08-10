<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Courier extends Model
{
    /** @use HasFactory<\Database\Factories\CourierFactory> */
    use HasFactory;
    use \App\Traits\BelongsToUserAndCountry;

    use SoftDeletes;


    protected $fillable = [
        'name',
        'phone_number',
        'email',
        // 'vehicle_type',
        // 'license_plate',
        'status',
        'country_id',
        // 'city_id',
        // 'zone_id',
    ];
}
