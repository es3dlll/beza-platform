<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Controllers\WalletController;

Route::prefix('v1/wallets')->middleware('auth:api')->group(function () {
    Route::post('/', [WalletController::class, 'create']);
    Route::get('/{id}', [WalletController::class, 'show']);
    Route::post('/{id}/deposit', [WalletController::class, 'deposit']);
    Route::post('/{id}/withdraw', [WalletController::class, 'withdraw']);
    Route::post('/{id}/transfer', [WalletController::class, 'transfer']);
    Route::get('/{id}/transactions', [WalletController::class, 'transactions']);
});
