<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\BelongsToVendor;
use App\Traits\BelongsToUserAndCountry;


class Product extends Model
{
    use HasFactory, LogsActivity,SoftDeletes;
        use BelongsToVendor;
            use BelongsToUserAndCountry;



    protected $fillable = [
        'sku',
        'category_id',
        'country_id',
        'vendor_id',
        'product_name',
        'description',
    ];

    // Spatie Activitylog configuration
    protected static $logAttributes = [
        'sku',
        'category_id',
        'vendor_id',
        'product_name',
        'description',
    ];
    protected static $logName = 'product';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('product');
    }

   

    public function vendor()
{
    return $this->belongsTo(\App\Models\User::class, 'vendor_id');
}


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

   
    public function statistics()
    {
        return $this->hasOne(ProductStatistic::class);
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }
     public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }


    public function media()
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function offers()
    {
        return $this->hasMany(ProductOffer::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }


     public function attributeValues()
    {
        return $this->hasMany(\App\Models\ProductAttributeValue::class, 'product_id');
    }


// product belongs to a warehouse
// product belongs to a country 
}
