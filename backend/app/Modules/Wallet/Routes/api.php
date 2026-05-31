<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1/wallet')->middleware('token.auth')->group(function (): void {
    Route::get('/balance', function () {
        $wallet = request()->user()->wallet;

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الرصيد',
            'data' => [
                'balance_fils' => $wallet->balance_fils,
                'currency' => $wallet->currency,
            ],
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-Id'),
        ]);
    });
});
