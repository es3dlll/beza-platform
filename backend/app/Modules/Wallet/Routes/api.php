<?php

declare(strict_types=1);

use App\Modules\Wallet\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:30,1'])->prefix('v1/wallet')->group(function () {
    Route::get('/limits', [WalletController::class, 'limits']);
    Route::post('/limits/request-increase', [WalletController::class, 'requestIncrease']);
    Route::get('/balance', [WalletController::class, 'balance']);
});
