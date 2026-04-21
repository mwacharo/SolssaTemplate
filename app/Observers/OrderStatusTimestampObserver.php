<?php

namespace App\Observers;

use App\Models\OrderStatusTimestamp;
use App\Services\StockService;
use Illuminate\Support\Facades\Log;

class OrderStatusTimestampObserver
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }







    public function created(OrderStatusTimestamp $statusTimestamp)
    {
        $this->stockService->applyForStatus($statusTimestamp);
    }
}
