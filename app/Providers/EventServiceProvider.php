<?php

namespace App\Providers;

use App\Models\OrderStatusTimestamp;
use Illuminate\Support\ServiceProvider;
use App\Observers\OrderStatusTimestampObserver;

use App\Listeners\SendOrderStatusToAdPlatforms;
use App\Events\OrderStatusChanged;


class EventServiceProvider extends ServiceProvider
{


    /**
     * The event to listener mappings.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderStatusChanged::class => [
            SendOrderStatusToAdPlatforms::class,
        ],
    ];
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
        //   SendOrderStatusToAdPlatforms::class,


    }
}
