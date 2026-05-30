<?php

use Illuminate\Support\Facades\Route;
use Modules\Financing\Controllers\FinancingController;

Route::middleware(['auth:api'])->prefix('v1/financing')->group(function () {
    Route::get('products', [FinancingController::class, 'products']);
    Route::post('apply', [FinancingController::class, 'apply']);
    Route::post('{id}/approve', [FinancingController::class, 'approve']);
    Route::post('{id}/disburse', [FinancingController::class, 'disburse']);
    Route::post('{id}/repay', [FinancingController::class, 'repay']);
    Route::get('my-loans', [FinancingController::class, 'myLoans']);
});
