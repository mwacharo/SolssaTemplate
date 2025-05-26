<?php

use App\Http\Controllers\Api\Admin\UserRolePermissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// routes/api.php

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('roles', \App\Http\Controllers\Api\Admin\RoleController::class)->except('show');
        Route::apiResource('permissions', \App\Http\Controllers\Api\Admin\PermissionController::class)->except(['show', 'update']);
        
        Route::prefix('users/{user}')->name('users.')->group(function () {
            Route::post('assign-role', [UserRolePermissionController::class, 'assignRole'])->name('assignRole');
            Route::post('remove-role', [UserRolePermissionController::class, 'removeRole'])->name('removeRole');
            Route::post('assign-permission', [UserRolePermissionController::class, 'assignPermission'])->name('assignPermission');
            Route::post('remove-permission', [UserRolePermissionController::class, 'removePermission'])->name('removePermission');
        });
    });

});
