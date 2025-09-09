<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    /** @use HasFactory<\Database\Factories\ZoneFactory> */
    use HasFactory;
    use SoftDeletes;
    use \App\Traits\BelongsToUserAndCountry;


    protected $fillable = [
        'name',
        'country_id',
        // 'state_id',
        'city_id',
        'latitude',
        'longitude',
        'population',
        'inbound',
    ];


    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
