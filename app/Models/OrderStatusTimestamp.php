<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OrderStatusTimestamp extends Model
{

    use LogsActivity;

    //

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('order_status_timestamp');
    }



    protected $fillable = [
        'order_id',
        'status_id',
        'status_category_id',
        'status_notes',
        'timestamp',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
