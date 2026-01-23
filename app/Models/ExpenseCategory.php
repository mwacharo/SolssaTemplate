<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use HasFactory,
        SoftDeletes;

    protected $fillable = [
        'name',
        'kra_code',
        'is_deductible',
    ];

    protected $casts = [
        'is_deductible' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function expenseTypes()
    {
        return $this->hasMany(ExpenseType::class, 'category_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDeductible($query)
    {
        return $query->where('is_deductible', true);
    }

    public function scopeNonDeductible($query)
    {
        return $query->where('is_deductible', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function totalOrderExpenses()
    {
        return OrderExpense::whereHas('type', function ($q) {
            $q->where('category_id', $this->id);
        })->sum('amount');
    }

    public function totalSellerExpenses()
    {
        return SellerExpense::whereHas('type', function ($q) {
            $q->where('category_id', $this->id);
        })->sum('amount');
    }

    // public function totalCompanyExpenses()
    // {
    //     return CompanyExpense::whereHas('type', function ($q) {
    //         $q->where('category_id', $this->id);
    //     })->sum('amount');
    // }

    public function totalAllExpenses()
    {
        return $this->totalOrderExpenses()
            + $this->totalSellerExpenses()
            + $this->totalCompanyExpenses();
    }
}
