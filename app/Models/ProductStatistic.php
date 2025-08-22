<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStatistic extends Model
{
    protected $fillable = [
        'product_id',
        'vendor_id',
        'total_orders',
        'total_pending',
        'total_cancelled',
        
        
        'total_confirmed',// scheduled for delivery, awaiting dispatch, dispatched ,intransit ,
        'total_in_delivery',
        'total_returned',
        'total_delivered',
        'delivery_rate',
    ];

    /** Relationships **/
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /** Auto-calculate delivery rate */
    public function calculateDeliveryRate(): void
    {
        if ($this->total_orders > 0) {
            $this->delivery_rate = round(($this->total_delivered / $this->total_orders) * 100, 2);
        } else {
            $this->delivery_rate = 0.00;
        }
        $this->save();
    }
}
