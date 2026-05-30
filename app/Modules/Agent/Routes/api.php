<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Agent\Controllers\AgentController;

Route::prefix('v1/agents')->middleware('auth:api')->group(function () {
    Route::post('/register', [AgentController::class, 'register']);
    Route::post('/{id}/approve', [AgentController::class, 'approve']);
    Route::get('/{id}', [AgentController::class, 'show']);
    Route::get('/nearby/{governorate}', [AgentController::class, 'nearby']);
    Route::post('/{id}/cash-in', [AgentController::class, 'cashIn']);
    Route::post('/{id}/cash-out', [AgentController::class, 'cashOut']);
});

Route::prefix('v1/public/agents')->group(function () {
    Route::get('/nearby/{governorate}', [AgentController::class, 'nearby']);
});
