<?php

namespace App\Services\Order;

use Spatie\Activitylog\Models\Activity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\OrderStatusTimestamp;

class OrderTimelineService
{


    // get order events 



    public function orderEvents(int $orderId): array
    {
        $order = Order::findOrFail($orderId);

        $result = [];
        $previousEvent = null;

        $events = $order->events()
            ->with('actor:id,name')
            ->orderBy('created_at')
            ->cursor();

        foreach ($events as $event) {

            $meta = $event->event_data;

            if (!is_array($meta)) {
                $meta = json_decode($meta, true) ?? [];
            }

            $event->event_data = $meta;

            $result[] = [
                'type'    => $event->event_type,
                'changes' => $event->diffWith($previousEvent),
                'actor'   => optional($event->actor)->name ?? 'user',
                'time'    => $event->created_at->toDateTimeString(),
            ];

            $previousEvent = $event;
        }

        return $result; // ✅ RETURN ARRAY ONLY
    }


    private function formatEventTitle($event): string
    {
        $data = $event->event_data ?? [];

        return match ($event->event_type) {

            'created' => 'Order Created',

            'status_changed' =>
            isset($data['from'], $data['to'])
                ? "Status changed from {$data['from']} to {$data['to']}"
                : 'Status Updated',

            'scheduled' =>
            isset($data['date'])
                ? "Order scheduled for {$data['date']}"
                : 'Order Scheduled',

            'dispatched' => 'Order Dispatched',

            'delivered' => 'Order Delivered',

            'cancelled' => 'Order Cancelled',

            default => ucfirst(str_replace('_', ' ', $event->event_type)),
        };
    }


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


            ->orWhere(function ($query) use ($orderId) {
                $query->where('subject_type', OrderStatusTimestamp::class)
                    ->whereRaw("JSON_EXTRACT(properties, '$.attributes.order_id') = ?", [$orderId]);
            })

            // OrderAssignment
            ->orWhere(function ($query) use ($orderId) {
                $query->where('subject_type', 'App\Models\OrderAssignment')
                    ->whereRaw("JSON_EXTRACT(properties, '$.attributes.order_id') = ?", [$orderId]);
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
