<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class RemittanceOrderCharge extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'remittance_order_id',
        'service_id',
        'rate_source',
        'rate_type',
        'rate_value',
        'amount'
    ];

    protected $casts = [
        'rate_value' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function remittanceOrder()
    {
        return $this->belongsTo(RemittanceOrder::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
