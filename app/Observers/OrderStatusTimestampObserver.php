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
        $order = $statusTimestamp->order;

        $vendor = $order->vendor;

        if (!$vendor->hasService('warehousing')) {
            return;
        }

        $this->stockService->applyForStatus($statusTimestamp);



        event(new OrderStatusChanged($statusTimestamp));
        // OrderStatusChanged::dispatch($statusTimestamp);
    }
}
