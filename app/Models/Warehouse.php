<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFactory> */
    use HasFactory;


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
