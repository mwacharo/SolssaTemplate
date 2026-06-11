<?php

namespace App\Observers;

use App\Events\OrderStatusChanged;
use App\Models\OrderStatusTimestamp;
use App\Services\StockService;
use Illuminate\Support\Facades\Log;
use App\Models\User;

// use App\Events\OrderStatusChanged;


class OrderStatusTimestampObserver
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }



    public function created(OrderStatusTimestamp $statusTimestamp)
    {

        // new code to improve performance 

        $statusTimestamp->load([
            'status',

            'order' => function ($query) {
                $query->with([
                    'vendor',
                    'customer',
                    'zone',

                    'latest_status.status',

                    'orderItems.product',

                    'assignments.user',
                ]);
            }
        ]);
        $order = $statusTimestamp->order;

        $vendor = $order->vendor;

        if (!$vendor->hasService('warehousing')) {
            return;
        }

        $this->stockService->applyForStatus($statusTimestamp);



        // event(new OrderStatusChanged($statusTimestamp));

        // event(new OrderStatusChanged($statusTimestamp));

        $userId = auth()->id() ?? 1;



        OrderStatusChanged::dispatch([
            'status_timestamp_id' => $statusTimestamp->id,

            'status' => strtolower(
                $statusTimestamp->status?->name
                    ?? $statusTimestamp->status
            ),

            'order' => $order->toArray(),

            'vendor_id' => $vendor->id,

            'country_id' => $order->country_id,

            'customer_phone' =>
            $order->customer?->phone
                ?? $order->customer_phone,

            'user_id' => $userId,

        ]);
        // OrderStatusChanged::dispatch($statusTimestamp);
    }
}
