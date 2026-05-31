<?php

declare(strict_types=1);

use App\Modules\EventBus\Controllers\EventBusController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/event-bus')->group(function () {
    Route::get('/health', [EventBusController::class, 'health']);
    Route::get('/dead-letters', [EventBusController::class, 'deadLetters']);
    Route::post('/dead-letters/{id}/retry', [EventBusController::class, 'retryDeadLetter']);
});
