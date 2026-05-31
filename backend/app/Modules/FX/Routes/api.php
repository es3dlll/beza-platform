<?php

declare(strict_types=1);

use App\Modules\Fx\Controllers\FxController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/fx')->group(function () {
    Route::post('/convert', [FxController::class, 'convert']);
    Route::get('/rate', [FxController::class, 'rate']);
    Route::post('/rate', [FxController::class, 'updateRate']);
    Route::get('/spread', [FxController::class, 'spread']);
    Route::get('/history', [FxController::class, 'history']);
});
