<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Services;

use App\Modules\FinancialCore\Models\IdempotencyKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class IdempotencyService
{
    public function checkOrCreate(string $key): ?array
    {
        $existing = IdempotencyKey::where('key', $key)->first();
        if ($existing !== null) {
            if ($existing->expires_at === null || $existing->expires_at->isFuture()) {
                return $existing->response;
            }
            $existing->delete();
        }

        IdempotencyKey::create([
            'key' => $key,
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        return null;
    }

    public function complete(string $key, string $transactionId, array $response): void
    {
        IdempotencyKey::where('key', $key)->update([
            'transaction_id' => $transactionId,
            'response' => $response,
        ]);
    }

    public function isProcessing(string $key): bool
    {
        $record = IdempotencyKey::where('key', $key)->first();
        return $record !== null && $record->transaction_id === null;
    }

    public function cleanup(): void
    {
        IdempotencyKey::where('expires_at', '<', Carbon::now())->delete();
    }
}
