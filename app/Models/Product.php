<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'category_id',
        'vendor_id',
        'product_name',
        'description',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function statistics()
    {
        return $this->hasOne(ProductStatistic::class);
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function media()
    {
        return $this->hasMany(ProductMedia::class);
    }


public function variants()
{
    return $this->hasMany(ProductVariant::class);
}


    
}
