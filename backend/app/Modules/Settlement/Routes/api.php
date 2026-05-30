<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Settlement\Controllers\SettlementController;

Route::prefix('v1/settlements')->middleware('auth:api')->group(function () {
    Route::get('/', [SettlementController::class, 'index']);
    Route::get('/{id}', [SettlementController::class, 'show']);
    Route::post('/{id}/execute', [SettlementController::class, 'execute']);
    Route::post('/{id}/retry', [SettlementController::class, 'retry']);
    Route::post('/{id}/reconcile', [SettlementController::class, 'reconcile']);
    Route::post('/cutoff', [SettlementController::class, 'processCutoff']);
    Route::post('/agent/daily', [SettlementController::class, 'settleAgentDaily']);
});
