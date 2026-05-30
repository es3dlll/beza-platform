<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notification\Controllers\NotificationController;

Route::middleware(['auth:api'])->prefix('v1/notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('unread', [NotificationController::class, 'unread']);
    Route::post('{id}/read', [NotificationController::class, 'markRead']);
    Route::post('mark-all-read', [NotificationController::class, 'markAllRead']);
});
