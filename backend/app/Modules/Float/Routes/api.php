<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Float\Controllers\FloatController;

Route::prefix('v1/floats')->middleware('auth:api')->group(function () {
    Route::get('/{id}', [FloatController::class, 'show']);
    Route::post('/{id}/adjust', [FloatController::class, 'adjust']);
    Route::get('/{id}/transactions', [FloatController::class, 'transactions']);
});
