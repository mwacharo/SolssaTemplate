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

    public function servserviceRatesiceRates()
    {
        return $this->hasMany(ServiceRate::class);
    }
}
