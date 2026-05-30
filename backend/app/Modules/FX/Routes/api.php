<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FX\Controllers\FxController;

Route::prefix('v1/fx')->middleware('auth:api')->group(function () {
    Route::get('/rates', [FxController::class, 'rates']);
    Route::get('/rates/{base}/{quote}/history', [FxController::class, 'rateHistory']);
    Route::post('/rates', [FxController::class, 'createRate']);
    Route::post('/quotes', [FxController::class, 'getQuote']);
    Route::post('/conversions', [FxController::class, 'executeConversion']);
    Route::get('/quotes', [FxController::class, 'quoteHistory']);
    Route::get('/conversions/{walletId}', [FxController::class, 'conversionHistory']);
    Route::get('/conversions/show/{id}', [FxController::class, 'showConversion']);
});

Route::prefix('v1/public/fx')->group(function () {
    Route::get('/rates', [FxController::class, 'rates']);
});
