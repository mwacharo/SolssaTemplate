<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    /** @use HasFactory<\Database\Factories\CityFactory> */
    use HasFactory;
    use SoftDeletes;
    use \App\Traits\BelongsToUserAndCountry;


    protected $fillable = [
        'name',
        'country_id',
        // 'state_id',
        'latitude',
        'longitude',
        'population',
    ];


    public function country()
    {
        return $this->belongsTo(Country::class);
    }   

    public function zone()
    {
        return $this->hasMany(Zone::class);
    }
}
