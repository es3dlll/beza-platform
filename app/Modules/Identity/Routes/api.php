<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Controllers\IdentityController;

Route::prefix('v1/identity')->group(function () {
    Route::post('/register', [IdentityController::class, 'register']);
    Route::post('/check-phone', [IdentityController::class, 'checkPhone']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/profile', [IdentityController::class, 'profile']);
        Route::put('/profile', [IdentityController::class, 'updateProfile']);
    });
});
