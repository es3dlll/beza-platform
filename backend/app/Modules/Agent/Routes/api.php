<?php

declare(strict_types=1);

use App\Modules\Agent\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/agent')->middleware(['token.auth', 'financial'])->group(function (): void {
    Route::post('/register', [AgentController::class, 'register']);
    Route::post('/liquidity/request', [AgentController::class, 'requestLiquidity'])->middleware('throttle:transfers');
    Route::post('/commission/calculate', [AgentController::class, 'calculateCommission']);
    Route::post('/commission/preview', [AgentController::class, 'previewCommission']);
});
