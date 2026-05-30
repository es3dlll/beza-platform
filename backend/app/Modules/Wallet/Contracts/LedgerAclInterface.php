<?php

declare(strict_types=1);

namespace Modules\Wallet\Contracts;

interface LedgerAclInterface
{
    public function ensureAccount(string $walletId, string $userId, string $currency): string;
    public function postDeposit(string $accountId, int $amount, string $referenceType, string $referenceId, string $description, string $channel, string $initiatedBy, array $metadata = []): object;
    public function postWithdrawal(string $accountId, int $amount, string $referenceType, string $referenceId, string $description, string $channel, string $initiatedBy, array $metadata = []): object;
    public function postTransfer(string $fromAccountId, string $toAccountId, int $amount, string $referenceId, string $description, string $channel, string $initiatedBy, array $metadata = []): object;
    public function placeHold(string $accountId, int $amount, string $currency, string $reason, string $referenceType, string $referenceId, \DateTimeImmutable $expiresAt): object;
    public function releaseHold(string $holdId, string $reason): void;
    public function calculateFee(string $feeType, string $accountId, int $amount, string $currency, string $referenceType, ?string $referenceId = null): object;
    public function applyFee(string $feeType, string $accountId, int $amount, string $currency, string $referenceType, ?string $referenceId = null): object;
}
