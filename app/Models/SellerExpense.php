<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellerExpense extends Model
{
    /** @use HasFactory<\Database\Factories\SellerExpenseFactory> */
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'id',
        'vendor_id',
        'description',
        'amount',
        'expense_type',
        'invoice_id',
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
}
