<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Financing\Controllers\FinancingController;

Route::middleware(['auth:api'])->prefix('v1/financing')->group(function () {
    Route::get('products', [FinancingController::class, 'products']);
    Route::get('products/{id}', [FinancingController::class, 'showProduct']);
    Route::post('apply', [FinancingController::class, 'apply']);
    Route::post('{id}/approve', [FinancingController::class, 'approve']);
    Route::post('{id}/disburse', [FinancingController::class, 'disburse']);
    Route::post('{id}/repay', [FinancingController::class, 'repay']);
    Route::get('{id}/schedule', [FinancingController::class, 'schedule']);
    Route::get('my-loans', [FinancingController::class, 'myLoans']);
    Route::post('bnpl/checkout', [FinancingController::class, 'bnplCheckout']);

    Route::get('admin/dashboard', [FinancingController::class, 'adminDashboard']);
    Route::get('admin/loans', [FinancingController::class, 'loansByStatus']);
});
