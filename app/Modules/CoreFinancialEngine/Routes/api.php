<?php

use Illuminate\Support\Facades\Route;
use Modules\CoreFinancialEngine\Controllers\TransactionController;
use Modules\CoreFinancialEngine\Controllers\FeeController;
use Modules\CoreFinancialEngine\Controllers\SettlementController;

Route::middleware(['auth:api', 'jwt'])->prefix('cfe')->group(function () {
    Route::post('transactions', [TransactionController::class, 'post']);
    Route::post('transactions/{id}/reverse', [TransactionController::class, 'reverse']);
    Route::get('transactions/{id}/reversible', [TransactionController::class, 'canReverse']);

    Route::post('fees/calculate', [FeeController::class, 'calculate']);
    Route::post('fees/apply', [FeeController::class, 'apply']);

    Route::post('settlements/batch', [SettlementController::class, 'batch']);
    Route::get('settlements/daily-cutoff/{date}', [SettlementController::class, 'dailyCutoff']);
});
