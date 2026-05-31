<?php

declare(strict_types=1);

use App\Modules\Fraud\Controllers\FraudController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token.auth', 'throttle:transfers'])->prefix('v1/fraud')->group(function () {
    Route::get('/pending-reviews', [FraudController::class, 'pendingReviews']);
    Route::post('/pending-reviews/{id}/decision', [FraudController::class, 'reviewDecision']);
    Route::post('/pending-reviews/{id}/documents', [FraudController::class, 'requestDocuments']);
    Route::get('/dashboard', [FraudController::class, 'riskDashboard']);
    Route::get('/rules', [FraudController::class, 'listRules']);
    Route::post('/rules', [FraudController::class, 'createRule']);
    Route::put('/rules/{key}', [FraudController::class, 'updateRule']);
    Route::post('/rules/{key}/toggle', [FraudController::class, 'toggleRule']);
    Route::post('/rules/preview', [FraudController::class, 'previewRule']);
});
