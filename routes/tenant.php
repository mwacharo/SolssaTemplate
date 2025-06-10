<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {


    // Public routes
    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('home');


    // Login and Register routes for guests
    // Route::middleware(['guest'])->group(function () {
    //     Route::get('/login', fn() => Inertia::render('Auth/Login'))->name('login');
    //     Route::get('/register', fn() => Inertia::render('Auth/Register'))->name('register');
    // });

    // Testing cache route
    Route::get('/test-cache', function () {
        Cache::put('test_key', 'Hello from ' . tenant('id'), 60);
        return Cache::get('test_key');
    });

    // Authenticated user routes
    Route::get('/dashboard', fn() => Inertia::render('Dashboard', [
        'tenant_id' => tenant('id'),
        'user' => auth()->user(),
    ]))->name('dashboard')->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified']);

    // Settings routes
    Route::prefix('settings')->group(function () {
        Route::get('/', fn() => Inertia::render('Settings/Index', ['tenant_id' => tenant('id')]))->name('settings');
        Route::get('/profile', fn() => Inertia::render('Settings/Profile', ['tenant_id' => tenant('id')]))->name('settings.profile');
        Route::get('/company', fn() => Inertia::render('Settings/Company', ['tenant_id' => tenant('id')]))->name('settings.company');
        Route::get('/features', fn() => Inertia::render('Settings/Features', ['tenant_id' => tenant('id')]))->name('settings.features');
        Route::get('/notifications', fn() => Inertia::render('Settings/Notifications', ['tenant_id' => tenant('id')]))->name('settings.notifications');
        Route::get('/branding', fn() => Inertia::render('Settings/Branding', ['tenant_id' => tenant('id')]))->name('settings.branding');
    });

    // Admin routes (auth protected)
    Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
        ->prefix('admin')->name('admin.')->group(function () {
            Route::get('/', fn() => Inertia::render('Admin/Dashboard', ['tenant_id' => tenant('id')]))->name('dashboard');
            Route::get('/permissions', fn() => Inertia::render('Admin/Permissions/Index', ['tenant_id' => tenant('id')]))->name('permissions.index');
            Route::get('/users', fn() => Inertia::render('Admin/Users/Index', ['tenant_id' => tenant('id')]))->name('users');
        });

    // Other tenant routes
    Route::get('/reports', fn() => Inertia::render('Reports/Index', ['tenant_id' => tenant('id')]))->name('reports');
    Route::get('/integrations', fn() => Inertia::render('Integrations/Index', ['tenant_id' => tenant('id')]))->name('integrations');
    Route::get('/billing', fn() => Inertia::render('Billing/Index', ['tenant_id' => tenant('id')]))->name('billing');
    Route::get('/notifications', fn() => Inertia::render('Notifications/Index', ['tenant_id' => tenant('id')]))->name('notifications');
    Route::get('/support', fn() => Inertia::render('Support/Index', ['tenant_id' => tenant('id')]))->name('support');
    Route::get('/audit', fn() => Inertia::render('Audit/Index', ['tenant_id' => tenant('id')]))->name('audit');
    Route::get('/developer', fn() => Inertia::render('Developer/Index', ['tenant_id' => tenant('id')]))->name('developer');
});
