<?php

declare(strict_types=1);

use App\Modules\Notification\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token.auth', 'throttle:api'])->prefix('v1/notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/stats', [NotificationController::class, 'stats']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/mark-read/{id}', [NotificationController::class, 'markRead']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
    Route::post('/send', [NotificationController::class, 'send']);
    Route::post('/send-bulk', [NotificationController::class, 'sendBulk']);
});
