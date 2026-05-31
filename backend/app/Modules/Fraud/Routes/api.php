<?php

declare(strict_types=1);

use App\Modules\Fraud\Controllers\FraudController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/fraud')->group(function () {
    Route::post('/check', [FraudController::class, 'check']);
    Route::post('/monitor', [FraudController::class, 'monitor']);
    Route::get('/decisions', [FraudController::class, 'decisions']);
    Route::get('/rules', [FraudController::class, 'rules']);
    Route::post('/decisions/{id}/resolve', [FraudController::class, 'resolve']);
});
