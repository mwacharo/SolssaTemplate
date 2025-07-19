<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rider extends Model
{
    /** @use HasFactory<\Database\Factories\RiderFactory> */
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'user_id',
        'country_id',
        'name',
        'email',
        'address',
        'city',
        'state',
        'vehicle_number',
        'license_number',
        'phone',
        'status',
    ];



   public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // public function orderItems()
    // {
    //     return $this->hasMany(OrderItem::class);
    // }

    // public function vendor()
    // {
    //     return $this->belongsTo(Vendor::class);
    // }

    // public function client()
    // {
    //     return $this->belongsTo(Client::class);
    // } 
}
