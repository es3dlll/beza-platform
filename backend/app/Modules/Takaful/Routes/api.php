<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Takaful\Controllers\TakafulController;

Route::prefix('v1/takaful')->middleware('auth:api')->group(function () {
    Route::get('/products', [TakafulController::class, 'indexProducts']);
    Route::post('/subscribe', [TakafulController::class, 'subscribe']);
    Route::get('/policies', [TakafulController::class, 'indexPolicies']);
    Route::get('/policies/{id}', [TakafulController::class, 'showPolicy']);
    Route::post('/claims', [TakafulController::class, 'fileClaim']);
    Route::get('/claims', [TakafulController::class, 'indexClaims']);
    Route::post('/claims/{id}/approve', [TakafulController::class, 'approveClaim']);
    Route::post('/claims/{id}/reject', [TakafulController::class, 'rejectClaim']);
    Route::get('/admin/dashboard', [TakafulController::class, 'adminDashboard']);
});
