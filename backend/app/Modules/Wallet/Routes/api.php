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

    Route::post('/transfer', function () {
        $validated = request()->validate([
            'to_wallet_id' => 'required|string',
            'amount_fils' => 'required|integer|min:1',
            'currency' => 'required|string|in:SYP,USD,EUR,TRY',
        ]);

        $wallet = request()->user()->wallet;
        $toWallet = \App\Modules\Wallet\Models\Wallet::findOrFail($validated['to_wallet_id']);
        $money = \App\Modules\Core\ValueObjects\Money::fromFils($validated['amount_fils'], \App\Modules\Core\ValueObjects\Currency::from($validated['currency']));

        $engine = app(\App\Modules\Ledger\Services\CoreFinancialEngine::class);
        $entry = $engine->transfer($money, $wallet, $toWallet, 'تحويل', 'transfer', request()->header('X-Request-Id'));

        return response()->json([
            'success' => true,
            'message' => 'تم التحويل بنجاح',
            'data' => [
                'entry_id' => $entry->id,
                'amount_fils' => $entry->amount_fils,
                'currency' => $entry->currency,
            ],
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-Id'),
        ]);
    })->middleware(['financial', 'throttle:transfers']);
});
