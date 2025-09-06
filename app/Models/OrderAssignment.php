<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\OrderAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'assigned_by',
        'role', // picker, packer, shipper, delivery, support, manager
        'status', // pending, in_progress, completed
        'started_at',
        'completed_at',
    ];
}
