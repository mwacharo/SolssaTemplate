<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceRate extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceRateFactory> */
    use HasFactory;
    use SoftDeletes;


    protected $fillable = ['vendor_service_id', 'service_condition_id', 'custom_rate'];

    public function vendorService()
    {
        return $this->belongsTo(VendorService::class);
    }

    public function condition()
    {
        return $this->belongsTo(ServiceCondition::class, 'service_condition_id');
    }
}
