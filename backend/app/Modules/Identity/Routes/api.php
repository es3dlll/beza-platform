<?php

declare(strict_types=1);

use App\Modules\Identity\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

use App\Modules\Identity\Controllers\UserLookupController;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
});

Route::get('/v1/users/lookup/{email}', [UserLookupController::class, 'byEmail']);
