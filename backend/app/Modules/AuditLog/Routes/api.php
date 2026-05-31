<?php

declare(strict_types=1);

use App\Modules\AuditLog\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->middleware('token.auth')->group(function (): void {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});
