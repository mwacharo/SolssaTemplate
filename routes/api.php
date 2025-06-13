<?php

use App\Http\Controllers\Api\Admin\UserRolePermissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuditLogController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CredentialController;
use App\Http\Controllers\Api\Integrations\GoogleSheetController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\WhatsAppController;

// Route::apiResource('/v1/admin/permissions', \App\Http\Controllers\Api\Admin\PermissionController::class)->except(['show', 'update']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// routes/api.php


Route::prefix('v1')->group(function () {
    Route::get('/google-sheets', [GoogleSheetController::class, 'index'])->name('google-sheets.index');
    Route::get('/google-sheets/{id}', [GoogleSheetController::class, 'show'])->name('google-sheets.show');
    Route::post('/google-sheets', [GoogleSheetController::class, 'store'])->name('google-sheets.store');
    Route::put('/google-sheets/{id}', [GoogleSheetController::class, 'update'])->name('google-sheets.update');
    Route::delete('/google-sheets/{id}', [GoogleSheetController::class, 'destroy'])->name('google-sheets.destroy');

    Route::apiResource('/google-sheets', GoogleSheetController::class);
    Route::post('/google-sheets/{id}/read-sheet', [GoogleSheetController::class, 'uploadOrders']);
    // Route::post('v1/google-sheets/{id}/sync-orders', [ApiGoogleSheetController::class, 'updateSheet']);
    Route::post('/google-sheets/{id}/sync-products', [GoogleSheetController::class, 'syncProducts']);



    // Order APIs
    Route::get('v1/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']); // List all orders
    // Route::post('v1/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']); // Create a new order
    // Route::get('v1/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']); // Show a specific order
    // Route::put('v1/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'update']); // Update a specific order
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



    // Template APIs

    Route::get('v1/templates', [TemplateController::class, 'index']); // List all templates
    Route::post('v1/templates', [TemplateController::class, 'store']); // Create a new template
    Route::get('v1/templates/{id}', [TemplateController::class, 'show']); // Show a specific template
    Route::put('v1/templates/{id}', [TemplateController::class, 'update']); // Update a specific template
    Route::delete('v1/templates/{id}', [TemplateController::class, 'destroy']); // Delete a specific template



    // Conacts APIs
    Route::get('v1/contacts', [ContactController::class, 'index']);       // List all contacts
    Route::post('v1/contacts', [ContactController::class, 'store']);       // Create new contact
    Route::get('v1/contacts/{contact}', [ContactController::class, 'show']); // Show single contact
    Route::put('v1/contacts/{contact}', [ContactController::class, 'update']); // Update contact
    Route::patch('v1contacts/{contact}', [ContactController::class, 'update']); // Also support patch
    Route::delete('v1/contacts/{contact}', [ContactController::class, 'destroy']); // Delete contact




    // fetchCredentials
    Route::post('/channel-credentials', [CredentialController::class, 'store']);
    Route::get('/channel-credentials/{id}', [CredentialController::class, 'show']);
    Route::put('/channel-credentials/{id}', [CredentialController::class, 'update']);
    Route::delete('/channel-credentials/{id}', [CredentialController::class, 'destroy']);




    // webwhats    webhook 
    // Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'receive']);
    Route::post('/whatsapp-send', [WhatsAppController::class, 'send']);
    Route::get('/whatsapp-messages', [WhatsAppController::class, 'index']);
    Route::get('messages/chat/{phone}', [WhatsAppController::class, 'getChat']);
    Route::delete('/whatsapp-messages/{id}', [WhatsAppController::class, 'destroy']);
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
