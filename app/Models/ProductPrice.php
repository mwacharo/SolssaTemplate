<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'vendor_id',
        'base_price',
        'discount_price',
        'cost_price',
        'wholesale_price',
        'currency',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the effective price (discount applied if valid).
     */
    protected function effectivePrice(): Attribute
    {
        return Attribute::get(function () {
            if ($this->discount_price && $this->isDiscountActive()) {
                return $this->discount_price;
            }
            return $this->base_price;
        });
    }

    /**
     * Check if discount is valid (based on time and status).
     */
    public function isDiscountActive(): bool
    {
        return $this->discount_price &&
            $this->is_active &&
            (is_null($this->valid_from) || now()->gte($this->valid_from)) &&
            (is_null($this->valid_to) || now()->lte($this->valid_to));
    }
}
