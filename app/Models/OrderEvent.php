<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEvent extends Model
{
    protected $fillable = [
        'order_id',
        'event_type',
        'event_data',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
