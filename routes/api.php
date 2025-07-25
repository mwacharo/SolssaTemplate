<?php

use App\Http\Controllers\Api\Admin\UserRolePermissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuditLogController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CredentialController;
use App\Http\Controllers\Api\Integrations\GoogleSheetController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\ClientController;
// use App\Http\Controllers\Api\VendorController;
// Make sure only one VendorController exists and is imported. If you have multiple, remove or rename the duplicate.
use App\Http\Controllers\Api\VendorController;

// Route::apiResource('/v1/admin/permissions', \App\Http\Controllers\Api\Admin\PermissionController::class)->except(['show', 'update']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// routes/api.php


Route::prefix('v1')->group(function () {
        // Route::apiResource('/google-sheets', GoogleSheetController::class);

    Route::get('/google-sheets', [GoogleSheetController::class, 'index'])->name('google-sheets.index');
    Route::get('/google-sheets/{id}', [GoogleSheetController::class, 'show'])->name('google-sheets.show');
    Route::post('/google-sheets', [GoogleSheetController::class, 'store'])->name('google-sheets.store');
    Route::put('/google-sheets/{id}', [GoogleSheetController::class, 'update'])->name('google-sheets.update');
    Route::delete('/google-sheets/{id}', [GoogleSheetController::class, 'destroy'])->name('google-sheets.destroy');

    Route::post('/google-sheets/{id}/read-sheet', [GoogleSheetController::class, 'uploadOrders']);
    // Route::post('v1/google-sheets/{id}/sync-orders', [ApiGoogleSheetController::class, 'updateSheet']);
    Route::post('/google-sheets/{id}/sync-products', [GoogleSheetController::class, 'syncProducts']);



    // Order APIs
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']); // List all orders
    // Route::post('v1/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']); // Create a new order
    Route::get('/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']); // Show a specific order
    Route::put('v1/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'update']); // Update a specific order

    // Print waybill
    Route::get('/orders/{id}/print-waybill', [OrderController::class, 'printWaybill']);
    Route::get('/{id}/print-waybill', [OrderController::class, 'printWaybill'])->name('orders.print-waybill');
    Route::get('/{id}/download-waybill', [OrderController::class, 'downloadWaybill'])->name('orders.download-waybill');
    Route::get('/{id}/preview-waybill', [OrderController::class, 'previewWaybill'])->name('orders.preview-waybill');
    Route::post('/bulk-print-waybills', [OrderController::class, 'bulkPrintWaybills'])->name('orders.bulk-print-waybills');

    // Route::delete('v1/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'destroy']); // Delete a specific order
    // Route::get('v1/orders/{id}/products', [\App\Http\Controllers\Api\OrderController::class, 'getOrderProducts']); // Get products for a specific order
    // Route::post('v1/orders/{id}/products', [\App\Http\Controllers\Api\OrderController::class, 'addOrderProducts']); // Add products to a specific order
    // Route::put('v1/orders/{id}/products', [\App\Http\Controllers\Api\OrderController::class, 'updateOrderProducts']); // Update products for a specific order
    // Route::delete('v1/orders/{id}/products/{productId}', [\App\Http\Controllers\Api\OrderController::class, 'removeOrderProduct']); // Remove a product from a specific order
    // Route::get('v1/orders/{id}/status', [\App\Http\Controllers\Api\OrderController::class, 'getOrderStatus']); // Get status of a specific order
    // Route::put('v1/orders/{id}/status', [\App\Http\Controllers\Api\OrderController::class, 'updateOrderStatus']); // Update status of a specific order
    // Route::get('v1/orders/{id}/tracking', [\App\Http\Controllers\Api\OrderController::class, 'getOrderTracking']); // Get tracking information for a specific order
    // Route::put('v1/orders/{id}/tracking', [\App\Http\Controllers\Api\OrderController::class, 'updateOrderTracking']); // Update tracking information for a specific order
    // Route::get('v1/orders/{id}/history', [\App\Http\Controllers\Api\OrderController::class, 'getOrderHistory']); // Get history of a specific order
    // Route::get('v1/orders/{id}/invoice', [\App\Http\Controllers\Api\OrderController::class, 'generateInvoice']); // Generate invoice for a specific order
    // Route::get('v1/orders/{id}/receipt', [\App\Http\Controllers\Api\OrderController::class, 'generateReceipt']); // Generate receipt for a specific order
    // Route::get('v1/orders/{id}/return', [\App\Http\Controllers\Api\OrderController::class, 'requestReturn']); // Request return for a specific order
    // Route::post('v1/orders/{id}/return', [\App\Http\Controllers\Api\OrderController::class, 'processReturn']); // Process return for a specific order
    // Route::get('v1/orders/{id}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'requestCancel']); // Request cancel for a specific order
    // Route::post('v1/orders/{id}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'processCancel']); // Process cancel for a specific order
    // Route::get('v1/orders/{id}/shipping-label', [\App\Http\Controllers\Api\OrderController::class, 'generateShippingLabel']); // Generate shipping label for a specific order
    // Route::get('v1/orders/{id}/tracking-info', [\App\Http\Controllers\Api\OrderController::class, 'getTrackingInfo']); // Get tracking info for a specific order
    // Route::get('v1/orders/{id}/client', [\App\Http\Controllers\Api\OrderController::class, 'getOrderClient']); // Get client information for a specific order
    // Route::put('v1/orders/{id}/client', [\App\Http\Controllers\Api\OrderController::class, 'updateOrderClient']); // Update client information for a specific order
    // Route::get('v1/orders/{id}/vendor', [\App\Http\Controllers\Api\OrderController::class, 'getOrderVendor']); // Get vendor information for a specific order
    // Route::put('v1/orders/{id}/vendor', [\App\Http\Controllers\Api\OrderController::class, 'updateOrderVendor']); // Update vendor information for a specific order
    // Route::get('v1/orders/{id}/warehouse', [\App\Http\Controllers\Api\OrderController::class, 'getOrderWarehouse']); // Get warehouse information for a specific order
    // Route::put('v1/orders/{id}/warehouse', [\App\Http\Controllers\Api\OrderController::class, 'updateOrderWarehouse']); // Update warehouse information for a specific order
    // Route::get('v1/orders/{id}/payment', [\App\Http\Controllers\Api\OrderController::class, 'getOrderPayment']); // Get payment information for a specific order
    // Route::put('v1/orders/{id}/payment', [\App\Http\Controllers\Api\OrderController::class, 'updateOrderPayment']); // Update payment information for a specific order
    // Route::get('v1/orders/{id}/notes', [\App\Http\Controllers\Api\OrderController::class, 'getOrderNotes']); // Get notes for a specific order
    // Route::post('v1/orders/{id}/notes', [\App\Http\Controllers\Api\OrderController::class, 'addOrderNotes']); // Add notes to a specific order
    // Route::put('v1/orders/{id}/notes', [\App\Http\Controllers\Api\OrderController::class, 'updateOrderNotes']); // Update notes for a specific order
    // Route::delete('v1/orders/{id}/notes/{noteId}', [\App\Http\Controllers\Api\OrderController::class, 'removeOrderNote']); // Remove a note from a specific order
    // Route::get('v1/orders/{id}/summary', [\App\Http\Controllers\Api\OrderController::class, 'getOrderSummary']); // Get summary of a specific order
    // Route::get('v1/orders/{id}/export', [\App\Http\Controllers\Api\OrderController::class, 'exportOrder']); // Export a specific order
    // Route::get('v1/orders/{id}/import', [\App\Http\Controllers\Api\OrderController::class, 'importOrder']); // Import a specific order
    // Route::get('v1/orders/{id}/export-template', [\App\Http\Controllers\Api\OrderController::class, 'exportOrderTemplate']); // Export order template for a specific order
    // Route::post('v1/orders/{id}/import-template', [\App\Http\Controllers\Api\OrderController::class, 'importOrderTemplate']); // Import order template for a specific order
    // Route::get('v1/orders/{id}/status-history', [\App\Http\Controllers\Api\OrderController::class, 'getOrderStatusHistory']); // Get status history for a specific order


    Route::post('/assign-rider', [OrderController::class, 'assignRider']);
    Route::post('/assign-agent', [OrderController::class, 'assignAgent']);
    Route::post('/update-status', [OrderController::class, 'updateStatus']);



    // Template APIs

    Route::get('/templates', [TemplateController::class, 'index']); // List all templates
    Route::post('/templates', [TemplateController::class, 'store']); // Create a new template
    Route::get('/templates/{id}', [TemplateController::class, 'show']); // Show a specific template
    Route::put('/templates/{id}', [TemplateController::class, 'update']); // Update a specific template
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy']); // Delete a specific template



    // Contacts APIs
    Route::get('/contacts', [ContactController::class, 'index']);       // List all contacts
    Route::post('/contacts', [ContactController::class, 'store']);       // Create new contact
    Route::get('/contacts/{contact}', [ContactController::class, 'show']); // Show single contact
    Route::put('/contacts/{contact}', [ContactController::class, 'update']); // Update contact
    Route::patch('contacts/{contact}', [ContactController::class, 'update']); // Also support patch
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']); // Delete contact




    // fetchCredentials
    Route::get('/channel-credentials', [CredentialController::class, 'index']);
    Route::get('/fetch-credentials', [CredentialController::class, 'fetchCredentials']);
    Route::get('/credentialable-types', [CredentialController::class, 'getCredentialableTypes']);
    Route::get('/credentialables', [CredentialController::class, 'getOwnersByType']);


    Route::post('/channel-credentials', [CredentialController::class, 'store']);
    Route::get('/channel-credentials/{id}', [CredentialController::class, 'show']);
    Route::put('/channel-credentials/{id}', [CredentialController::class, 'update']);
    Route::delete('/channel-credentials/{id}', [CredentialController::class, 'destroy']);


    // rider 

    Route::get('/riders', [RiderController::class, 'index']);
    Route::post('/riders', [RiderController::class, 'store']);
    Route::get('/riders/{id}', [RiderController::class, 'show']);
    Route::put('/riders/{id}', [RiderController::class, 'update']);
    Route::delete('/riders/{id}', [RiderController::class, 'destroy']);


    // Agennt 
    Route::get('/agents', [AgentController::class, 'index']);
    Route::post('/agents', [AgentController::class, 'store']);
    Route::get('/agents/{id}', [AgentController::class, 'show']);
    Route::put('/agents/{id}', [AgentController::class, 'update']);
    Route::delete('/agents/{id}', [AgentController::class, 'destroy']);


    // vendor 
    Route::get('/vendors', [VendorController::class, 'index']);
    Route::post('/vendors', [VendorController::class, 'store']);
    Route::get('/vendors/{id}', [VendorController::class, 'show']);
    Route::put('/vendors/{id}', [VendorController::class, 'update']);
    Route::delete('/vendors/{id}', [VendorController::class, 'destroy']);

    // clients 

    // For clients (you'll need to create these)
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::put('/clients/{id}', [ClientController::class, 'update']);
    Route::delete('/clients/{id}', [ClientController::class, 'destroy']);




    // webwhats    webhook 
    // Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'receive']);
    // Route::post('/whatsapp-send', [WhatsAppController::class, 'send']);
    Route::get('/whatsapp-messages', [WhatsAppController::class, 'listConversations']);
    // Route::get('messages/chat/{phone}', [WhatsAppController::class, 'getChat']);
    Route::delete('/whatsapp-messages/{id}', [WhatsAppController::class, 'destroy']);
    Route::get('/whatsapp/state', [WhatsAppController::class, 'getState']);
    Route::post('/whatsapp/send-message', [WhatsAppController::class, 'sendMessage']);
    Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);
    // Route::get('/conversations', [WhatsAppController::class, 'listConversations']);
    Route::get('/conversation/{chatId}', [WhatsAppController::class, 'getConversation']);
    Route::post('/whatsapp/retry-message/{id}', [WhatsAppController::class, 'retryMessage']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('roles', \App\Http\Controllers\Api\Admin\RoleController::class)->except('show');
        Route::apiResource('permissions', \App\Http\Controllers\Api\Admin\PermissionController::class)->except(['show']);
        Route::apiResource('users', \App\Http\Controllers\Api\Admin\UserController::class);
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::post('/{id}/force-password', [UserController::class, 'forceChangePassword']);
        Route::post('/reset-link', [UserController::class, 'sendResetLink']);
        Route::get('audit-logs', [\App\Http\Controllers\Api\Admin\AuditLogController::class, 'index'])->name('audit.logs');



        Route::prefix('users/{user}')->name('users.')->group(function () {
            Route::post('assign-role', [UserRolePermissionController::class, 'assignRole'])->name('assignRole');
            Route::post('remove-role', [UserRolePermissionController::class, 'removeRole'])->name('removeRole');
            Route::post('assign-permission', [UserRolePermissionController::class, 'assignPermission'])->name('assignPermission');
            Route::post('remove-permission', [UserRolePermissionController::class, 'removePermission'])->name('removePermission');
            Route::get('permissions', [UserRolePermissionController::class, 'getUserPermissions'])->name('permissions');
        });
    });
});
