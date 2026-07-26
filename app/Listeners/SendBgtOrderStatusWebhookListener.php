<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\OrderStatusChanged;
use App\Jobs\SendBgtOrderStatusWebhook;

use Illuminate\Support\Facades\Log;


class SendBgtOrderStatusWebhookListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    // /**
    //  * Handle the event.
    //  */
    // public function handle(object $event): void
    // {
    //     //
    // }



    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        Log::info('BGT webhook listener triggered', [
            'status_timestamp_id' => $event->statusTimestampId,
        ]);

        SendBgtOrderStatusWebhook::dispatch(
            $event->statusTimestampId
        );
    }
}
