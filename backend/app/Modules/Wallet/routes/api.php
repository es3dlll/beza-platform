<?php

use App\Modules\Wallet\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/transfer', [TransferController::class, 'send']);
});
