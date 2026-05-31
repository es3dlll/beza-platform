<?php

declare(strict_types=1);

use App\Modules\BillProvider\Controllers\BillProviderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token.auth', 'throttle:api'])->prefix('v1/bill-providers')->group(function () {
    Route::get('/', [BillProviderController::class, 'index']);
    Route::get('/{id}', [BillProviderController::class, 'show']);
    Route::post('/', [BillProviderController::class, 'store']);
    Route::put('/{id}', [BillProviderController::class, 'update']);
    Route::patch('/{id}/toggle', [BillProviderController::class, 'toggle']);
});
