<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Settlement\Controllers\SettlementController;

Route::prefix('v1/settlements')->middleware('auth:api')->group(function () {
    Route::get('/{id}', [SettlementController::class, 'show']);
    Route::post('/{id}/execute', [SettlementController::class, 'execute']);
    Route::post('/agent/daily', [SettlementController::class, 'settleAgentDaily']);
});
