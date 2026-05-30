<?php

declare(strict_types=1);

namespace Modules\Wallet\Services;

use App\Domain\ValueObjects\Currency;
use App\Domain\ValueObjects\Money;
use Modules\CoreFinancialEngine\DTOs\HoldInstructionDto;
use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\DTOs\FeeAssessmentDto;
use Modules\CoreFinancialEngine\Services\PostingEngine;
use Modules\CoreFinancialEngine\Services\FeeEngine;
use Modules\CoreFinancialEngine\Services\HoldEngine;
use Modules\Wallet\Contracts\WalletServiceInterface;
use Modules\Wallet\DTOs\CreateWalletDto;
use Modules\Wallet\DTOs\DepositDto;
use Modules\Wallet\DTOs\WithdrawDto;
use Modules\Wallet\DTOs\TransferDto;
use Modules\Wallet\Events\WalletCreated;
use Modules\Wallet\Events\WalletCredited;
use Modules\Wallet\Events\WalletDebited;
use Modules\Wallet\Events\WalletTransferInitiated;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Exceptions\WalletNotFoundException;
use Modules\Wallet\Exceptions\DailyLimitExceededException;
use Modules\Wallet\Exceptions\KycTierTooLowException;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Repositories\WalletRepository;
use Illuminate\Support\Str;

final class WalletService implements WalletServiceInterface
{
    public function __construct(
        private readonly WalletRepository $wallets,
        private readonly PostingEngine $posting,
        private readonly FeeEngine $fees,
        private readonly HoldEngine $holds,
    ) {}

    public function create(CreateWalletDto $dto): Wallet
    {
        $existing = $this->wallets->findByUserAndCurrency($dto->userId, $dto->currency);
        if ($existing) {
            return $existing;
        }

        $wallet = new Wallet();
        $wallet->id = Str::ulid()->toBase32();
        $wallet->user_id = $dto->userId;
        $wallet->currency = $dto->currency;
        $wallet->balance = 0;
        $wallet->available_balance = 0;
        $wallet->status = 'active';
        $wallet->kyc_tier_required = $dto->kycTierRequired;
        $wallet->daily_limit = $dto->dailyLimit;
        $wallet->daily_used = 0;
        $wallet->daily_reset_at = now()->endOfDay();

        $this->wallets->save($wallet);

        event(new WalletCreated(
            walletId: $wallet->id,
            userId: $dto->userId,
            currency: $dto->currency,
        ));

        return $wallet;
    }

    public function deposit(DepositDto $dto): Wallet
    {
        $wallet = $this->findOrFail($dto->walletId);

        $instruction = new PostingInstructionDto(
            referenceType: $dto->referenceType,
            referenceId: $dto->referenceId ?: Str::ulid()->toBase32(),
            description: $dto->description ?: 'Wallet deposit',
            lines: [
                [
                    'account_id' => $wallet->ledger_account_id ?? $wallet->id,
                    'amount' => $dto->amount,
                    'type' => 'credit',
                    'description' => "Deposit to wallet {$wallet->id}",
                ],
            ],
            channel: $dto->channel,
            initiatedBy: $wallet->user_id,
            metadata: array_merge($dto->metadata ?? [], ['wallet_id' => $wallet->id, 'operation' => 'deposit']),
        );

        $result = $this->posting->execute($instruction);
        if (!$result->success) {
            throw new \RuntimeException("Deposit failed: {$result->errorMessage}");
        }

        $wallet->balance += $dto->amount;
        $wallet->available_balance += $dto->amount;
        $this->wallets->save($wallet);

        $this->recordTransaction($wallet, [
            'type' => 'deposit',
            'amount' => $dto->amount,
            'balance_before' => $wallet->balance - $dto->amount,
            'balance_after' => $wallet->balance,
            'reference_type' => $dto->referenceType,
            'reference_id' => $dto->referenceId,
            'cfe_transaction_id' => $result->transactionId,
            'description' => $dto->description ?: 'Deposit',
        ]);

        event(new WalletCredited(
            walletId: $wallet->id,
            userId: $wallet->user_id,
            amount: $dto->amount,
            currency: $dto->currency,
            balanceAfter: $wallet->balance,
            transactionType: 'deposit',
            cfeTransactionId: $result->transactionId,
        ));

        return $wallet;
    }

    public function withdraw(WithdrawDto $dto): Wallet
    {
        $wallet = $this->findOrFail($dto->walletId);
        $this->validateOperation($wallet, $dto->amount);

        $holdDto = new HoldInstructionDto(
            accountId: $wallet->ledger_account_id ?? $wallet->id,
            amount: $dto->amount,
            currency: $dto->currency,
            reason: "Withdrawal hold: {$dto->referenceId}",
            referenceType: 'withdrawal',
            referenceId: $dto->referenceId ?: Str::ulid()->toBase32(),
            expiresAt: now()->addHours(2),
        );

        $holdResult = $this->holds->place($holdDto);
        if (!$holdResult->success) {
            throw new InsufficientBalanceException($dto->amount, $wallet->available_balance, $dto->currency);
        }

        $totalFee = 0;
        if ($dto->applyFee) {
            $feeDto = new FeeAssessmentDto(
                feeType: 'cash_withdrawal',
                accountId: $wallet->ledger_account_id ?? $wallet->id,
                transactionAmount: $dto->amount,
                currency: $dto->currency,
                referenceType: 'withdrawal',
                referenceId: $dto->referenceId,
            );
            $feeResult = $this->fees->apply($feeDto);
            if ($feeResult->applied) {
                $totalFee = $feeResult->feeAmount;
            }
        }

        $lines = [
            [
                'account_id' => $wallet->ledger_account_id ?? $wallet->id,
                'amount' => $dto->amount + $totalFee,
                'type' => 'debit',
                'description' => "Withdrawal from wallet {$wallet->id}",
            ],
        ];

        $instruction = new PostingInstructionDto(
            referenceType: 'withdrawal',
            referenceId: $dto->referenceId ?: Str::ulid()->toBase32(),
            description: $dto->description ?: 'Wallet withdrawal',
            lines: $lines,
            channel: $dto->channel,
            initiatedBy: $wallet->user_id,
            metadata: ['wallet_id' => $wallet->id, 'operation' => 'withdrawal', 'fee' => $totalFee],
        );

        $result = $this->posting->execute($instruction);
        if (!$result->success) {
            throw new \RuntimeException("Withdrawal failed: {$result->errorMessage}");
        }

        $this->holds->release($holdResult->holdId, 'withdrawal_completed');

        $totalDeduction = $dto->amount + $totalFee;
        $wallet->balance -= $totalDeduction;
        $wallet->available_balance = max(0, $wallet->available_balance - $totalDeduction);
        $wallet->daily_used += $dto->amount;
        $this->wallets->save($wallet);

        $this->recordTransaction($wallet, [
            'type' => 'withdrawal',
            'amount' => $dto->amount,
            'balance_before' => $wallet->balance + $totalDeduction,
            'balance_after' => $wallet->balance,
            'reference_type' => 'withdrawal',
            'reference_id' => $dto->referenceId,
            'cfe_transaction_id' => $result->transactionId,
            'description' => $dto->description ?: 'Withdrawal',
            'metadata' => ['fee' => $totalFee],
        ]);

        event(new WalletDebited(
            walletId: $wallet->id,
            userId: $wallet->user_id,
            amount: $dto->amount,
            fee: $totalFee,
            currency: $dto->currency,
            balanceAfter: $wallet->balance,
            transactionType: 'withdrawal',
            cfeTransactionId: $result->transactionId,
        ));

        return $wallet;
    }

    public function transfer(TransferDto $dto): array
    {
        $from = $this->findOrFail($dto->fromWalletId);
        $to = $this->findOrFail($dto->toWalletId);

        $this->validateOperation($from, $dto->amount);

        $referenceId = $dto->referenceId ?: Str::ulid()->toBase32();

        $holdDto = new HoldInstructionDto(
            accountId: $from->ledger_account_id ?? $from->id,
            amount: $dto->amount,
            currency: $dto->currency,
            reason: "Transfer hold: $referenceId",
            referenceType: 'transfer',
            referenceId: $referenceId,
            expiresAt: now()->addHours(2),
        );

        $holdResult = $this->holds->place($holdDto);
        if (!$holdResult->success) {
            throw new InsufficientBalanceException($dto->amount, $from->available_balance, $dto->currency);
        }

        $totalFee = 0;
        if ($dto->applyFee) {
            $feeDto = new FeeAssessmentDto(
                feeType: 'wallet_to_wallet',
                accountId: $from->ledger_account_id ?? $from->id,
                transactionAmount: $dto->amount,
                currency: $dto->currency,
                referenceType: 'transfer',
                referenceId: $referenceId,
            );
            $feeResult = $this->fees->apply($feeDto);
            if ($feeResult->applied) {
                $totalFee = $feeResult->feeAmount;
            }
        }

        $lines = [
            [
                'account_id' => $from->ledger_account_id ?? $from->id,
                'amount' => $dto->amount + $totalFee,
                'type' => 'debit',
                'description' => "Transfer out to wallet {$to->id}",
            ],
            [
                'account_id' => $to->ledger_account_id ?? $to->id,
                'amount' => $dto->amount,
                'type' => 'credit',
                'description' => "Transfer in from wallet {$from->id}",
            ],
        ];

        $instruction = new PostingInstructionDto(
            referenceType: 'transfer',
            referenceId: $referenceId,
            description: $dto->description ?: "Transfer from {$from->id} to {$to->id}",
            lines: $lines,
            channel: $dto->channel,
            initiatedBy: $from->user_id,
            metadata: [
                'from_wallet' => $from->id,
                'to_wallet' => $to->id,
                'fee' => $totalFee,
            ],
        );

        $result = $this->posting->execute($instruction);
        if (!$result->success) {
            throw new \RuntimeException("Transfer failed: {$result->errorMessage}");
        }

        $this->holds->release($holdResult->holdId, 'transfer_completed');

        $totalDeduction = $dto->amount + $totalFee;
        $from->balance -= $totalDeduction;
        $from->available_balance = max(0, $from->available_balance - $totalDeduction);
        $from->daily_used += $dto->amount;
        $this->wallets->save($from);

        $to->balance += $dto->amount;
        $to->available_balance += $dto->amount;
        $this->wallets->save($to);

        $this->recordTransaction($from, [
            'type' => 'transfer_out',
            'amount' => $dto->amount,
            'balance_before' => $from->balance + $totalDeduction,
            'balance_after' => $from->balance,
            'reference_type' => 'transfer',
            'reference_id' => $referenceId,
            'cfe_transaction_id' => $result->transactionId,
            'description' => "Transfer to {$to->id}",
            'related_wallet_id' => $to->id,
            'metadata' => ['fee' => $totalFee],
        ]);

        $this->recordTransaction($to, [
            'type' => 'transfer_in',
            'amount' => $dto->amount,
            'balance_before' => $to->balance - $dto->amount,
            'balance_after' => $to->balance,
            'reference_type' => 'transfer',
            'reference_id' => $referenceId,
            'cfe_transaction_id' => $result->transactionId,
            'description' => "Transfer from {$from->id}",
            'related_wallet_id' => $from->id,
        ]);

        event(new WalletTransferInitiated(
            fromWalletId: $from->id,
            toWalletId: $to->id,
            amount: $dto->amount,
            fee: $totalFee,
            currency: $dto->currency,
            cfeTransactionId: $result->transactionId,
        ));

        return [
            'from' => $from->fresh(),
            'to' => $to->fresh(),
            'cfe_transaction_id' => $result->transactionId,
            'fee' => $totalFee,
        ];
    }

    public function getBalance(string $walletId): array
    {
        $wallet = $this->findOrFail($walletId);
        return [
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
            'available_balance' => $wallet->available_balance,
            'currency' => $wallet->currency,
            'status' => $wallet->status,
        ];
    }

    public function getTransactions(string $walletId, int $limit = 20): array
    {
        $this->findOrFail($walletId);
        return $this->wallets->findTransactions($walletId, $limit)->toArray();
    }

    private function findOrFail(string $walletId): Wallet
    {
        $wallet = $this->wallets->findById($walletId);
        if (!$wallet) {
            throw new WalletNotFoundException($walletId);
        }
        if (!$wallet->isActive()) {
            throw new \RuntimeException("Wallet $walletId is {$wallet->status}");
        }
        return $wallet;
    }

    private function validateOperation(Wallet $wallet, int $amount): void
    {
        if (!$wallet->hasSufficientBalance($amount)) {
            throw new InsufficientBalanceException($amount, $wallet->available_balance, $wallet->currency);
        }
        if (!$wallet->withinDailyLimit($amount)) {
            throw new DailyLimitExceededException($wallet->daily_limit, $wallet->daily_used, $amount);
        }
    }

    private function recordTransaction(Wallet $wallet, array $data): WalletTransaction
    {
        $txn = new WalletTransaction();
        $txn->id = Str::ulid()->toBase32();
        $txn->wallet_id = $wallet->id;
        $txn->type = $data['type'];
        $txn->amount = $data['amount'];
        $txn->currency = $wallet->currency;
        $txn->balance_before = $data['balance_before'];
        $txn->balance_after = $data['balance_after'];
        $txn->reference_type = $data['reference_type'] ?? null;
        $txn->reference_id = $data['reference_id'] ?? null;
        $txn->cfe_transaction_id = $data['cfe_transaction_id'] ?? null;
        $txn->status = 'completed';
        $txn->description = $data['description'] ?? null;
        $txn->related_wallet_id = $data['related_wallet_id'] ?? null;
        $txn->metadata = $data['metadata'] ?? null;

        return $this->wallets->saveTransaction($txn);
    }
}
