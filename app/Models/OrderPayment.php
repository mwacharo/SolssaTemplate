<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    /** @use HasFactory<\Database\Factories\PayementFactory> */
    use HasFactory;


    protected $fillable = [
        'order_id',
        'amount',
        'balance',
        'method',
        'status',
        'transaction_id',
        'payment_date',
        'notes',
    ];  


    
}
