<?php

namespace App\Services\Order;

use Spatie\Activitylog\Models\Activity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;

class OrderTimelineService
{
    public function getTimeline(int $orderId)
    {
        $order = Order::findOrFail($orderId);

        // Preload related IDs
        $orderItemIds = $order->orderItems()->pluck('id');
        $customerId = $order->customer_id;

        return Activity::where(function ($query) use ($orderId) {

            // 1. Direct order logs
            $query->where('subject_type', Order::class)
                ->where('subject_id', $orderId);
        })
            ->orWhere(function ($query) use ($orderId) {

                // 2. OrderItem logs (JSON)
                $query->where('subject_type', OrderItem::class)
                    ->whereRaw("JSON_EXTRACT(properties, '$.attributes.order_id') = ?", [$orderId]);
            })
            ->orWhere(function ($query) use ($orderItemIds) {

                // 3. Fallback: OrderItem via IDs
                $query->where('subject_type', OrderItem::class)
                    ->whereIn('subject_id', $orderItemIds);
            })
            ->orWhere(function ($query) use ($customerId) {

                // 4. Customer logs
                $query->where('subject_type', Customer::class)
                    ->where('subject_id', $customerId);
            })
            ->with('causer')
            ->latest()
            ->get()
            ->map(function ($log) {
                return [
                    'title' => $this->formatTitle($log),
                    'actor' => optional($log->causer)->name ?? 'System',
                    'time' => $log->created_at,
                    'type' => class_basename($log->subject_type),
                    'changes' => $this->formatChanges($log),
                ];
            });
    }

    protected function formatTitle($log)
    {
        return match ($log->description) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            default => $log->description,
        };
    }

    protected function formatChanges($log)
    {
        $props = $log->properties;

        if (!isset($props['old'])) {
            return $props['attributes'] ?? [];
        }

        $changes = [];

        foreach ($props['attributes'] as $key => $value) {
            $old = $props['old'][$key] ?? null;

            if ($value != $old) {
                $changes[$key] = [
                    'old' => $old,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }
}
