<?php

declare(strict_types=1);

use App\Modules\Analytics\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token.auth', 'throttle:api'])->prefix('v1/analytics')->group(function () {
    Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/snapshot', [AnalyticsController::class, 'snapshot']);
    Route::get('/aggregate', [AnalyticsController::class, 'aggregate']);
    Route::get('/range', [AnalyticsController::class, 'range']);
    Route::post('/refresh', [AnalyticsController::class, 'refresh']);
    Route::get('/export-csv', [AnalyticsController::class, 'exportCsv']);
    Route::get('/summary', [AnalyticsController::class, 'summary']);
});
