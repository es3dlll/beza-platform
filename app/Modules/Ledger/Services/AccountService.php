<?php
declare(strict_types=1);

namespace Modules\Ledger\Services;

use App\Domain\ValueObjects\Money;
use Modules\Ledger\DTOs\CreateAccountDto;
use Modules\Ledger\Events\AccountBalanceChanged;
use Modules\Ledger\Exceptions\AccountAlreadyExistsException;
use Modules\Ledger\Exceptions\AccountNotFoundException;
use Modules\Ledger\Models\LedgerAccount;
use Modules\Ledger\Repositories\LedgerAccountRepository;
use Modules\Ledger\Repositories\LedgerHoldRepository;
use Illuminate\Support\Str;

final class AccountService
{
    public function __construct(
        private readonly LedgerAccountRepository $accounts,
        private readonly LedgerHoldRepository $holds,
    ) {}

    public function create(CreateAccountDto $dto): LedgerAccount
    {
        if ($this->accounts->findByAccountNumber($dto->accountNumber)) {
            throw new AccountAlreadyExistsException($dto->accountNumber);
        }

        $account = new LedgerAccount();
        $account->id = Str::ulid()->toBase32();
        $account->account_number = $dto->accountNumber;
        $account->name = $dto->name;
        $account->type = $dto->type;
        $account->currency = $dto->currency;
        $account->parent_id = $dto->parentId;
        $account->metadata = $dto->metadata;
        $account->balance = 0;
        $account->available_balance = 0;

        return $this->accounts->save($account);
    }

    public function getBalance(string $accountId): Money
    {
        $account = $this->findOrFail($accountId);
        return Money::fromInt($account->balance, \App\Domain\ValueObjects\Currency::fromCode($account->currency));
    }

    public function getAvailableBalance(string $accountId): Money
    {
        $account = $this->findOrFail($accountId);
        $activeHolds = $this->holds->totalHeldAmount($accountId);
        $available = $account->balance - $activeHolds;
        return Money::fromInt(max(0, $available), \App\Domain\ValueObjects\Currency::fromCode($account->currency));
    }

    public function adjustBalance(string $accountId, int $amount, string $direction, string $journalEntryId): LedgerAccount
    {
        $account = $this->findOrFail($accountId);
        $previousBalance = $account->balance;

        if ($direction === 'debit') {
            $account->debit($amount);
        } else {
            $account->credit($amount);
        }
        $account->refresh();

        $account->available_balance = max(0, $account->balance - $this->holds->totalHeldAmount($accountId));
        $this->accounts->save($account);

        event(new AccountBalanceChanged(
            accountId: $accountId,
            accountNumber: $account->account_number,
            previousBalance: $previousBalance,
            newBalance: $account->balance,
            change: $amount,
            direction: $direction,
            currency: $account->currency,
            journalEntryId: $journalEntryId,
        ));

        return $account;
    }

    private function findOrFail(string $accountId): LedgerAccount
    {
        $account = $this->accounts->findById($accountId);
        if (!$account) {
            throw new AccountNotFoundException($accountId);
        }
        return $account;
    }
}
