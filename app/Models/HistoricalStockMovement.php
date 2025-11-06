<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoricalStockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\HistoricalStockMovementFactory> */
    use HasFactory ,SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'product_id',
        'order_id',
        'status_timestamp_id',
        'movement_type',
        'quantity',
        'before_stock',
        'after_stock',
        'user_id',
    ];

    protected $casts = [
        'before_stock' => 'array',
        'after_stock' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function statusTimestamp(): BelongsTo
    {
        return $this->belongsTo(OrderStatusTimestamp::class, 'status_timestamp_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
