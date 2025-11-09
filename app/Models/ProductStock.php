<?php

namespace App\Models;

use App\Traits\BelongsToUserAndCountry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductStock extends Model
{
    use HasFactory;
        use BelongsToUserAndCountry;


    protected $fillable = [
        'product_id',
        'country_id',
        'warehouse_id',
        'current_stock',
        'committed_stock',
        'defected_stock',
        'historical_stock',
        'stock_threshold',
        'stock_delivered',
    ];

    /*
     |--------------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------------
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /*
     |--------------------------------------------------------------------------
     | Stock Management Methods
     |--------------------------------------------------------------------------
     */
    public function increaseStock(int $quantity): void
    {
        $this->increment('current_stock', $quantity);
        $this->increment('historical_stock', $quantity);
        activity()
            ->performedOn($this)
            ->withProperties(['quantity' => $quantity])
            ->log("Increased stock by {$quantity}");
    }

    public function decreaseStock(int $quantity): void
    {
        if ($this->current_stock < $quantity) {
            throw new \Exception("Insufficient stock for product ID {$this->product_id}");
        }

        $this->decrement('current_stock', $quantity);
        activity()
            ->performedOn($this)
            ->withProperties(['quantity' => $quantity])
            ->log("Decreased stock by {$quantity}");
    }

    public function commitStock(int $quantity): void
    {
        if ($this->current_stock < $quantity) {
            throw new \Exception("Not enough stock to commit for product ID {$this->product_id}");
        }

        $this->decrement('current_stock', $quantity);
        $this->increment('committed_stock', $quantity);

        activity()
            ->performedOn($this)
            ->withProperties(['quantity' => $quantity])
            ->log("Committed {$quantity} stock for orders");
    }

    public function releaseCommittedStock(int $quantity): void
    {
        $this->decrement('committed_stock', $quantity);
        $this->increment('current_stock', $quantity);

        activity()
            ->performedOn($this)
            ->withProperties(['quantity' => $quantity])
            ->log("Released {$quantity} committed stock back to inventory");
    }

    public function markAsDefected(int $quantity): void
    {
        if ($this->current_stock < $quantity) {
            throw new \Exception("Not enough stock to mark as defected for product ID {$this->product_id}");
        }

        $this->decrement('current_stock', $quantity);
        $this->increment('defected_stock', $quantity);

        activity()
            ->performedOn($this)
            ->withProperties(['quantity' => $quantity])
            ->log("Marked {$quantity} stock as defected");
    }

    /*
     |--------------------------------------------------------------------------
     | Helpers
     |--------------------------------------------------------------------------
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->stock_threshold;
    }

    public function availableStock(): int
    {
        return $this->current_stock;
    }
}

