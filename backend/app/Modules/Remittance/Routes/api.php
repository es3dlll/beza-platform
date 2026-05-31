<?php

declare(strict_types=1);

use App\Modules\Remittance\Controllers\RemittanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token.auth', 'throttle:transfers'])->prefix('v1/remittance')->group(function () {
    Route::post('/initiate', [RemittanceController::class, 'initiate']);
    Route::get('/preview', [RemittanceController::class, 'preview']);
    Route::get('/', [RemittanceController::class, 'list']);
    Route::get('/{id}', [RemittanceController::class, 'show']);
});
