<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Auth;



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

          $this->app->make(PermissionRegistrar::class)->setPermissionsTeamId(
        Auth::user()?->currentTeam?->id
    );
        // 
            // SpatiePermission::resolveRelationUsing('model', fn () => Permission::class);

                Route::model('permission', Permission::class);


    }
}

