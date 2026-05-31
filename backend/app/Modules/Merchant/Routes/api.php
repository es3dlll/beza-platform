<?php

declare(strict_types=1);

use App\Modules\Merchant\Controllers\MerchantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:30,1'])->prefix('v1/merchants')->group(function () {
    Route::post('/onboard', [MerchantController::class, 'onboard']);
    Route::post('/invoices', [MerchantController::class, 'createInvoice']);
    Route::get('/invoices/{id}/qr', [MerchantController::class, 'getQR']);
    Route::post('/invoices/{id}/pay', [MerchantController::class, 'pay']);
    Route::get('/settlements', [MerchantController::class, 'settlements']);
    Route::post('/invoices/{id}/refund', [MerchantController::class, 'refund']);
});
