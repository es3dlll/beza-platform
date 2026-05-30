<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Escrow\Controllers\EscrowController;

Route::middleware(['auth:api'])->prefix('v1/escrow')->group(function () {
    Route::get('/', [EscrowController::class, 'index']);
    Route::post('/', [EscrowController::class, 'store']);
    Route::get('{id}', [EscrowController::class, 'show']);
    Route::post('{id}/release', [EscrowController::class, 'release']);
    Route::post('{id}/refund', [EscrowController::class, 'refund']);
    Route::post('{id}/dispute', [EscrowController::class, 'dispute']);
    Route::post('disputes/{id}/resolve', [EscrowController::class, 'resolveDispute']);
});
