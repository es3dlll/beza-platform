<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\OpenFinance\Controllers\OpenFinanceController;

Route::middleware(['auth:api'])->prefix('v1/open-finance')->group(function () {
    // OAuth
    Route::post('register-app', [OpenFinanceController::class, 'registerApp']);
    Route::post('create-consent', [OpenFinanceController::class, 'createConsent']);
    Route::post('generate-token', [OpenFinanceController::class, 'generateToken']);
    Route::post('{id}/revoke-consent', [OpenFinanceController::class, 'revokeConsent']);
    Route::get('apps', [OpenFinanceController::class, 'listApps']);
    Route::get('consents', [OpenFinanceController::class, 'listConsents']);

    // Payment Initiation
    Route::post('payments', [OpenFinanceController::class, 'initiatePayment']);
    Route::get('payments', [OpenFinanceController::class, 'myPayments']);

    // Account Information
    Route::get('accounts', [OpenFinanceController::class, 'listAccounts']);
    Route::get('accounts/{accountId}/transactions', [OpenFinanceController::class, 'accountTransactions']);

    // Wallet API
    Route::post('wallets', [OpenFinanceController::class, 'createWallet']);

    // Webhooks
    Route::post('webhooks', [OpenFinanceController::class, 'registerWebhook']);
    Route::get('webhooks', [OpenFinanceController::class, 'listWebhooks']);
    Route::get('webhooks/{webhookId}/deliveries', [OpenFinanceController::class, 'webhookDeliveries']);

    // Developer
    Route::get('my-tier', [OpenFinanceController::class, 'myTier']);

    // Sandbox
    Route::get('sandbox', [OpenFinanceController::class, 'sandboxMode']);
});
