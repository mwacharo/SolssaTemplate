<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductFaq extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFaqFactory> */
    use HasFactory;
    use SoftDeletes;




    protected $fillable = [
        'product_id',
        'question',
        'answer',
        'sort_order'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
