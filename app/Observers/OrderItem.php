<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Models\OrderEvent;

class OrderItemObserver
{
    public function created(OrderItem $item)
    {
        $this->logEvent($item->order_id, 'order_item.created', $item->toArray());
    }

    public function updated(OrderItem $item)
    {
        $changes = $item->getChanges();
        if (! empty($changes)) {
            $this->logEvent($item->order_id, 'order_item.updated', $changes);
        }
    }

    public function deleted(OrderItem $item)
    {
        $this->logEvent($item->order_id, 'order_item.deleted', $item->toArray());
    }

    protected function logEvent(int $orderId, string $eventType, array $data)
    {
        OrderEvent::create([
            'order_id' => $orderId,
            'event_type' => $eventType,
            'event_data' => $data,
        ]);

        dispatch(new \App\Jobs\DispatchWebhookEvent($eventType, $data, $orderId));
    }
}
