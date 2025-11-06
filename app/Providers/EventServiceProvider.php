<?php

namespace App\Providers;

use App\Models\OrderStatusTimestamp;
use Illuminate\Support\ServiceProvider;
use App\Observers\OrderStatusTimestampObserver;


class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //

                OrderStatusTimestamp::observe(OrderStatusTimestampObserver::class);

    }
}
