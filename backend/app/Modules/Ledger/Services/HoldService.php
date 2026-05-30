<?php
declare(strict_types=1);

namespace Modules\Ledger\Services;

use Modules\Ledger\DTOs\CreateHoldDto;
use Modules\Ledger\Events\HoldPlaced;
use Modules\Ledger\Events\HoldReleased;
use Modules\Ledger\Exceptions\AccountNotFoundException;
use Modules\Ledger\Exceptions\InsufficientBalanceException;
use Modules\Ledger\Models\LedgerHold;
use Modules\Ledger\Repositories\LedgerHoldRepository;
use Modules\Ledger\Repositories\LedgerAccountRepository;
use Illuminate\Support\Str;

final class HoldService implements \Modules\Ledger\Contracts\HoldServiceInterface
{
    public function __construct(
        private readonly LedgerHoldRepository $holds,
        private readonly LedgerAccountRepository $accounts,
    ) {}

    public function place(CreateHoldDto $dto): LedgerHold
    {
        $account = $this->accounts->findById($dto->accountId);
        if (!$account) {
            throw new AccountNotFoundException($dto->accountId);
        }

        $activeHolds = $this->holds->totalHeldAmount($dto->accountId);
        $available = $account->balance - $activeHolds;

        if ($available < $dto->amount) {
            throw new InsufficientBalanceException($dto->accountId, $dto->amount, $available);
        }

        $hold = new LedgerHold();
        $hold->id = Str::ulid()->toBase32();
        $hold->account_id = $dto->accountId;
        $hold->amount = $dto->amount;
        $hold->currency = $dto->currency;
        $hold->reason = $dto->reason;
        $hold->reference_type = $dto->referenceType;
        $hold->reference_id = $dto->referenceId;
        $hold->expires_at = $dto->expiresAt;
        $hold->status = 'active';

        $this->holds->save($hold);

        event(new HoldPlaced(
            holdId: $hold->id,
            accountId: $dto->accountId,
            amount: $dto->amount,
            reason: $dto->reason,
            referenceType: $dto->referenceType,
            referenceId: $dto->referenceId,
        ));

        return $hold;
    }

    public function release(string $holdId, string $reason): LedgerHold
    {
        $hold = $this->holds->findById($holdId);
        if (!$hold) {
            throw new AccountNotFoundException("Hold not found: $holdId");
        }

        $hold->status = 'released';
        $hold->released_at = now();
        $hold->release_reason = $reason;
        $this->holds->save($hold);

        event(new HoldReleased(
            holdId: $holdId,
            accountId: $hold->account_id,
            amount: $hold->amount,
            reason: $reason,
        ));

        return $hold;
    }

    public function releaseExpired(): int
    {
        $expired = $this->holds->findExpired();
        $count = 0;
        foreach ($expired as $hold) {
            $this->release($hold->id, 'expired');
            $count++;
        }
        return $count;
    }
}
