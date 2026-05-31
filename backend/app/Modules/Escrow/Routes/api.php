<?php

declare(strict_types=1);

use App\Modules\Escrow\Controllers\EscrowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token.auth', 'throttle:api'])->prefix('v1/escrow')->group(function () {
    Route::get('/', [EscrowController::class, 'index']);
    Route::get('/stats', [EscrowController::class, 'stats']);
    Route::get('/{id}', [EscrowController::class, 'show']);
    Route::post('/', [EscrowController::class, 'initiate']);
    Route::post('/{id}/fund', [EscrowController::class, 'fund']);
    Route::post('/{id}/release', [EscrowController::class, 'release']);
    Route::post('/{id}/refund', [EscrowController::class, 'refund']);
    Route::post('/{id}/dispute', [EscrowController::class, 'dispute']);
    Route::get('/disputes', [EscrowController::class, 'disputes']);
    Route::post('/disputes/{id}/resolve', [EscrowController::class, 'resolveDispute']);
});
