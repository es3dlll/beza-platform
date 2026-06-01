<?php

use App\Modules\Team\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1/agents/teams')->group(function () {
    Route::get('/', [TeamController::class, 'index']);
    Route::post('/', [TeamController::class, 'store']);
    Route::get('{id}', [TeamController::class, 'show']);
    Route::post('{id}/members', [TeamController::class, 'addMember']);
    Route::delete('{id}/members/{memberId}', [TeamController::class, 'removeMember']);
    Route::put('{id}/members/{memberId}/commission', [TeamController::class, 'updateCommission']);
    Route::get('{id}/delegation-logs', [TeamController::class, 'delegationLogs']);
});
