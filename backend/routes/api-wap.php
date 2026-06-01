<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/wap')->middleware(['throttle:30,wap'])->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('login',  [App\Http\Controllers\Api\Wap\AuthController::class, 'login']);
        Route::post('refresh', [App\Http\Controllers\Api\Wap\AuthController::class, 'refresh']);

        Route::middleware(App\Http\Middleware\ApiWapAuth::class)->group(function () {
            Route::post('logout', [App\Http\Controllers\Api\Wap\AuthController::class, 'logout']);
            Route::get('me',      [App\Http\Controllers\Api\Wap\AuthController::class, 'me']);
        });
    });

    Route::middleware(App\Http\Middleware\ApiWapAuth::class)->group(function () {

        Route::prefix('wallet')->group(function () {
            Route::get('balance',  [App\Http\Controllers\Api\Wap\WalletController::class, 'balance']);
            Route::post('transfer', [App\Http\Controllers\Api\Wap\WalletController::class, 'transfer']);
        });

        Route::prefix('merchant')->middleware('wap.role:merchant')->group(function () {
            Route::get('summary',     [App\Http\Controllers\Api\Wap\MerchantController::class, 'summary']);
            Route::get('qr',          [App\Http\Controllers\Api\Wap\MerchantController::class, 'qr']);
            Route::get('settlements', [App\Http\Controllers\Api\Wap\MerchantController::class, 'settlements']);
        });

        Route::prefix('agent')->middleware('wap.role:agent')->group(function () {
            Route::get('limits',      [App\Http\Controllers\Api\Wap\AgentController::class, 'limits']);
            Route::get('commissions', [App\Http\Controllers\Api\Wap\AgentController::class, 'commissions']);
            Route::get('pending',     [App\Http\Controllers\Api\Wap\AgentController::class, 'pending']);
        });
    });
});
