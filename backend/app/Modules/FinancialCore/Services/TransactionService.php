<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Services;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use App\Modules\FinancialCore\Models\Transaction;
use App\Modules\FinancialCore\Services\Engines\FeeEngine;
use App\Modules\FinancialCore\Services\Engines\HoldEngine;
use App\Modules\FinancialCore\Services\Engines\PostingEngine;
use App\Modules\FinancialCore\Services\Engines\ReversalEngine;
use Illuminate\Pagination\LengthAwarePaginator;

final class TransactionService
{
    public function __construct(
        private readonly HoldEngine $holdEngine,
        private readonly PostingEngine $postingEngine,
        private readonly FeeEngine $feeEngine,
        private readonly ReversalEngine $reversalEngine,
        private readonly IdempotencyService $idempotencyService,
    ) {}

    public function transfer(
        string $fromWalletId,
        string $toWalletId,
        int $amount,
        string $currency = 'SYP',
        ?string $idempotencyKey = null,
        ?array $fee = null,
    ): array {
        $money = Money::fromInt($amount, Currency::from($currency));

        if ($amount > 100000) {
            $hold = $this->holdEngine->placeHold($fromWalletId, $money, 'Transfer', 'تحويل', $currency, $idempotencyKey);
            return $hold;
        }

        $result = $this->postingEngine->execute(
            fromWalletId: $fromWalletId,
            toWalletId: $toWalletId,
            amount: $money,
            description: 'Transfer',
            descriptionAr: 'تحويل',
            currency: $currency,
            idempotencyKey: $idempotencyKey,
            feeCallback: $fee !== null ? function ($tx, $amount) use ($fee) {
                $this->feeEngine->applyFee($amount, $fee['rule_id'], $tx->id, $fee['description'] ?? 'Fee', $fee['description_ar'] ?? 'رسوم');
            } : null,
        );

        return $result;
    }

    public function deposit(string $walletId, int $amount, string $currency = 'SYP', ?string $idempotencyKey = null): array
    {
        $money = Money::fromInt($amount, Currency::from($currency));
        return $this->postingEngine->execute(
            fromWalletId: $walletId,
            toWalletId: $walletId,
            amount: $money,
            description: 'Deposit',
            descriptionAr: 'إيداع',
            currency: $currency,
            idempotencyKey: $idempotencyKey,
        );
    }

    public function withdraw(string $walletId, int $amount, string $currency = 'SYP', ?string $idempotencyKey = null): array
    {
        $money = Money::fromInt($amount, Currency::from($currency));
        return $this->postingEngine->execute(
            fromWalletId: $walletId,
            toWalletId: $walletId,
            amount: $money,
            description: 'Withdrawal',
            descriptionAr: 'سحب',
            currency: $currency,
            idempotencyKey: $idempotencyKey,
        );
    }

    public function reverse(string $transactionId, string $reason, string $reasonAr, ?string $idempotencyKey = null): array
    {
        return $this->reversalEngine->reverse($transactionId, $reason, $reasonAr, $idempotencyKey);
    }

    public function getTransaction(string $id): Transaction
    {
        return Transaction::findOrFail($id);
    }

    public function getWalletTransactions(string $walletId, int $perPage = 15): LengthAwarePaginator
    {
        return Transaction::byWallet($walletId)->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
