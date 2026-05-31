<?php

declare(strict_types=1);

use App\Modules\Bills\Controllers\BillController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token.auth', 'throttle:api'])->prefix('v1/bills')->group(function () {
    Route::get('/', [BillController::class, 'index']);
    Route::get('/stats', [BillController::class, 'stats']);
    Route::get('/{id}', [BillController::class, 'show']);
    Route::post('/', [BillController::class, 'store']);
    Route::get('/{id}/preview', [BillController::class, 'preview']);
    Route::post('/{id}/pay', [BillController::class, 'pay']);

    Route::get('/schedules', [BillController::class, 'schedules']);
    Route::post('/schedules', [BillController::class, 'createSchedule']);
    Route::patch('/schedules/{id}/toggle', [BillController::class, 'toggleSchedule']);

    Route::post('/process-due', [BillController::class, 'processDueSchedules']);
});
