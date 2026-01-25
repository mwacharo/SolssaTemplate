<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use Laravel\Horizon\Horizon;


use App\Jobs\SendWhatsAppMessageJob;
use App\Services\Zoho\ZohoAuthService;

use App\Services\Zoho\ZohoMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;



// Route::get('/zoho/callback', function (Request $request) {
//     // Display all query parameters for now
//     dd($request->all());
// });


Route::get('/zoho/callback', function (Request $request) {
    $code = $request->query('code');

    $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
        'grant_type' => 'authorization_code',
        'client_id' => env('ZOHO_CLIENT_ID'),
        'client_secret' => env('ZOHO_CLIENT_SECRET'),
        'redirect_uri' => 'http://127.0.0.1:8000/zoho/callback',
        'code' => $code,
    ]);

    $data = $response->json();

    dd($data); // Shows access_token & refresh_token
});

Route::get('/test-zoho', function (App\Services\Zoho\ZohoMailService $zoho) {
    return $zoho->sendEmail(
        "your-email@example.com",
        "Zoho API Test",
        "This email is from Zoho API!"
    );
});
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public home


// Dynamic online form route
// Route::get('/online-form/{order_no}', [OrderConfirmationController::class, 'showForm'])
//     ->name('online-form.show');




Route::get('/online-form/{order_no}', function ($order_no) {
    return Inertia::render('OnlineForm', [
        'order_no' => $order_no,
    ]);
})->name('online-form.show');

// tracking page route
Route::get('/track/{tracking_number}', function ($tracking_number) {
    return Inertia::render('TrackingPage', [
        'tracking_number' => $tracking_number,
    ]);
})->name('tracking.page');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');



Route::any('/register', function () {
    abort(403, 'User registration is disabled.');
});





// Route::get('/test-whatsapp-job', function () {
//     $delayMinutes = 2; // Delay interval between messages
//     $counter = 0;      // Progressive delay counter
//     $userId = 1;       // Replace with a valid user_id from your DB

//     $recipients = [
//         '254741821113@c.us', // Your number
//         '254701148928@c.us', // Example second number
//         '254700000000@c.us', // Example third number
//     ];



//     return "Dispatched " . count($recipients) . " jobs with {$delayMinutes} minute intervals";
// });

// Route::get('/login', function () {
//     return Inertia::render('Login', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// })->name('login');

// Route::get('/register', function () {
//     return Inertia::render('Auth/Register', []);
// })->name('register');


// Route::get('/', function () {

//     return Inertia::render('Welcome');
// })->name('home');


// Horizon routes


Horizon::auth(function ($request) {
    return true; // or use proper auth logic
});

// Authenticated user routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // Dashboard Main & Subviews
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/metrics', fn() => Inertia::render('Dashboard/Metrics'))->name('metrics');
        Route::get('/activity', fn() => Inertia::render('Dashboard/Activity'))->name('activity');
        Route::get('/status', fn() => Inertia::render('Dashboard/Status'))->name('status');
    });

    // Call Center
    Route::prefix('call-centre')->name('call-centre.')->group(function () {
        Route::get('/', fn() => Inertia::render('CallCenter/CallCentre'))->name('index');
        Route::get('/tickets', fn() => Inertia::render('CallCenter/Tickets'))->name('tickets');
        Route::get('/messages', fn() => Inertia::render('CallCenter/Messages'))->name('messages');
        Route::get('/whatsapp', fn() => Inertia::render('CallCenter/WhatsApp'))->name('whatsapp');
        Route::get('/emails', fn() => Inertia::render('CallCenter/Emails'))->name('emails');
        Route::get('/clients', fn() => Inertia::render('CallCenter/Clients'))->name('clients');
        Route::get('/contacts', fn() => Inertia::render('CallCenter/Contacts'))->name('contacts');
        Route::get('/telegram', fn() => Inertia::render('CallCenter/Telegram'))->name('telegram');
        Route::get('/notes', fn() => Inertia::render('CallCenter/Notes'))->name('notes');
    });



    // orders 


    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', fn() => Inertia::render('Orders/Index'))->name('index');
        Route::get('/shipping', fn() => Inertia::render('Orders/Shipping'))->name('shipping');
        Route::get('/scan-dispatch', fn() => Inertia::render('Orders/ScanDispatch'))->name('scan-dispatch');
        Route::get('/dispatch-list', fn() => Inertia::render('Orders/DispatchList'))->name('dispatch-list');
        Route::get('/ship', fn() => Inertia::render('Orders/Ship'))->name('ship');
        // new order form
        Route::get('/create', fn() => Inertia::render('Orders/OrderForm'))->name('create');
    });

    // Users & Teams
    Route::prefix('users')->name('users.')->group(function () {
        // Route::get('/', fn() => Inertia::render('Users/UserManagement'))->name('index');
        // Route::get('/roles', fn() => Inertia::render('Users/UserRoles'))->name('roles');
        Route::get('/', fn() => Inertia::render('Users/Users'))->name('index');

        Route::get('/roles', fn() => Inertia::render('Users/Roles'))->name('roles');

        Route::get('/permissions', fn() => Inertia::render('Users/Permissions'))->name('permissions');
        Route::get('/teams', fn() => Inertia::render('Users/Teams'))->name('teams');
        Route::get('/invitations', fn() => Inertia::render('Users/Invitations'))->name('invitations');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', fn() => Inertia::render('Reports/ViewReports'))->name('index');
        Route::get('/generate', fn() => Inertia::render('Reports/GenerateReport'))->name('generate');
        Route::get('/export', fn() => Inertia::render('Reports/Export'))->name('export');
        Route::get('/schedule', fn() => Inertia::render('Reports/Schedule'))->name('schedule');
    });

    // Branches
    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', fn() => Inertia::render('Branches/ViewBranches'))->name('index');
        Route::get('/create', fn() => Inertia::render('Branches/AddBranch'))->name('create');
        Route::get('/locations', fn() => Inertia::render('Branches/ManageLocations'))->name('locations');
        Route::get('/cities', fn() => Inertia::render('Branches/City'))->name('cities');
        Route::get('/zones', fn() => Inertia::render('Branches/Zone'))->name('zones');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', fn() => Inertia::render('Settings/Profile'))->name('profile');
        Route::get('/organization', fn() => Inertia::render('Settings/Organization'))->name('org');
        Route::get('/ivr', fn() => Inertia::render('Settings/IVROptions'))->name('ivr');
        Route::get('/integrations', fn() => Inertia::render('Settings/Integrations'))->name('integrations');
        Route::get('/templates', fn() => Inertia::render('Settings/Templates'))->name('templates');
        Route::get('/status', fn() => Inertia::render('Settings/Status'))->name('status');


        // expense settings routes
        Route::get('/expense-categories', fn() => Inertia::render('Settings/ExpenseCategories'))->name('expense-categories');
        Route::get('/expense-types', fn() => Inertia::render('Settings/ExpenseTypes'))->name('expense-types');



        // pricing settings routes
        Route::get('/pricing-services', fn() => Inertia::render('Settings/Pricing/Services'))->name('pricing-services');
        Route::get('/pricing-conditions', fn() => Inertia::render('Settings/Pricing/Conditions'))->name('pricing-conditions');
        Route::get('/vendor-assignments', fn() => Inertia::render('Settings/Pricing/VendorAssignments'))->name('vendor-assignments');
        Route::get('/vendor-overrides', fn() => Inertia::render('Settings/Pricing/VendorOverrides'))->name('vendor-overrides');

        // add call settings

        Route::get('/call-settings', fn() => Inertia::render('Settings/CallSettings'))->name('call-settings');
        Route::get('/features', fn() => Inertia::render('Settings/FeatureToggles'))->name('features');
        Route::get('/notifications', fn() => Inertia::render('Settings/Notifications'))->name('notifications');
        Route::get('/branding', fn() => Inertia::render('Settings/Branding'))->name('branding');
    });

    // Integrations
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/api', fn() => Inertia::render('Integrations/API'))->name('api');
        Route::get('/webhooks', fn() => Inertia::render('Integrations/Webhooks'))->name('webhooks');
        Route::get('/apps', fn() => Inertia::render('Integrations/ThirdPartyApps'))->name('apps');
        Route::get('/marketplace', fn() => Inertia::render('Integrations/Marketplace'))->name('marketplace');
        Route::get('/shopify', fn() => Inertia::render('Integrations/Shopifty'))->name('shopify');
    });

    // Billing
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/plans', fn() => Inertia::render('Billing/Plans'))->name('plans');
        Route::get('/payments', fn() => Inertia::render('Billing/Payments'))->name('payments');
        Route::get('/invoices', fn() => Inertia::render('Billing/Invoices'))->name('invoices');
        Route::get('/usage', fn() => Inertia::render('Billing/Usage'))->name('usage');
        Route::get('/licenses', fn() => Inertia::render('Billing/Licenses'))->name('licenses');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/center', fn() => Inertia::render('Notifications/Center'))->name('center');
        Route::get('/activity', fn() => Inertia::render('Notifications/ActivityTrail'))->name('activity');
        Route::get('/status', fn() => Inertia::render('Notifications/Status'))->name('status');
    });

    // Audit Logs
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/logins', fn() => Inertia::render('Audit/Logins'))->name('logins');
        Route::get('/actions', fn() => Inertia::render('Audit/UserActions'))->name('actions');
        Route::get('/system', fn() => Inertia::render('Audit/SystemChanges'))->name('system');
    });

    // Developer Tools
    Route::prefix('developer')->name('developer.')->group(function () {
        Route::get('/docs', fn() => Inertia::render('Developer/APIDocs'))->name('docs');
        Route::get('/sdks', fn() => Inertia::render('Developer/SDKs'))->name('sdks');
        Route::get('/sandbox', fn() => Inertia::render('Developer/Sandbox'))->name('sandbox');
        Route::get('/stats', fn() => Inertia::render('Developer/UsageStats'))->name('stats');
    });

    // Support
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/faqs', fn() => Inertia::render('Support/FAQs'))->name('faqs');
        Route::get('/contact', fn() => Inertia::render('Support/Contact'))->name('contact');
        Route::get('/chat', fn() => Inertia::render('Support/LiveChat'))->name('chat');
        Route::get('/ticket', fn() => Inertia::render('Support/Ticket'))->name('ticket');
    });

    // warehousing 


    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', fn() => Inertia::render('Warehouse/Index'))->name('index');
        Route::get('/products', fn() => Inertia::render('Products/Index'))->name('products');
        Route::get('/inventory', fn() => Inertia::render('Stock/Index'))->name('inventory');
        Route::get('/statistics', fn() => Inertia::render('Statistics/Index'))->name('statistics');
        Route::get('/category', fn() => Inertia::render('Products/ProductCategory'))->name('category');
        // Route::get('/suppliers', fn() => Inertia::render('Warehouse/Suppliers'))->name('suppliers');
        // Route::get('/shipments', fn() => Inertia::render('Warehouse/Shipments'))->name('shipments');
        // Route::get('/reports', fn() => Inertia::render('Warehouse/Reports'))->name('reports');
    });

    // vendor routes 
    // Vendor
    Route::get('/vendors', fn() => Inertia::render('Vendor/index'))->name('vendor.index');

    Route::prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/', fn() => Inertia::render('Vendor/Dashboard'))->name('dashboard');
        Route::get('/products', fn() => Inertia::render('Vendor/Products'))->name('products');
        Route::get('/orders', fn() => Inertia::render('Vendor/Orders'))->name('orders');
        Route::get('/invoices', fn() => Inertia::render('Vendor/Invoices'))->name('invoices');
        Route::get('/settings', fn() => Inertia::render('Vendor/Settings'))->name('settings');
    });


    // Expedition
    Route::prefix('expedition')->name('expedition.')->group(function () {
        Route::get('/', fn() => Inertia::render('Expedition/Index'))->name('index');
    });

    // Seller Expenses
    Route::prefix('seller-expenses')->name('seller-expenses.')->group(function () {
        Route::get('/', fn() => Inertia::render('SellerExpenses/Index'))->name('index');
    });

    // Remittances
    Route::prefix('remittances')->name('remittances.')->group(function () {
        Route::get('/', fn() => Inertia::render('Remittances/Index'))->name('index');
        // call agents payment 
        Route::get('/agent-payments', fn() => Inertia::render('Remittances/AgentPayments'))->name('agent-payments');

        // rider payments
        Route::get('/rider-payments', fn() => Inertia::render('Remittances/RiderPayments'))->name('rider-payments');
    });
});

// Admin routes (with optional role middleware)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    // 'role:admin', // Uncomment when you have role middleware
])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn() => Inertia::render('Admin/Dashboard'))->name('dashboard');
    Route::get('/users', fn() => Inertia::render('Admin/Users/Index'))->name('users');
    Route::get('/permissions', fn() => Inertia::render('Admin/Permissions/Index'))->name('permissions');
});

// Optional: Redirect old routes to new ones for backward compatibility
// Route::redirect('/callcenter', '/call-centre')->permanent();
// Route::redirect('/user', '/users')->permanent();
// Route::redirect('/branches/add', '/branches/create')->permanent();