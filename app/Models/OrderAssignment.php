<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OrderAssignment extends Model
{


    use LogsActivity;

    /** @use HasFactory<\Database\Factories\OrderAssignmentFactory> */
    use HasFactory;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('order_assignment');
    }


    protected $fillable = [
        'order_id',
        'user_id',
        // 'assigned_by',
        'role', // picker, packer, shipper, delivery, support, manager
        'status', // pending, in_progress, completed
        // 'started_at',
        // 'completed_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // public function assignedBy()
    // {
    //     return $this->belongsTo(User::class, 'assigned_by');
    // }
}
