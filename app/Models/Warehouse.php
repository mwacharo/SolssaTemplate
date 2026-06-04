<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CountryScope;


class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFactory> */
    use HasFactory;



    protected static function booted(): void
    {
        static::addGlobalScope(new CountryScope);
    }
    protected $fillable = [
        'country_id',
        'city_id',
        'zone_id',
        'name',
        'location',
        'contact_person',
        'phone',
    ];


    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
