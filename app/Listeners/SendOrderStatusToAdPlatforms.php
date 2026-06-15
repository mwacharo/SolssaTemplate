<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\OrderStatusTimestamp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

use App\Services\OrderEventFactory;
use App\Services\VendorTrackingResolver;
use App\Services\ConversionDispatcher;

class SendOrderStatusToAdPlatforms implements ShouldQueue
{
    use InteractsWithQueue;

    protected OrderEventFactory $factory;
    protected VendorTrackingResolver $resolver;
    protected ConversionDispatcher $dispatcher;

    protected const TRACKABLE_STATUSES = [
        'new',
        'scheduled',
        'delivered',
        
    ];

    public function __construct(
        OrderEventFactory $factory,
        VendorTrackingResolver $resolver,
        ConversionDispatcher $dispatcher
    ) {
        $this->factory = $factory;
        $this->resolver = $resolver;
        $this->dispatcher = $dispatcher;
    }

    public function handle(OrderStatusChanged $event): void
    {
        $payload = $event->payload;

        $statusName = strtolower($payload['status'] ?? '');

        Log::info('Ad tracking listener started', [
            'order_id' => $payload['order']['id'] ?? null,
            'status_name' => $statusName,
            'status_timestamp_id' => $payload['status_timestamp_id'] ?? null,
        ]);

        if (!in_array($statusName, self::TRACKABLE_STATUSES, true)) {
            Log::info('Status not trackable', [
                'status_name' => $statusName,
            ]);

            return;
        }

        $status = OrderStatusTimestamp::with([
            'status',
            'order.vendor',
            'order.customer',
        ])->find($payload['status_timestamp_id']);

        if (!$status) {
            Log::warning('OrderStatusTimestamp not found', [
                'status_timestamp_id' => $payload['status_timestamp_id'],
            ]);

            return;
        }

        $order = $status->order;

        $conversionEvent = $this->factory->fromStatus($status);

        Log::info('Conversion event built', [
            'event_name' => $conversionEvent->eventName,
            'event_id' => $conversionEvent->eventId,
        ]);

        $config = $this->resolver->resolve($order);

        $this->dispatcher->dispatch(
            $conversionEvent,
            $config,
            $order
        );

        Log::info('Conversion dispatched', [
            'order_id' => $order->id,
        ]);
    }

    public function failed(
        OrderStatusChanged $event,
        \Throwable $exception
    ): void {
        Log::error('Ad tracking failed', [
            'order_id' => $event->payload['order']['id'] ?? null,
            'status_timestamp_id' => $event->payload['status_timestamp_id'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
