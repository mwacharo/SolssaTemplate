<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPayment extends Model
{
    use HasFactory, SoftDeletes;



    const STATUS_PENDING   = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_FAILED    = 2;
    const STATUS_FLAGGED   = 3;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',              // FK to orders
        'method',                // Payment method (e.g., mpesa, bank)
        'amount',                // Amount paid
        'balance',               // Remaining balance if any
        'status',                // 0=pending,1=confirmed,2=failed
        'transaction_id',        // Your internal transaction reference
        'checkout_request_id',   // Safaricom STK Push CheckoutRequestID
        'merchant_request_id',   // Safaricom MerchantRequestID
        'mpesa_receipt',         // MpesaReceiptNumber from callback
        'payment_date',          // Date of successful payment
        'notes',                 // Optional notes
        'meta',                  // JSON payload from callback (callback metadata)
        'phone',                 // Phone number used for payment
        'result_code',           // Result code from M-PESA callback
        'result_desc',           // Result description from M-PESA callback
        'raw_response',          // Raw JSON response from STK Push initiation
        'raw_callback',          // Raw JSON payload from M-PESA callback
        'paid_at',               // Timestamp when payment was completed
    ];

    /**
     * Casts
     */
    protected $casts = [
        'meta' => 'array',
        'payment_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
