<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Loyalty\Controllers\LoyaltyController;

Route::middleware(['auth:api'])->prefix('v1/loyalty')->group(function () {
    Route::get('points', [LoyaltyController::class, 'myPoints']);
    Route::post('points/award', [LoyaltyController::class, 'awardPoints']);
    Route::post('points/redeem', [LoyaltyController::class, 'redeemPoints']);
    Route::get('points/history', [LoyaltyController::class, 'pointsHistory']);

    Route::post('cashback/calculate', [LoyaltyController::class, 'calculateCashback']);

    Route::get('rewards', [LoyaltyController::class, 'rewards']);
    Route::get('tiers', [LoyaltyController::class, 'tiers']);
});
