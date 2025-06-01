<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Authenticated user routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Add other authenticated user routes here
    // Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    // Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});

// Admin routes with additional middleware
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    // 'role:admin', // Uncomment if using Spatie Laravel Permission or similar
    // 'can:access-admin', // Alternative permission-based approach
])->prefix('admin')->name('admin.')->group(function () {

    // Admin dashboard
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // Role management
    // Route::get('/roles', function () {
    //     return Inertia::render('Admin/Roles/Index');
    // })->name('roles.index');

    // Permission management
    Route::get('/permissions', function () {
        return Inertia::render('Admin/Permissions/Index');
    })->name('permissions.index');

    // User management
    // Route::get('/users', function () {
    //     return Inertia::render('Admin/Users/Index');
    // })->name('users.index');

    // Route::get('/users', function () {
    //     return Inertia::render('Admin/Users/Index');
    // })->name('users');


    Route::get('/users', fn () => Inertia::render('Admin/Users/Index'))->name('users');


    // Additional admin routes can be added here
    // Route::resource('posts', AdminPostController::class);
    // Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
});

// API routes (if needed)
// Route::prefix('api')->middleware('api')->group(function () {
//     Route::middleware('auth:sanctum')->group(function () {
//         // Authenticated API routes
//     });
// });




// Dashboard
Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');

// Settings
Route::prefix('settings')->group(function () {
    Route::get('/', fn() => Inertia::render('Settings/Index'))->name('settings');
    Route::get('/profile', fn() => Inertia::render('Settings/Profile'))->name('settings.profile');
    Route::get('/company', fn() => Inertia::render('Settings/Company'))->name('settings.company');
    Route::get('/features', fn() => Inertia::render('Settings/Features'))->name('settings.features');
    Route::get('/notifications', fn() => Inertia::render('Settings/Notifications'))->name('settings.notifications');
    Route::get('/branding', fn() => Inertia::render('Settings/Branding'))->name('settings.branding');
});

// Reports
Route::get('/reports', fn() => Inertia::render('Reports/Index'))->name('reports');

// Integrations
Route::get('/integrations', fn() => Inertia::render('Integrations/Index'))->name('integrations');

// Users, Teams, Roles
// Route::get('/users', fn () => Inertia::render('Users/Index'))->name('users');
// Route::get('/admin/users', fn () => Inertia::render('Users/Index'))->name('users');


// Billing & Subscription
Route::get('/billing', fn() => Inertia::render('Billing/Index'))->name('billing');

// Notifications & Logs
Route::get('/notifications', fn() => Inertia::render('Notifications/Index'))->name('notifications');

// Help & Support
Route::get('/support', fn() => Inertia::render('Support/Index'))->name('support');

// Audit / Logs
Route::get('/audit', fn() => Inertia::render('Audit/Index'))->name('audit');

// Developer Tools
Route::get('/developer', fn() => Inertia::render('Developer/Index'))->name('developer');
