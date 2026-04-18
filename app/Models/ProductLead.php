<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductLead extends Model
{
    /** @use HasFactory<\Database\Factories\ProductLeadFactory> */
    use HasFactory;
    use SoftDeletes;



    protected $fillable = [
        'product_id',
        'name',
        'phone',
        'location',
        'bundle_id',
        'status',
        'source'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function bundle()
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id');
    }
}
