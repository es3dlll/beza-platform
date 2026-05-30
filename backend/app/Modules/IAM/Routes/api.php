<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\IAM\Controllers\PermissionController;
use Modules\IAM\Controllers\PolicyController;
use Modules\IAM\Controllers\RoleController;

Route::prefix('v1/admin')
    ->middleware(['auth:api', 'permission:admin.access'])
    ->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions']);
        Route::apiResource('permissions', PermissionController::class);
        Route::apiResource('policies', PolicyController::class);
    });
