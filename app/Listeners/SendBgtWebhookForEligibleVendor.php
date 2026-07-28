<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Jobs\SendBgtOrderStatusWebhook;
use App\Models\OrderStatusTimestamp;
use Illuminate\Support\Facades\Log;

class SendBgtWebhookForEligibleVendor
{
    public function handle(OrderStatusChanged $event): void
    {
        $statusTimestamp = OrderStatusTimestamp::with('order')
            ->find($event->statusTimestampId);

        if (!$statusTimestamp) {
            Log::warning('BGT Webhook Listener: Status timestamp not found', [
                'status_timestamp_id' => $event->statusTimestampId,
            ]);

            return;
        }

        $order = $statusTimestamp->order;

        if (!$order) {
            Log::warning('BGT Webhook Listener: Order not found', [
                'status_timestamp_id' => $event->statusTimestampId,
            ]);

            return;
        }

        // Get the vendor ID that is allowed to use BGT webhook
        // $bgtVendorId = (int) config('services.bgt.vendor_id');

        $bgtVendorId = (int) env('BGT_VENDOR_ID');

        // Only dispatch BGT webhook for the configured vendor
        if ((int) $order->vendor_id !== $bgtVendorId) {
            Log::info('BGT Webhook Not Dispatched: Vendor not eligible', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'vendor_id' => $order->vendor_id,
                'bgt_vendor_id' => $bgtVendorId,
            ]);

            return;
        }

        Log::info('BGT Webhook Dispatched', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'vendor_id' => $order->vendor_id,
            'status_timestamp_id' => $event->statusTimestampId,
        ]);

        SendBgtOrderStatusWebhook::dispatch(
            $event->statusTimestampId
        );
    }
}
