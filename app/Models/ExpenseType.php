<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseType extends Model
{
    use HasFactory,
        SoftDeletes;

    protected $fillable = [
        'expense_category_id',
        'slug',
        'display_name',
        'is_order_level',
        'default_vat_rate',
    ];

    protected $casts = [
        'is_order_level' => 'boolean',
        'default_vat_rate' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    // Operational costs
    public function orderExpenses()
    {
        return $this->hasMany(OrderExpense::class, 'expense_type_id');
    }

    // Seller settlement deductions
    public function sellerExpenses()
    {
        return $this->hasMany(SellerExpense::class, 'expense_type_id');
    }

    // Company overhead
    // public function companyExpenses()
    // {
    //     return $this->hasMany(CompanyExpense::class, 'expense_type_id');
    // }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOrderLevel($query)
    {
        return $query->where('is_order_level', true);
    }

    public function scopeNonOrderLevel($query)
    {
        return $query->where('is_order_level', false);
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isDeductible()
    {
        return optional($this->category)->is_deductible ?? false;
    }
}
