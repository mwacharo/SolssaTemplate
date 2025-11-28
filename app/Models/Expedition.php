<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expedition extends Model
{
    /** @use HasFactory<\Database\Factories\ExpeditionFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'weight',
        'packages_number',
        'source_country',
        'warehouse_id',
        'shipment_date',
        'arrival_date',
        'shipment_fees',
        'shipment_status',
        'approval_status',
        'transporter_reimbursement_status',
        'transporter_name',
        'tracking_number',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'packages_number' => 'integer',
        'shipment_fees' => 'decimal:2',
        'shipment_date' => 'date',
        'arrival_date' => 'datetime',
    ];

    // Relationships
    public function  vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shipmentItems()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    // Scopes
    public function scopeExpedited($query)
    {
        return $query->where('shipment_status', 'expedited');
    }

    public function scopePending($query)
    {
        return $query->where('shipment_status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('shipment_status', 'delivered');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeDraft($query)
    {
        return $query->where('approval_status', 'draft');
    }

    public function scopePaid($query)
    {
        return $query->where('transporter_reimbursement_status', 'paid');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('tracking_number', 'like', "%{$search}%")
                ->orWhere('source_country', 'like', "%{$search}%")
                ->orWhere('transporter_name', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getTotalItemsAttribute()
    {
        return $this->shipmentItems()->sum('quantity_sent');
    }

    public function getIsOverdueAttribute()
    {
        return $this->arrival_date < now() && $this->shipment_status !== 'delivered';
    }

    // Mutators
    public function setShipmentDateAttribute($value)
    {
        $this->attributes['shipment_date'] = $value ? date('Y-m-d', strtotime($value)) : null;
    }
}
