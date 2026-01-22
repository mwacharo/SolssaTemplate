<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Remittance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'remittances';

    protected $fillable = [
        'old_id',
        'invoice_number',
        'invoice_date',
        'payment_period_start',
        'payment_period_end',
        'approval_status',
        'manager_approved_at',
        'cfo_approved_at',
        'seller_id',
        'total_amount',
        'total_amount_mad',
        'vat_percentage',
        'conversion_rate',
        'bonus_amount',
        'confirmation_fee',
        'shipping_fee',
        'fulfillement_fee',
        'return_fee',
        'inbound_return_fee',
        'outbound_return_fee',
        'inbound_shipping_fee',
        'outbound_shipping_fee',
        'cancelation_fee',
        'percentage_fee',
        'affiliate_fee',
        'upsell_fee',
        'additional_fees',
        'total_marketplace_cost',
        'payment_status',
        'payment_date',
        'payment_method',
        'debt_amount',
        'debt_paid_status',
        'debt_invoice_id',
        'country_id',
        'is_marketplace',
        'approved_by_manager_id',
        'approved_by_cfo_id',
        'rejection_reason',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'payment_period_start' => 'datetime',
        'payment_approved_by_cfo_idperiod_end' => 'datetime',
        'manager_approved_at' => 'datetime',
        'cfo_approved_at' => 'datetime',
        'payment_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'total_amount_mad' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'confirmation_fee' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'fulfillement_fee' => 'decimal:2',
        'return_fee' => 'decimal:2',
        'inbound_return_fee' => 'decimal:2',
        'outbound_return_fee' => 'decimal:2',
        'inbound_shipping_fee' => 'decimal:2',
        'outbound_shipping_fee' => 'decimal:2',
        'cancelation_fee' => 'decimal:2',
        'percentage_fee' => 'decimal:2',
        'affiliate_fee' => 'decimal:2',
        'upsell_fee' => 'decimal:2',
        'additional_fees' => 'decimal:2',
        'total_marketplace_cost' => 'decimal:2',
        'debt_amount' => 'decimal:2',
    ];

    /*
     |--------------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------------
    */

    /** 
     * One remittance has many orders. 
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'invoice_id');
    }

    /**
     * Seller / Vendor relationship
     */
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Country
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Manager who approved
     */
    public function approvedByManager()
    {
        return $this->belongsTo(User::class, 'approved_by_manager_id');
    }

    /**
     * CFO who approved
     */
    public function approvedByCfo()
    {
        return $this->belongsTo(User::class, 'approved_by_cfo_id');
    }

    /**
     * If this remittance is linked to a debt invoice
     */
    public function debtInvoice()
    {
        return $this->belongsTo(Remittance::class, 'debt_invoice_id');
    }
}
