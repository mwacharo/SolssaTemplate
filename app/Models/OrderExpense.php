<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        // 'vendor_id',
        // 'country_id',
        'user_id',        // rider / call agent / courier
        'expense_type_id',
        'description',
        'amount',
        'status',
        'incurred_on',
        'paid_at',
        'payment_reference',
        'currency',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'incurred_on' => 'date',
        'paid_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // public function vendor()
    // {
    //     return $this->belongsTo(User::class, 'vendor_id');
    // }

    // Rider / Agent / Courier
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // public function country()
    // {
    //     return $this->belongsTo(Country::class);
    // }


    // BELONGS TO INVOICE  OF  THAT THE EXPENSE WAS PAID

    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('expense_type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function markAsPaid($reference = null)
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $reference,
        ]);
    }
}
