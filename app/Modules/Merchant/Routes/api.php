<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Merchant\Controllers\MerchantController;

Route::middleware(['auth:api'])->prefix('v1/merchant')->group(function () {
    Route::post('register', [MerchantController::class, 'register']);
    Route::get('my', [MerchantController::class, 'myMerchant']);
    Route::post('{id}/approve', [MerchantController::class, 'approve']);
    Route::post('{id}/suspend', [MerchantController::class, 'suspend']);

    Route::post('stores', [MerchantController::class, 'createStore']);
    Route::get('{merchantId}/stores', [MerchantController::class, 'listStores']);

    Route::get('qr/generate', [MerchantController::class, 'generateQr']);

    Route::post('pay', [MerchantController::class, 'pay']);
    Route::post('{id}/refund', [MerchantController::class, 'refund']);

    Route::get('payments/my', [MerchantController::class, 'myPayments']);
    Route::get('{merchantId}/payments', [MerchantController::class, 'merchantPayments']);
    Route::get('payment/{id}', [MerchantController::class, 'showPayment']);
});
