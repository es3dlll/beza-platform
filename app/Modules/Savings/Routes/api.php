<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Savings\Controllers\SavingsController;

Route::middleware(['auth:api'])->prefix('v1/savings')->group(function () {
    Route::post('goals', [SavingsController::class, 'createGoal']);
    Route::get('goals', [SavingsController::class, 'listGoals']);
    Route::get('goals/{id}', [SavingsController::class, 'showGoal']);
    Route::post('goals/{id}/contribute', [SavingsController::class, 'contribute']);
    Route::post('goals/{id}/withdraw', [SavingsController::class, 'withdraw']);
    Route::get('goals/{id}/transactions', [SavingsController::class, 'transactions']);
});
