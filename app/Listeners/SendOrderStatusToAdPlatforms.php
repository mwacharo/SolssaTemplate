<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
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

    /**
     * Statuses we care about for ad platform conversion tracking.
     * Matched by name so courier-specific IDs don't matter.
     */
    protected const TRACKABLE_STATUSES = [
        'New',
        'Scheduled',
        // 'Awaiting Dispatch',
        // 'In Transit',
        'Delivered',  // Purchase event
    ];

    public function __construct(
        OrderEventFactory $factory,
        VendorTrackingResolver $resolver,
        ConversionDispatcher $dispatcher
    ) {
        $this->factory    = $factory;
        $this->resolver   = $resolver;
        $this->dispatcher = $dispatcher;
    }

    public function handle(OrderStatusChanged $event): void
    {
        $status = $event->statusTimestamp;
        $order  = $status->order;

        // Assumes $status->status is the related Status model (eager-loaded or lazy)
        $statusName = $status->status->name ?? null;

        Log::info('Listener started', [
            'order_id'    => $order->id,
            'status_id'   => $status->status_id,
            'status_name' => $statusName,
        ]);

        // ✅ Filter by name — ID-agnostic, works across all couriers
        if (!in_array($statusName, self::TRACKABLE_STATUSES, strict: true)) {
            Log::info('Skipped status', [
                'status_id'   => $status->status_id,
                'status_name' => $statusName,
            ]);
            return;
        }

        $conversionEvent = $this->factory->fromStatus($status);

        Log::info('ConversionEvent built', [
            'event_name' => $conversionEvent->eventName,
            'event_id'   => $conversionEvent->eventId,
        ]);

        $config = $this->resolver->resolve($order);

        Log::info('Vendor config resolved', $config);

        $this->dispatcher->dispatch($conversionEvent, $config, $order);

        Log::info('Dispatch complete', ['order_id' => $order->id]);
    }

    public function failed(OrderStatusChanged $event, \Throwable $exception): void
    {
        Log::error('Tracking failed', [
            'order_id' => $event->statusTimestamp->order_id,
            'error'    => $exception->getMessage(),
        ]);
    }
}
