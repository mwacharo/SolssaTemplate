<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    /** @use HasFactory<\Database\Factories\ShipmentItemFactory> */
    use HasFactory;

    protected $fillable = [
        'expedition_id',
        'product_id',
        'quantity_sent',
        'quantity_received',
        'unit_price',
        'total_value',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity_sent' => 'integer',
        'quantity_received' => 'integer',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    // Relationships
    public function expedition()
    {
        return $this->belongsTo(Expedition::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeForExpedition($query, $expeditionId)
    {
        return $query->where('expedition_id', $expeditionId);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeFullyReceived($query)
    {
        return $query->whereColumn('quantity_received', '=', 'quantity_sent');
    }

    public function scopePartiallyReceived($query)
    {
        return $query->whereColumn('quantity_received', '<', 'quantity_sent')
            ->where('quantity_received', '>', 0);
    }

    public function scopePending($query)
    {
        return $query->where('quantity_received', 0)
            ->orWhereNull('quantity_received');
    }

    // Accessors
    public function getQuantityPendingAttribute()
    {
        return $this->quantity_sent - ($this->quantity_received ?? 0);
    }

    public function getReceivePercentageAttribute()
    {
        if ($this->quantity_sent == 0) {
            return 0;
        }
        return round(($this->quantity_received / $this->quantity_sent) * 100, 2);
    }

    public function getIsFullyReceivedAttribute()
    {
        return $this->quantity_received >= $this->quantity_sent;
    }

    public function getIsPartiallyReceivedAttribute()
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity_sent;
    }

    public function getCalculatedTotalValueAttribute()
    {
        return $this->quantity_sent * ($this->unit_price ?? 0);
    }

    // Mutators
    public function setQuantitySentAttribute($value)
    {
        $this->attributes['quantity_sent'] = max(0, (int) $value);

        // Auto-calculate total value if unit price exists
        if (isset($this->attributes['unit_price'])) {
            $this->attributes['total_value'] = $this->attributes['quantity_sent'] * $this->attributes['unit_price'];
        }
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = max(0, (float) $value);

        // Auto-calculate total value if quantity exists
        if (isset($this->attributes['quantity_sent'])) {
            $this->attributes['total_value'] = $this->attributes['quantity_sent'] * $this->attributes['unit_price'];
        }
    }

    // Methods
    public function markAsReceived($quantityReceived = null)
    {
        $oldQuantityReceived = $this->quantity_received ?? 0;
        $newQuantityReceived = $quantityReceived ?? $this->quantity_sent;
        $quantityDifference = $newQuantityReceived - $oldQuantityReceived;

        \DB::transaction(function () use ($newQuantityReceived, $quantityDifference) {
            // Update shipment item
            $this->quantity_received = $newQuantityReceived;
            $this->status = $this->is_fully_received ? 'received' : 'partial';
            $this->save();

            // Update product stock
            if ($quantityDifference > 0) {
                $this->updateProductStock($quantityDifference);
            }
        });

        return $this;
    }

    public function updateReceivedQuantity($quantity)
    {
        $oldQuantityReceived = $this->quantity_received ?? 0;
        $newQuantityReceived = min($quantity, $this->quantity_sent);
        $quantityDifference = $newQuantityReceived - $oldQuantityReceived;

        \DB::transaction(function () use ($newQuantityReceived, $quantityDifference) {
            // Update shipment item
            $this->quantity_received = $newQuantityReceived;
            $this->status = $this->is_fully_received ? 'received' : 'partial';
            $this->save();

            // Update product stock if there's an increase
            if ($quantityDifference > 0) {
                $this->updateProductStock($quantityDifference);
            }
        });

        return $this;
    }

    /**
     * Update the product stock when items are received
     * 
     * @param int $quantityIncrease The quantity to add to stock
     * @return void
     */
    protected function updateProductStock($quantityIncrease)
    {
        // Get the warehouse from the expedition
        $expedition = $this->expedition;

        if (!$expedition || !$expedition->warehouse_id) {
            \Log::warning("Cannot update product stock: No warehouse found for expedition {$expedition->id}");
            return;
        }

        // Find or create product stock for this warehouse
        $productStock = ProductStock::firstOrCreate(
            [
                'product_id' => $this->product_id,
                'warehouse_id' => $expedition->warehouse_id,
                'country_id' => $expedition->warehouse->country_id ?? null,
            ],
            [
                'current_stock' => 0,
                'committed_stock' => 0,
                'defected_stock' => 0,
                'historical_stock' => 0,
                'stock_threshold' => 0,
                'stock_delivered' => 0,
            ]
        );

        // Increment the current stock and stock delivered
        $productStock->increment('current_stock', $quantityIncrease);
        // $productStock->increment('stock_delivered', $quantityIncrease);
        $productStock->increment('historical_stock', $quantityIncrease);

        \Log::info("Product stock updated", [
            'product_id' => $this->product_id,
            'warehouse_id' => $expedition->warehouse_id,
            // 'quantity_added' => $quantityIncrease,
            'new_current_stock' => $productStock->current_stock,
        ]);
    }

    // Boot method for automatic calculations
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($shipmentItem) {
            // Auto-calculate total value before saving
            if ($shipmentItem->quantity_sent && $shipmentItem->unit_price) {
                $shipmentItem->total_value = $shipmentItem->quantity_sent * $shipmentItem->unit_price;
            }
        });
    }
}
