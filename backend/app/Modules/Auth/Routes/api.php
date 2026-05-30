<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\HealthController;
use Modules\Auth\Controllers\LoginController;
use Modules\Auth\Controllers\OtpController;
use Modules\Auth\Controllers\PinController;
use Modules\Auth\Controllers\RegisterController;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);
});

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/otp/request', [OtpController::class, 'send'])->middleware('throttle:5,1');
    Route::post('/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:10,1');
    Route::post('/pin/create', [PinController::class, 'create']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/login-with-password', [LoginController::class, 'loginWithPassword']);
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [LoginController::class, 'refresh']);
});
