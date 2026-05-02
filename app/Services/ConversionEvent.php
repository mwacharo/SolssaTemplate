<?php

namespace App\Services;

class ConversionEvent
{
    /**
     * Create a new class instance.
     */
    public function __construct(


        public string $eventName,
        public string $eventId,
        public float $value,
        public string $currency,
        public ?int $orderId = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?int $timestamp = null,
    ) {
        //
    }
}
