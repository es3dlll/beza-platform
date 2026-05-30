<?php
declare(strict_types=1);

namespace Modules\Ledger\Repositories;

use Modules\Ledger\Models\LedgerAccount;
use Illuminate\Support\Collection;

final class LedgerAccountRepository
{
    public function findById(string $id): ?LedgerAccount
    {
        return LedgerAccount::find($id);
    }

    public function findByAccountNumber(string $accountNumber): ?LedgerAccount
    {
        return LedgerAccount::where('account_number', $accountNumber)->first();
    }

    public function findByType(string $type): Collection
    {
        return LedgerAccount::where('type', $type)->get();
    }

    public function findAll(): Collection
    {
        return LedgerAccount::all();
    }

    public function save(LedgerAccount $account): LedgerAccount
    {
        $account->save();
        return $account;
    }

    public function findSubAccounts(string $parentId): Collection
    {
        return LedgerAccount::where('parent_id', $parentId)->get();
    }

    public function findRootAccounts(): Collection
    {
        return LedgerAccount::whereNull('parent_id')->get();
    }
}
