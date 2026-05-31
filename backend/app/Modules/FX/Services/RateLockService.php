<?php

declare(strict_types=1);

namespace App\Modules\Fx\Services;

use App\Modules\Fx\Exceptions\RateExpiredException;
use App\Modules\Fx\Models\FxHold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RateLockService
{
    private const LOCK_TTL = 15;

    public function lockRate(
        string $walletId,
        string $baseCurrency,
        string $quoteCurrency,
        int $amount,
        int $rate,
        int $convertedAmount,
        int $spreadBps,
        ?string $idempotencyKey = null,
    ): FxHold {
        $existingHold = FxHold::where('wallet_id', $walletId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingHold !== null) {
            throw new RateExpiredException('Another FX operation is in progress for this wallet');
        }

        $expiresAt = now()->addSeconds(self::LOCK_TTL);

        return FxHold::create([
            'wallet_id' => $walletId,
            'base_currency' => $baseCurrency,
            'quote_currency' => $quoteCurrency,
            'amount' => $amount,
            'locked_rate' => $rate,
            'locked_spread_bps' => $spreadBps,
            'converted_amount' => $convertedAmount,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function getActiveHold(string $holdId): FxHold
    {
        $hold = FxHold::findOrFail($holdId);

        if (!$hold->isValid()) {
            $hold->release();
            throw new RateExpiredException("FX hold {$holdId} has expired");
        }

        return $hold;
    }
}
