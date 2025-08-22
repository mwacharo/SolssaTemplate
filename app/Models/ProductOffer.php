<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',        // The product this offer applies to
        'offer_type',        // 'tiered', 'bogo', 'cart_discount', 'cross_product'
        'min_quantity',      // e.g. 2 (buy at least 2)
        'discount_value',    // e.g. 10% OR 500 (fixed)
        'discount_type',     // 'percentage' or 'fixed'
        'free_product_id',   // for cross-product offers like plate -> spoon
        'free_quantity',     // e.g. 1 spoon
        'spend_threshold',   // for cart-level offers (e.g. spend $5000 get 10% off)
        'start_date',
        'end_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function freeProduct()
    {
        return $this->belongsTo(Product::class, 'free_product_id');
    }
}
