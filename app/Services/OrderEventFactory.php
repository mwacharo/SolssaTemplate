<?php

namespace App\Services;

use App\Models\OrderStatusTimestamp;

class OrderEventFactory
{
    protected const STATUS_EVENT_MAP = [
        'New'  => 'InitiateCheckout',
        'Scheduled'  => 'AddToCart' || 'Lead', // depending on your funnel
        // 'In Transit' => 'AddPaymentInfo',
        'Delivered'  => 'Purchase',
    ];

    public function fromStatus(OrderStatusTimestamp $status): ConversionEvent
    {
        $order      = $status->order;
        $statusName = $status->status->name ?? '';

        return new ConversionEvent(
            eventName: $this->mapEventName($statusName),
            eventId: $order->id . '-' . $status->id . '-' . $statusName,
            value: (float) ($order->total_amount ?? 0),
            currency: $order->currency ?? 'KES',
            orderId: $order->id,
            email: $order->customer_email,
            phone: $order->customer_phone,
            timestamp: time(),
        );
    }

    private function mapEventName(string $statusName): string
    {
        return self::STATUS_EVENT_MAP[$statusName] ?? 'CustomEvent';
    }

    public static function isTrackable(string $statusName): bool
    {
        return array_key_exists($statusName, self::STATUS_EVENT_MAP);
    }
}
