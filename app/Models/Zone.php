<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Scopes\CountryScope;

class Zone extends Model
{
    /** @use HasFactory<\Database\Factories\ZoneFactory> */
    use HasFactory;
    use SoftDeletes;
    use \App\Traits\BelongsToUserAndCountry;

    protected static function booted(): void
    {
        static::addGlobalScope(new CountryScope);
    }


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
