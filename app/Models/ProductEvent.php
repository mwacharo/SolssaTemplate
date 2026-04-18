<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductEvent extends Model
{
    /** @use HasFactory<\Database\Factories\ProductEventFactory> */
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'product_id',
        'event_type',
        'session_id',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
