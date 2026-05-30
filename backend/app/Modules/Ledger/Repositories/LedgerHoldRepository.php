<?php
declare(strict_types=1);

namespace Modules\Ledger\Repositories;

use Modules\Ledger\Models\LedgerHold;
use Illuminate\Support\Collection;

final class LedgerHoldRepository
{
    public function findById(string $id): ?LedgerHold
    {
        return LedgerHold::find($id);
    }

    public function findByAccount(string $accountId): Collection
    {
        return LedgerHold::where('account_id', $accountId)
            ->where('status', 'active')
            ->get();
    }

    public function findByReference(string $referenceType, string $referenceId): Collection
    {
        return LedgerHold::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get();
    }

    public function findExpired(): Collection
    {
        return LedgerHold::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();
    }

    public function save(LedgerHold $hold): LedgerHold
    {
        $hold->save();
        return $hold;
    }

    public function totalHeldAmount(string $accountId): int
    {
        return (int) LedgerHold::where('account_id', $accountId)
            ->where('status', 'active')
            ->sum('amount');
    }
}
