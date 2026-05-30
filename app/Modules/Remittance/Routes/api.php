<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Remittance\Controllers\RemittanceController;

Route::middleware(['auth:api'])->prefix('remittance')->group(function () {
    Route::get('corridors', [RemittanceController::class, 'listCorridors']);
    Route::post('corridors', [RemittanceController::class, 'createCorridor']);

    Route::get('beneficiaries', [RemittanceController::class, 'listBeneficiaries']);
    Route::post('beneficiaries', [RemittanceController::class, 'registerBeneficiary']);

    Route::post('orders', [RemittanceController::class, 'createRemittance']);
    Route::get('orders', [RemittanceController::class, 'listRemittances']);
    Route::get('orders/{id}', [RemittanceController::class, 'showRemittance']);
    Route::post('orders/{id}/screen', [RemittanceController::class, 'screenRemittance']);
    Route::post('orders/{id}/quote', [RemittanceController::class, 'quoteRemittance']);
    Route::post('orders/{id}/paid-in', [RemittanceController::class, 'confirmPaidIn']);
    Route::post('orders/{id}/complete', [RemittanceController::class, 'completeRemittance']);
    Route::post('orders/{id}/fail', [RemittanceController::class, 'failRemittance']);
    Route::post('orders/{id}/refund', [RemittanceController::class, 'refundRemittance']);
});
