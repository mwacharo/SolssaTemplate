<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        
        // Public routes
        // Route::get('/', function () {
        //     return Inertia::render('Welcome', [
        //         'canLogin' => Route::has('login'),
        //         'canRegister' => Route::has('register'),
        //         'laravelVersion' => Application::VERSION,
        //         'phpVersion' => PHP_VERSION,
        //     ]);
        // })->name('home');

    //     // Help & Support (public)
    //     Route::get('/support', fn() => Inertia::render('Support/Index'))->name('support');

    //     // Authenticated routes
    //     Route::middleware([
    //         'auth:sanctum',
    //         config('jetstream.auth_session'),
    //         'verified',
    //     ])->group(function () {
            
    //         // Dashboard
    //         Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');

    //         // Settings
    //         Route::prefix('settings')->name('settings.')->group(function () {
    //             Route::get('/', fn() => Inertia::render('Settings/Index'))->name('index');
    //             Route::get('/profile', fn() => Inertia::render('Settings/Profile'))->name('profile');
    //             Route::get('/company', fn() => Inertia::render('Settings/Company'))->name('company');
    //             Route::get('/features', fn() => Inertia::render('Settings/Features'))->name('features');
    //             Route::get('/notifications', fn() => Inertia::render('Settings/Notifications'))->name('notifications');
    //             Route::get('/branding', fn() => Inertia::render('Settings/Branding'))->name('branding');
    //         });

    //         // Reports
    //         Route::get('/reports', fn() => Inertia::render('Reports/Index'))->name('reports');

    //         // Integrations
    //         Route::get('/integrations', fn() => Inertia::render('Integrations/Index'))->name('integrations');

    //         // Billing & Subscription
    //         Route::get('/billing', fn() => Inertia::render('Billing/Index'))->name('billing');

    //         // Notifications & Logs
    //         Route::get('/notifications', fn() => Inertia::render('Notifications/Index'))->name('notifications');

    //         // Audit / Logs
    //         Route::get('/audit', fn() => Inertia::render('Audit/Index'))->name('audit');

    //         // Developer Tools
    //         Route::get('/developer', fn() => Inertia::render('Developer/Index'))->name('developer');
    //     });

    //     // Admin-only routes
    //     Route::middleware([
    //         'auth:sanctum',
    //         config('jetstream.auth_session'),
    //         'verified',
    //         // 'role:admin', // Uncomment if using Spatie Laravel Permission
    //         // 'can:access-admin', // Alternative permission-based approach
    //     ])->prefix('admin')->name('admin.')->group(function () {
            
    //         // Admin dashboard
    //         Route::get('/', fn() => Inertia::render('Admin/Dashboard'))->name('dashboard');

    //         // User management
    //         Route::get('/users', fn() => Inertia::render('Admin/Users/Index'))->name('users');

    //         // Role management
    //         Route::get('/roles', fn() => Inertia::render('Admin/Roles/Index'))->name('roles.index');

    //         // Permission management
    //         Route::get('/permissions', fn() => Inertia::render('Admin/Permissions/Index'))->name('permissions.index');

    //         // Analytics
    //         Route::get('/analytics', fn() => Inertia::render('Admin/Analytics/Index'))->name('analytics.index');

    //         // Additional admin routes can be added here
    //         // Route::resource('posts', AdminPostController::class);
    //     });

    //     // API routes (if needed)
    //     Route::prefix('api')->middleware('api')->group(function () {
    //         Route::middleware('auth:sanctum')->group(function () {
    //             // Authenticated API routes go here
    //         });
    //     });
     });
}