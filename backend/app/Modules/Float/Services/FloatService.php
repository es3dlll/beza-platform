<?php

declare(strict_types=1);

namespace Modules\Float\Services;

use Modules\Float\DTOs\CreateFloatAccountDto;
use Modules\Float\DTOs\FloatTransactionDto;
use Modules\Float\DTOs\FloatTransferDto;
use Modules\Float\Exceptions\FloatAccountNotFoundException;
use Modules\Float\Exceptions\InsufficientFloatBalanceException;
use Modules\Float\Exceptions\FloatLimitExceededException;
use Modules\Float\Models\FloatAccount;
use Modules\Float\Models\FloatTransaction;
use Modules\Float\Repositories\FloatRepository;
use Illuminate\Support\Str;

final class FloatService
{
    public function __construct(
        private readonly FloatRepository $floats,
    ) {}

    public function create(CreateFloatAccountDto $dto): FloatAccount
    {
        $existing = $this->floats->findByOwnerAndType($dto->ownerType, $dto->ownerId, $dto->floatType);
        if ($existing) {
            return $existing;
        }

        $account = new FloatAccount();
        $account->id = Str::ulid()->toBase32();
        $account->owner_type = $dto->ownerType;
        $account->owner_id = $dto->ownerId;
        $account->float_type = $dto->floatType;
        $account->balance = 0;
        $account->pending_balance = 0;
        $account->currency = $dto->currency;
        $account->status = 'active';
        $account->minimum_balance = $dto->minimumBalance;
        $account->maximum_balance = $dto->maximumBalance;

        return $this->floats->save($account);
    }

    public function credit(FloatTransactionDto $dto): FloatAccount
    {
        $account = $this->findOrFail($dto->floatAccountId);

        if ($account->maximum_balance && ($account->balance + $dto->amount) > $account->maximum_balance) {
            throw new FloatLimitExceededException(
                'maximum_balance',
                $account->maximum_balance,
                $account->balance
            );
        }

        $txn = new FloatTransaction();
        $txn->id = Str::ulid()->toBase32();
        $txn->float_account_id = $account->id;
        $txn->type = $dto->type;
        $txn->amount = $dto->amount;
        $txn->balance_before = $account->balance;
        $txn->balance_after = $account->balance + $dto->amount;
        $txn->reference_type = $dto->referenceType;
        $txn->reference_id = $dto->referenceId;
        $txn->description = $dto->description;
        $txn->status = 'completed';

        $account->balance += $dto->amount;
        $this->floats->save($account);
        $this->floats->saveTransaction($txn);

        return $account;
    }

    public function debit(FloatTransactionDto $dto): FloatAccount
    {
        $account = $this->findOrFail($dto->floatAccountId);
        $available = $account->availableBalance();

        if ($available < $dto->amount) {
            throw new InsufficientFloatBalanceException($available, $dto->amount, $account->float_type);
        }

        if (($account->balance - $dto->amount) < $account->minimum_balance) {
            throw new FloatLimitExceededException(
                'minimum_balance',
                $account->minimum_balance,
                $account->balance - $dto->amount
            );
        }

        $txn = new FloatTransaction();
        $txn->id = Str::ulid()->toBase32();
        $txn->float_account_id = $account->id;
        $txn->type = $dto->type;
        $txn->amount = $dto->amount;
        $txn->balance_before = $account->balance;
        $txn->balance_after = $account->balance - $dto->amount;
        $txn->reference_type = $dto->referenceType;
        $txn->reference_id = $dto->referenceId;
        $txn->description = $dto->description;
        $txn->status = 'completed';

        $account->balance -= $dto->amount;
        $this->floats->save($account);
        $this->floats->saveTransaction($txn);

        return $account;
    }

    public function transfer(FloatTransferDto $dto): array
    {
        $from = $this->findOrFail($dto->fromFloatAccountId);
        $to = $this->findOrFail($dto->toFloatAccountId);

        $this->debit(new FloatTransactionDto(
            floatAccountId: $from->id,
            type: 'transfer_out',
            amount: $dto->amount,
            referenceType: 'float_transfer',
            referenceId: $dto->referenceId,
            description: $dto->description,
        ));

        $this->credit(new FloatTransactionDto(
            floatAccountId: $to->id,
            type: 'transfer_in',
            amount: $dto->amount,
            referenceType: 'float_transfer',
            referenceId: $dto->referenceId,
            description: $dto->description,
        ));

        return [
            'from' => $from->fresh(),
            'to' => $to->fresh(),
        ];
    }

    public function getBalance(string $accountId): array
    {
        $account = $this->findOrFail($accountId);
        return [
            'id' => $account->id,
            'owner_type' => $account->owner_type,
            'owner_id' => $account->owner_id,
            'float_type' => $account->float_type,
            'balance' => $account->balance,
            'pending_balance' => $account->pending_balance,
            'available' => $account->availableBalance(),
            'currency' => $account->currency,
            'status' => $account->status,
        ];
    }

    public function adjust(string $accountId, int $newBalance, string $reason): FloatAccount
    {
        $account = $this->findOrFail($accountId);
        $diff = $newBalance - $account->balance;

        if ($diff > 0) {
            return $this->credit(new FloatTransactionDto(
                floatAccountId: $accountId,
                type: 'adjustment_in',
                amount: $diff,
                description: $reason,
            ));
        }

        return $this->debit(new FloatTransactionDto(
            floatAccountId: $accountId,
            type: 'adjustment_out',
            amount: abs($diff),
            description: $reason,
        ));
    }

    private function findOrFail(string $id): FloatAccount
    {
        $account = $this->floats->findById($id);
        if (!$account) {
            throw new FloatAccountNotFoundException($id);
        }
        return $account;
    }
}
