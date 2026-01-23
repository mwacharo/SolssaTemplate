<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToUserAndCountry;


class SellerExpense extends Model
{
    /** @use HasFactory<\Database\Factories\SellerExpenseFactory> */
    use HasFactory;
    use SoftDeletes;
    use BelongsToUserAndCountry;


    protected $fillable = [
        'id',
        'vendor_id',
        'description',
        'amount',
        'expense_type',
        'expense_type_id',
        'remittacnce_id',
        'country_id',
        'status',
        'incurred_on',
        'created_at',
        'updated_at',
    ];


    public function  vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }


    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }
}
