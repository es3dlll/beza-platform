<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Cards\Controllers\CardController;

Route::middleware(['auth:api'])->prefix('v1/cards')->group(function () {
    Route::post('/', [CardController::class, 'createCard']);
    Route::get('/', [CardController::class, 'listCards']);
    Route::get('{id}', [CardController::class, 'showCard']);

    Route::post('{id}/activate', [CardController::class, 'activateCard']);
    Route::post('{id}/suspend', [CardController::class, 'suspendCard']);
    Route::post('{id}/cancel', [CardController::class, 'cancelCard']);
    Route::put('{id}/limits', [CardController::class, 'updateLimits']);
    Route::put('{id}/settings', [CardController::class, 'updateSettings']);

    Route::post('{id}/authorize', [CardController::class, 'authorizeTransaction']);
    Route::get('{id}/transactions', [CardController::class, 'transactions']);

    Route::post('{id}/merchant-blocks', [CardController::class, 'blockMerchant']);
    Route::delete('{id}/merchant-blocks', [CardController::class, 'unblockMerchant']);
    Route::get('{id}/merchant-blocks', [CardController::class, 'listMerchantBlocks']);
});
