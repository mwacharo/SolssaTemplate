<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductDelivery extends Model
{
    /** @use HasFactory<\Database\Factories\ProductDeliveryFactory> */
    use HasFactory;

    use SoftDeletes;


    protected $fillable = [
        'product_id',
        'city',
        'delivery_time',
        'shipping_fee',
        'is_free'
    ];

    protected $casts = [
        'is_free' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
