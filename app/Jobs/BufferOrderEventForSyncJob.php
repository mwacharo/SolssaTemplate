<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Order;


class BufferOrderEventForSyncJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
    //  */
    // public function __construct()
    // {
    //     //
    // }

    /**
     * Execute the job.
     */
    // public function handle(): void
    // {
    //     //
    // }\

    public function __construct(
        public int $orderId
    ) {}



    public function handle(): void
    {
        $order = Order::find($this->orderId);

        if (! $order) return;

        $vendorId = $order->vendor_id;

        // Store only latest state in cache (overwrite-safe)
        cache()->put(
            "google_sync_buffer:{$vendorId}:{$order->id}",
            [
                'order_id' => $order->id,
                'vendor_id' => $vendorId,
                'payload' => [
                    'status' => $order->status,
                    'tracking_number' => $order->tracking_number,
                    'updated_at' => now()->toDateTimeString(),
                ]
            ],
            now()->addMinutes(10)
        );

        // Schedule batch flush (debounced)
        dispatch(new FlushGoogleSheetBufferJob($vendorId))
            ->delay(now()->addMinutes(1));
    }
}
