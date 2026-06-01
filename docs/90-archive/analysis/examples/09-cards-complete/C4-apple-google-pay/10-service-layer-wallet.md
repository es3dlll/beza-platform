# 10 - طبقة الخدمة - المحفظة (Service Layer - Wallet)

## Wallet Operations

```php
<?php

namespace App\Services;

use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function holdForWalletPayment(Wallet $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $wallet->refresh();

            if ($wallet->balance - $wallet->hold_balance < $amount) {
                throw new \App\Exceptions\InsufficientBalanceException();
            }

            $wallet->increment('hold_balance', $amount);
        });
    }

    public function captureWalletHold(Wallet $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $wallet->decrement('hold_balance', $amount);
            $wallet->decrement('balance', $amount);
        });
    }

    public function releaseWalletHold(Wallet $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $wallet->decrement('hold_balance', $amount);
        });
    }
}
```

## Method Summary

| Method | Description | Transactional |
|--------|-------------|---------------|
| holdForWalletPayment | Place temporary hold on funds | Yes |
| captureWalletHold | Finalize hold into actual charge | Yes |
| releaseWalletHold | Release hold if payment declined | Yes |
