<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class RemittanceOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'remittance_id',
        'order_id',
        'cod_amount',
        'total_charges',
        'net_remit'
    ];

    protected $casts = [
        'cod_amount' => 'decimal:2',
        'total_charges' => 'decimal:2',
        'net_remit' => 'decimal:2',
    ];

    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function charges()
    {
        return $this->hasMany(RemittanceOrderCharge::class);
    }
}
