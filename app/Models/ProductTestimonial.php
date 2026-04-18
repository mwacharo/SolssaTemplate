<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductTestimonial extends Model
{
    /** @use HasFactory<\Database\Factories\ProductTestimonialFactory> */
    use HasFactory;
    use SoftDeletes;





    protected $fillable = [
        'product_id',
        'name',
        'title',
        'content',
        'image'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
