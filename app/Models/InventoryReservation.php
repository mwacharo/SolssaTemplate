<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryReservation extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryReservationFactory> */
    use HasFactory;


    protected $fillable = [
        'sku',
        'order_id',
        'quantity',
        'reserved_at',
        'released_at',
        'reason',
    ];
}
