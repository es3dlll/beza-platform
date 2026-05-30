<?php

declare(strict_types=1);

namespace Modules\Wallet\Services;

use Modules\Ledger\Models\LedgerAccount;
use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\DTOs\FeeAssessmentDto;
use Modules\CoreFinancialEngine\DTOs\HoldInstructionDto;
use Modules\CoreFinancialEngine\Services\PostingEngine;
use Modules\CoreFinancialEngine\Services\FeeEngine;
use Modules\CoreFinancialEngine\Services\HoldEngine;
use Modules\Wallet\Contracts\LedgerAclInterface;
use Illuminate\Support\Str;

final class LedgerAclService implements LedgerAclInterface
{
    public function __construct(
        private readonly PostingEngine $posting,
        private readonly FeeEngine $fees,
        private readonly HoldEngine $holds,
    ) {}

    public function ensureAccount(string $walletId, string $userId, string $currency): string
    {
        $account = LedgerAccount::find($walletId);
        if ($account) {
            return $account->id;
        }

        $account = LedgerAccount::create([
            'id' => $walletId,
            'account_number' => 'WALLET-' . substr($walletId, -12),
            'name' => "Wallet {$walletId}",
            'type' => 'liability',
            'currency' => $currency,
            'balance' => 0,
            'available_balance' => 0,
            'module' => 'wallet',
        ]);

        return $account->id;
    }

    public function postDeposit(
        string $accountId, int $amount, string $referenceType, string $referenceId,
        string $description, string $channel, string $initiatedBy, array $metadata = []
    ): object {
        $instruction = new PostingInstructionDto(
            referenceType: $referenceType,
            referenceId: $referenceId ?: Str::ulid()->toBase32(),
            description: $description,
            lines: [[
                'account_id' => $accountId,
                'amount' => $amount,
                'type' => 'credit',
                'description' => "Deposit to wallet",
            ]],
            channel: $channel,
            initiatedBy: $initiatedBy,
            metadata: array_merge($metadata, ['operation' => 'deposit']),
        );

        $result = $this->posting->execute($instruction);
        if (!$result->success) {
            throw new \RuntimeException("Deposit failed: {$result->errorMessage}");
        }

        return $result;
    }

    public function postWithdrawal(
        string $accountId, int $amount, string $referenceType, string $referenceId,
        string $description, string $channel, string $initiatedBy, array $metadata = []
    ): object {
        $instruction = new PostingInstructionDto(
            referenceType: $referenceType,
            referenceId: $referenceId ?: Str::ulid()->toBase32(),
            description: $description,
            lines: [[
                'account_id' => $accountId,
                'amount' => $amount,
                'type' => 'debit',
                'description' => "Withdrawal from wallet",
            ]],
            channel: $channel,
            initiatedBy: $initiatedBy,
            metadata: array_merge($metadata, ['operation' => 'withdrawal']),
        );

        $result = $this->posting->execute($instruction);
        if (!$result->success) {
            throw new \RuntimeException("Withdrawal failed: {$result->errorMessage}");
        }

        return $result;
    }

    public function postTransfer(
        string $fromAccountId, string $toAccountId, int $amount, string $referenceId,
        string $description, string $channel, string $initiatedBy, array $metadata = []
    ): object {
        $instruction = new PostingInstructionDto(
            referenceType: 'transfer',
            referenceId: $referenceId,
            description: $description,
            lines: [
                ['account_id' => $fromAccountId, 'amount' => $amount, 'type' => 'debit', 'description' => "Transfer out"],
                ['account_id' => $toAccountId, 'amount' => $amount, 'type' => 'credit', 'description' => "Transfer in"],
            ],
            channel: $channel,
            initiatedBy: $initiatedBy,
            metadata: array_merge($metadata, ['operation' => 'transfer']),
        );

        $result = $this->posting->execute($instruction);
        if (!$result->success) {
            throw new \RuntimeException("Transfer failed: {$result->errorMessage}");
        }

        return $result;
    }

    public function placeHold(
        string $accountId, int $amount, string $currency, string $reason,
        string $referenceType, string $referenceId, \DateTimeImmutable $expiresAt
    ): object {
        $dto = new HoldInstructionDto(
            accountId: $accountId,
            amount: $amount,
            currency: $currency,
            reason: $reason,
            referenceType: $referenceType,
            referenceId: $referenceId,
            expiresAt: $expiresAt,
        );

        $result = $this->holds->place($dto);
        if (!$result->success) {
            throw new \RuntimeException("Hold failed: {$result->errorMessage}");
        }

        return $result;
    }

    public function releaseHold(string $holdId, string $reason): void
    {
        $this->holds->release($holdId, $reason);
    }

    public function calculateFee(
        string $feeType, string $accountId, int $amount, string $currency,
        string $referenceType, ?string $referenceId = null
    ): object {
        $dto = new FeeAssessmentDto(
            feeType: $feeType,
            accountId: $accountId,
            transactionAmount: $amount,
            currency: $currency,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );

        return $this->fees->calculate($dto);
    }

    public function applyFee(
        string $feeType, string $accountId, int $amount, string $currency,
        string $referenceType, ?string $referenceId = null
    ): object {
        $dto = new FeeAssessmentDto(
            feeType: $feeType,
            accountId: $accountId,
            transactionAmount: $amount,
            currency: $currency,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );

        return $this->fees->apply($dto);
    }
}
