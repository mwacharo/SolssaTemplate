<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // \App\Models\Vendor::class => \App\Policies\VendorPolicy::class,
        \App\Models\User::class => \App\Policies\VendorPolicy::class,

        \App\Models\Order::class => \App\Policies\OrderPolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        // \App\Models\Role::class => \App\Policies\UserRolePermissionPolicy::class,
        // \App\Models\ProductStock::class => \App\Policies\ProductStockPolicy::class,


    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
