<?php

declare(strict_types=1);

use App\Modules\Remittance\Controllers\RemittanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:30,1'])->prefix('v1/remittance')->group(function () {
    Route::post('/quote', [RemittanceController::class, 'quote']);
    Route::post('/initiate', [RemittanceController::class, 'initiate']);
    Route::get('/{id}/status', [RemittanceController::class, 'status']);
    Route::post('/{id}/cancel', [RemittanceController::class, 'cancel']);
});
