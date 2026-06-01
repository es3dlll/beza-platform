<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Controllers\AdminAuthController;
use App\Modules\Admin\Controllers\WapManagementController;

Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);

    Route::middleware('auth.admin')->group(function () {
        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('logout', [AdminAuthController::class, 'logout']);

        Route::middleware('admin.permission:manage_wap')->prefix('wap')->group(function () {
            Route::get('summary', [WapManagementController::class, 'summary']);
            Route::get('devices', [WapManagementController::class, 'devices']);
            Route::get('queue', [WapManagementController::class, 'queue']);
            Route::get('routes', [WapManagementController::class, 'routes']);
            Route::put('routes/{id}', [WapManagementController::class, 'updateRoute']);
        });
    });
});
