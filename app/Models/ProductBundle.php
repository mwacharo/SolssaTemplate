<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBundle extends Model
{
    /** @use HasFactory<\Database\Factories\ProductBundleFactory> */
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'product_id',
        'title',
        'quantity',
        'price',
        'old_price',
        'is_popular'
    ];

    protected $casts = [
        'is_popular' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
