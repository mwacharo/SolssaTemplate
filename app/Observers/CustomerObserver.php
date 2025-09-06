<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\OrderEvent;

class CustomerObserver
{
    public function updated(Customer $customer)
    {
        $changes = $customer->getChanges();
        if (! empty($changes)) {
            $this->logEvent(null, 'customer.updated', $changes);
        }
    }

    protected function logEvent(?int $orderId, string $eventType, array $data)
    {
        OrderEvent::create([
            'order_id' => $orderId,
            'event_type' => $eventType,
            'event_data' => $data,
        ]);

        dispatch(new \App\Jobs\DispatchWebhookEvent($eventType, $data, $orderId));
    }
}
