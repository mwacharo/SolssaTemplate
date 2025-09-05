<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    /** @use HasFactory<\Database\Factories\ProductAttributeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];


    public function values()
{
    return $this->hasMany(ProductAttributeValue::class);
}

// ProductAttributeValue.php
public function attribute()
{
    return $this->belongsTo(ProductAttribute::class);
}
}
