<?php

declare(strict_types=1);

use App\Modules\Compliance\Controllers\ComplianceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:30,1'])->prefix('v1/compliance')->group(function () {
    Route::get('/alerts', [ComplianceController::class, 'alerts']);
    Route::get('/cases/{id}', [ComplianceController::class, 'showCase']);
    Route::post('/cases/{id}/review', [ComplianceController::class, 'reviewCase']);
    Route::post('/rules/evaluate', [ComplianceController::class, 'evaluateRule']);
    Route::post('/sanctions/check', [ComplianceController::class, 'checkSanctions']);
});
