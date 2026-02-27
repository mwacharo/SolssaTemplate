<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorService extends Model
{
    //
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'vendor_id',
        'service_id',
        'is_active',
        // 'fee_amount',
        // 'fee_percentage'
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

  
    // has many servicerates and service conditions

    public function serviceRates()
    {
        return $this->hasMany(ServiceRate::class);
    }



    // public function serviceConditions()
    // {
    //     return $this->hasMany(ServiceCondition::class);
    // }
}
