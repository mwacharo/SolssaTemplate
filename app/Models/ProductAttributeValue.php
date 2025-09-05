<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    /** @use HasFactory<\Database\Factories\ProductAttributeValueFactory> */
    use HasFactory;

    protected $fillable = [
        'product_attribute_id',
        'value',
    ];



    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class);
    }
}
