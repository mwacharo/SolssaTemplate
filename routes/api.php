<?php

use App\Http\Controllers\Api\Admin\UserRolePermissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuditLogController;
use App\Http\Controllers\Api\Admin\UserController;

// Route::apiResource('/v1/admin/permissions', \App\Http\Controllers\Api\Admin\PermissionController::class)->except(['show', 'update']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// routes/api.php

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
        });
    });
});
