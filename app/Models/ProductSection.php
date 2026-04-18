<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSection extends Model
{
    /** @use HasFactory<\Database\Factories\ProductSectionFactory> */
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'product_id',
        // hero
        // problem
        // benefits
        // how_it_works
        // composition
        // comparison
        // trust ----
        //  offer, urgency, cta, faq, reviews, testimonials, delivery
        'type',
        'title',
        'content',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
