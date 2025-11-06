<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusTimestamp extends Model
{
    //

    protected $fillable = [
        'order_id',
        'status_id',
        'status_notes',
        'timestamp',
    ];

    public function status ()
    {
        return $this->belongsTo(Status::class);
    }

    public function order()
{
    return $this->belongsTo(Order::class);
}
}
