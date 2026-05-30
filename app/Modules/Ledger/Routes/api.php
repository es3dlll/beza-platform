<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Ledger\Controllers\AccountController;
use Modules\Ledger\Controllers\JournalController;
use Modules\Ledger\Controllers\HoldController;
use Modules\Ledger\Controllers\TrialBalanceController;

Route::middleware(['auth:api', 'jwt'])->prefix('ledger')->group(function () {
    Route::get('accounts', [AccountController::class, 'index']);
    Route::post('accounts', [AccountController::class, 'store']);
    Route::get('accounts/{id}', [AccountController::class, 'show']);
    Route::get('accounts/{id}/balance', [AccountController::class, 'balance']);
    Route::get('accounts/{id}/available', [AccountController::class, 'available']);

    Route::post('journal/entries', [JournalController::class, 'post']);
    Route::get('journal/entries/{id}', [JournalController::class, 'show']);
    Route::get('journal/reference/{type}/{id}', [JournalController::class, 'byReference']);

    Route::post('holds', [HoldController::class, 'store']);
    Route::post('holds/{id}/release', [HoldController::class, 'release']);
    Route::get('holds/account/{accountId}', [HoldController::class, 'byAccount']);

    Route::get('trial-balance', [TrialBalanceController::class, 'index']);
});
