<?php

namespace App\Services;

use App\Models\OrderStatusTimestamp;

class OrderEventFactory
{
    protected const STATUS_EVENT_MAP = [
        // 'New'  => 'InitiateCheckout',
        // 'Scheduled'  => 'AddToCart' || 'Lead', // depending on your funnel

        'Scheduled'  =>  'Lead', // depending on your funnel

        // 'In Transit' => 'AddPaymentInfo',
        'Delivered'  => 'Purchase',
    ];


    protected const CURRENCY_MAP = [
        'KSH' => 'KES',
        'KSHS' => 'KES',
        'KES' => 'KES',

        'TSH' => 'TZS',
        'TZS' => 'TZS',

        'UGX' => 'UGX',
        'GHS' => 'GHS',
        'NGN' => 'NGN',
        'ZMW' => 'ZMW',
        'USD' => 'USD',
    ];

    public function fromStatus(OrderStatusTimestamp $status): ConversionEvent
    {
        $order      = $status->order;
        $statusName = $status->status->name ?? '';

        return new ConversionEvent(
            eventName: $this->mapEventName($statusName),
            eventId: $order->id . '-' . $status->id . '-' . $statusName,
            value: (float) ($order->total_price ?? 0),


            // currency: $order->currency ?? 'KES',

            // Always send normalized ISO currency
            currency: $this->normalizeCurrency(
                $order->currency
                    ?? $order->country?->currency
                    ?? 'KES'
            ),

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


    // newline 


    private function normalizeCurrency(?string $currency): string
    {
        $currency = strtoupper(trim($currency ?? ''));

        return self::CURRENCY_MAP[$currency] ?? $currency ?: 'KES';
    }


    public static function isTrackable(string $statusName): bool
    {
        return array_key_exists($statusName, self::STATUS_EVENT_MAP);
    }
}
