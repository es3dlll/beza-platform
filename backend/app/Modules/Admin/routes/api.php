<?php

use App\Modules\Admin\Controllers\AdminAuthController;
use App\Modules\Admin\Controllers\AgentOversightController;
use App\Modules\Admin\Controllers\WapManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->group(function () {
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

        Route::prefix('agents')->group(function () {
            Route::middleware('admin.permission:agents:view')->group(function () {
                Route::get('/', [AgentOversightController::class, 'index']);
                Route::get('{id}', [AgentOversightController::class, 'show']);
            });

            Route::middleware('admin.permission:agents:commissions')->group(function () {
                Route::get('{id}/commissions', [AgentOversightController::class, 'commissions']);
            });

            Route::middleware('admin.permission:agents:finance')->group(function () {
                Route::get('{id}/settlements', [AgentOversightController::class, 'settlements']);
            });
        });

        Route::middleware('admin.permission:commissions:approve')->group(function () {
            Route::post('commissions/{id}/approve', [AgentOversightController::class, 'approveCommission']);
        });

        Route::middleware('admin.permission:finance:approve')->group(function () {
            Route::post('settlements/{id}/approve', [AgentOversightController::class, 'approveSettlement']);
        });

        Route::prefix('fraud-alerts')->group(function () {
            Route::middleware('admin.permission:security:view')->group(function () {
                Route::get('/', [AgentOversightController::class, 'fraudAlerts']);
            });

            Route::middleware('admin.permission:security:resolve')->group(function () {
                Route::post('{id}/resolve', [AgentOversightController::class, 'resolveFraudAlert']);
            });
        });
    });
});
