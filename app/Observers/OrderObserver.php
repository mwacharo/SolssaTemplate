<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderEvent;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        $actorId = Auth::check() ? Auth::id() : null;

        Log::info('Order event actor ID', ['actor_id' => $actorId]);





        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => $eventType,
            'event_data' => $data,
            'actor_id'   => $actorId,

        ]);

        // Dispatch webhook job
        // dispatch(new \App\Jobs\DispatchWebhookEvent($eventType, $data, $order->id));
    }
}
