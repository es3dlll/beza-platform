<?php

declare(strict_types=1);

use App\Modules\Agent\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/agents')->group(function () {
    Route::post('/register', [AgentController::class, 'register']);
    Route::get('/{id}', [AgentController::class, 'show']);
    Route::post('/{id}/verify', [AgentController::class, 'verify']);
    Route::post('/cash-in', [AgentController::class, 'cashIn']);
    Route::post('/cash-out', [AgentController::class, 'cashOut']);
    Route::get('/transactions', [AgentController::class, 'transactions']);
    Route::get('/settlements', [AgentController::class, 'settlements']);
});
