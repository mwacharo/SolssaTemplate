<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEvent extends Model
{
    protected $fillable = [
        'order_id',
        'event_type',
        'event_data',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Compare with previous event and return changes.
     */
    public function diffWith(?OrderEvent $previousEvent): array
    {
        if (!$previousEvent) {
            return [];
        }

        $old = $previousEvent->event_data ?? [];
        $new = $this->event_data ?? [];

        $diffs = [];

        foreach ($new as $key => $value) {
            $oldValue = $old[$key] ?? null;

            if ($value !== $oldValue) {
                $diffs[$key] = [
                    'old' => $oldValue,
                    'new' => $value,
                ];
            }
        }

        return $diffs;
    }
}
