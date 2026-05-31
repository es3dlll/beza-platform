<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Ledger\Events\AccountBalanceChanged;
use App\Modules\Ledger\Exceptions\AccountNotFoundException;
use App\Modules\Ledger\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class AccountService
{
    public function createAccount(
        string $code,
        string $name,
        string $nameAr,
        string $type,
        string $currency = 'SYP',
        bool $isSystem = false,
    ): LedgerAccount {
        return LedgerAccount::create([
            'id' => Str::ulid()->toBase32(),
            'code' => $code,
            'name' => $name,
            'name_ar' => $nameAr,
            'type' => $type,
            'balance' => 0,
            'currency' => $currency,
            'is_system' => $isSystem,
        ]);
    }

    public function getAccount(string $id): LedgerAccount
    {
        return LedgerAccount::find($id) ?? throw new AccountNotFoundException($id);
    }

    public function getAccountByCode(string $code): LedgerAccount
    {
        return LedgerAccount::where('code', $code)->first() ?? throw new AccountNotFoundException($code);
    }

    public function listAccounts(?string $type = null): Collection
    {
        $query = LedgerAccount::orderBy('code');

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    public function getChartOfAccounts(): Collection
    {
        return LedgerAccount::orderBy('code')->get();
    }

    public function updateBalance(string $accountId, int $delta, string $direction, string $transactionId = ''): void
    {
        $account = $this->getAccount($accountId);
        $previousBalance = $account->balance;

        $increaseTypes = match ($direction) {
            'debit' => ['asset', 'expense'],
            'credit' => ['liability', 'equity', 'revenue'],
        };

        $isIncrease = in_array($account->type, $increaseTypes, true);

        $newBalance = $isIncrease
            ? $account->balance + $delta
            : $account->balance - $delta;

        $account->update(['balance' => $newBalance]);

        event(new AccountBalanceChanged(
            accountId: $accountId,
            previousBalance: $previousBalance,
            newBalance: $newBalance,
            delta: $delta,
            direction: $direction,
            transactionId: $transactionId,
        ));
    }
}
