<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\OrderStatusTimestamp;
use App\Services\OrderEventFactory;
use App\Services\VendorTrackingResolver;
use App\Services\ConversionDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderStatusToAdPlatforms implements ShouldQueue
{
    use InteractsWithQueue;

    protected const TRACKABLE_STATUSES = [
        'new',
        'scheduled',
        'delivered',
    ];

    public function __construct(
        protected OrderEventFactory $factory,
        protected VendorTrackingResolver $resolver,
        protected ConversionDispatcher $dispatcher
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $statusTimestampId = $event->statusTimestampId;

        Log::info('Ad tracking listener started', [
            'status_timestamp_id' => $statusTimestampId,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Load status + related order data
        |--------------------------------------------------------------------------
        */

        $status = OrderStatusTimestamp::with([
            'status',
            'order.vendor',
            'order.customer',
            'order.orderItems.product',
        ])->find($statusTimestampId);

        if (!$status) {
            Log::warning('OrderStatusTimestamp not found', [
                'status_timestamp_id' => $statusTimestampId,
            ]);

            return;
        }

        $order = $status->order;

        if (!$order) {
            Log::warning('Order not found for status timestamp', [
                'status_timestamp_id' => $statusTimestampId,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve actual status from database
        |--------------------------------------------------------------------------
        */

        $statusName = strtolower(
            trim($status->status?->name ?? '')
        );

        Log::info('Ad tracking status resolved', [
            'order_id' => $order->id,
            'status_timestamp_id' => $status->id,
            'status_name' => $statusName,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Ignore statuses we don't want to send
        |--------------------------------------------------------------------------
        */

        if (!in_array($statusName, self::TRACKABLE_STATUSES, true)) {
            Log::info('Status not trackable for ad platforms', [
                'order_id' => $order->id,
                'status_name' => $statusName,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Build conversion event
        |--------------------------------------------------------------------------
        */

        $conversionEvent = $this->factory->fromStatus($status);

        Log::info('Conversion event built', [
            'order_id' => $order->id,
            'event_name' => $conversionEvent->eventName,
            'event_id' => $conversionEvent->eventId,
            'value' => $conversionEvent->value,
            'currency' => $conversionEvent->currency,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Resolve vendor tracking configuration
        |--------------------------------------------------------------------------
        */

        $config = $this->resolver->resolve($order);

        /*
        |--------------------------------------------------------------------------
        | Send to Meta / TikTok
        |--------------------------------------------------------------------------
        */

        $this->dispatcher->dispatch(
            $conversionEvent,
            $config,
            $order
        );

        Log::info('Conversion dispatched', [
            'order_id' => $order->id,
            'status_name' => $statusName,
            'event_name' => $conversionEvent->eventName,
        ]);
    }

    public function failed(
        OrderStatusChanged $event,
        Throwable $exception
    ): void {
        Log::error('Ad tracking failed', [
            'status_timestamp_id' => $event->statusTimestampId,
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}