<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductConversion extends Model
{
    /** @use HasFactory<\Database\Factories\ProductConversionFactory> */
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'product_id',
        'headline',
        'subheadline',
        'urgency_text',
        'stock_count',
        'timer_end_at',
        'whatsapp_number',
        'phone_number',
        'cod_enabled'
    ];

    protected $casts = [
        'cod_enabled' => 'boolean',
        'timer_end_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
