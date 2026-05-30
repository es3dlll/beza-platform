<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Bills\Controllers\BillController;

Route::middleware(['auth:api'])->prefix('bills')->group(function () {
    Route::get('providers', [BillController::class, 'listProviders']);
    Route::post('providers', [BillController::class, 'createProvider']);

    Route::post('inquiry', [BillController::class, 'inquire']);
    Route::post('pay', [BillController::class, 'pay']);
    Route::post('{id}/refund', [BillController::class, 'refund']);
    Route::get('history', [BillController::class, 'history']);
    Route::get('{id}', [BillController::class, 'showPayment']);
});
