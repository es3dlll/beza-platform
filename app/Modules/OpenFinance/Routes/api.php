<?php

use Illuminate\Support\Facades\Route;
use Modules\OpenFinance\Controllers\OpenFinanceController;

Route::middleware(['auth:api'])->prefix('v1/open-finance')->group(function () {
    Route::post('register-app', [OpenFinanceController::class, 'registerApp']);
    Route::post('create-consent', [OpenFinanceController::class, 'createConsent']);
    Route::post('generate-token', [OpenFinanceController::class, 'generateToken']);
    Route::post('{id}/revoke-consent', [OpenFinanceController::class, 'revokeConsent']);
    Route::get('apps', [OpenFinanceController::class, 'listApps']);
    Route::get('consents', [OpenFinanceController::class, 'listConsents']);
});
