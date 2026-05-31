<?php

declare(strict_types=1);

use App\Modules\FinancialCore\Controllers\FinancialController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/financial')->group(function () {
    Route::post('/transfer', [FinancialController::class, 'transfer']);
    Route::post('/deposit', [FinancialController::class, 'deposit']);
    Route::post('/withdraw', [FinancialController::class, 'withdraw']);
    Route::post('/{id}/reverse', [FinancialController::class, 'reverse']);
    Route::get('/transactions', [FinancialController::class, 'transactions']);
    Route::get('/transactions/{id}', [FinancialController::class, 'show']);
});
