<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Investments\Controllers\InvestmentController;

Route::prefix('v1/investments')->middleware('auth:api')->group(function () {
    Route::get('/funds', [InvestmentController::class, 'listFunds']);
    Route::get('/funds/{id}', [InvestmentController::class, 'showFund']);
    Route::get('/funds/{id}/nav-history', [InvestmentController::class, 'navHistory']);

    Route::post('/subscribe', [InvestmentController::class, 'subscribe']);
    Route::post('/redeem', [InvestmentController::class, 'redeem']);
    Route::get('/subscriptions', [InvestmentController::class, 'subscriptions']);

    Route::post('/nav', [InvestmentController::class, 'recordNav']);
    Route::get('/admin/dashboard', [InvestmentController::class, 'adminDashboard']);

    Route::get('/zakat/calculate', [InvestmentController::class, 'calculateZakat']);
});
