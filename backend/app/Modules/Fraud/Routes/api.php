<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Fraud\Controllers\FraudController;

Route::middleware(['auth:api'])->prefix('v1/fraud')->group(function () {
    Route::post('check', [FraudController::class, 'check']);

    Route::get('rules', [FraudController::class, 'rules']);
    Route::post('rules', [FraudController::class, 'createRule']);
    Route::put('rules/{id}', [FraudController::class, 'updateRule']);

    Route::get('cases', [FraudController::class, 'cases']);
    Route::get('cases/{id}', [FraudController::class, 'showCase']);
    Route::post('cases/{id}/review', [FraudController::class, 'reviewCase']);

    Route::get('blacklist', [FraudController::class, 'blacklist']);
    Route::post('blacklist', [FraudController::class, 'addBlacklist']);
    Route::delete('blacklist/{id}', [FraudController::class, 'removeBlacklist']);
});
