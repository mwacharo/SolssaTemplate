<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderEvent;

class OrderObserver
{
    public function created(Order $order)
    {
        $this->logEvent($order, 'order.created', $order->toArray());
    }

    public function updated(Order $order)
    {
        $changes = $order->getChanges();
        if (! empty($changes)) {
            $this->logEvent($order, 'order.updated', $changes);
        }
    }

    public function deleted(Order $order)
    {
        $this->logEvent($order, 'order.deleted', $order->toArray());
    }

    protected function logEvent(Order $order, string $eventType, array $data)
    {
        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => $eventType,
            'event_data' => $data,
        ]);

        // Dispatch webhook job
        dispatch(new \App\Jobs\DispatchWebhookEvent($eventType, $data, $order->id));
    }
}
