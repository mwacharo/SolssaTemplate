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
    $order = $statusTimestamp->order;
    // $status = strtolower($statusTimestamp->status?->name ?? '');
        $status = $statusTimestamp->status?->name ?? '';

    $statusTimestampId = $statusTimestamp->id;

    Log::info('OrderStatusTimestampObserver::created called', [
        'status_timestamp_id' => $statusTimestampId,
        'status' => $status,
        'order_id' => $order?->id,
    ]);

    if (!$order) {
        Log::warning('OrderStatusTimestampObserver::created - no order found', [
            'status_timestamp_id' => $statusTimestampId,
        ]);
        return;
    }

    $order->load('orderItems.product');

    foreach ($order->orderItems as $item) {
        match ($status) {
            'New'   => $this->stockService->reserveStock($item, $statusTimestampId),
            'Scheduled'   => $this->stockService->reserveStock($item, $statusTimestampId),
            'Pending', 'Cancelled' => $this->stockService->revertStock($item, $statusTimestampId ),
            'Delivered'  => $this->stockService->deductStock($item, $statusTimestampId),
            'Returned'   => $this->stockService->returnStock($item, $statusTimestampId),
            default      => null,
        };
    }
}


   
}
