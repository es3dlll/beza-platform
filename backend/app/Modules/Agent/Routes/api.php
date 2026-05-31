<?php

declare(strict_types=1);

use App\Modules\Agent\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:60,1'])->prefix('v1/agents')->group(function () {
    Route::post('/onboard', [AgentController::class, 'onboard']);
    Route::get('/{id}', [AgentController::class, 'show']);
    Route::get('/{id}/float', [AgentController::class, 'showFloat']);
    Route::post('/float/adjust', [AgentController::class, 'adjustFloat']);
    Route::get('/commissions', [AgentController::class, 'commissions']);
    Route::post('/settle', [AgentController::class, 'settle']);
    Route::post('/{id}/verify', [AgentController::class, 'verify']);
    Route::post('/cash-in', [AgentController::class, 'cashIn']);
    Route::post('/cash-out', [AgentController::class, 'cashOut']);
    Route::get('/transactions', [AgentController::class, 'transactions']);
    Route::get('/settlements', [AgentController::class, 'settlementsList']);
});
