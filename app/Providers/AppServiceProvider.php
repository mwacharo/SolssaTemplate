<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;



// use Spatie\Permission\Models\Permission as SpatiePermission;
use App\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //

          $this->app->bind(
        \App\Repositories\Interfaces\UserRepositoryInterface::class,
        \App\Repositories\UserRepository::class
    );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        // 
            // SpatiePermission::resolveRelationUsing('model', fn () => Permission::class);

                Route::model('permission', Permission::class);


    }
}

