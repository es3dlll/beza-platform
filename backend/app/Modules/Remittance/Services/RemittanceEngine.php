<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Services;

use App\Modules\Remittance\Enums\ComplianceTier;
use App\Modules\Remittance\Enums\TransferStatus;
use App\Modules\Remittance\Events\CancelExpiredTransfer;
use App\Modules\Remittance\Events\ComplianceReviewRequired;
use App\Modules\Remittance\Events\FXRateLocked;
use App\Modules\Remittance\Events\InitiateLedgerTransfer;
use App\Modules\Remittance\Events\ReleaseFXLock;
use App\Modules\Remittance\Events\RemittanceCompleted;
use App\Modules\Remittance\Events\RequestFXQuote;
use App\Modules\Remittance\Exceptions\ComplianceBlockedException;
use App\Modules\Remittance\Exceptions\IdempotencyKeyMismatchException;
use App\Modules\Remittance\Exceptions\RateExpiredException;
use App\Modules\Remittance\Models\Remittance;
use App\Modules\Remittance\ValueObjects\RemittanceId;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class RemittanceEngine
{
    private const CACHE_TTL = 86400;
    private const IDEMPOTENCY_PREFIX = 'remit_idem_';
    private const COMPLIANCE_TIER_KEY = 'remit_compliance_';

    public function __construct(
        private readonly Cache $cache,
        private readonly RemittanceQuoteService $quoteService,
    ) {}

    public function initiate(
        string $idempotencyKey,
        string $senderId,
        string $recipientName,
        string $recipientPhone,
        string $recipientCountry,
        string $fromCurrency,
        string $toCurrency,
        int $sourceAmount,
    ): Remittance {
        if ($this->isDuplicate($idempotencyKey)) {
            $existing = Remittance::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
            throw new IdempotencyKeyMismatchException($idempotencyKey);
        }

        $this->markIdempotency($idempotencyKey);

        $remittanceId = RemittanceId::generate()->toString();
        $quote = $this->quoteService->calculateQuote($fromCurrency, $toCurrency, $sourceAmount);
        $tier = $this->resolveComplianceTier($senderId);

        if (ComplianceTier::isBlocked($tier)) {
            throw new ComplianceBlockedException('Sender is in sanctioned list');
        }

        $remittance = Remittance::create([
            'remittance_id' => $remittanceId,
            'idempotency_key' => $idempotencyKey,
            'sender_id' => $senderId,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'recipient_country' => $recipientCountry,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'source_amount' => $sourceAmount,
            'destination_amount' => $quote['destination_amount'],
            'buy_rate' => $quote['buy_rate'],
            'spread_bps' => $quote['spread_bps'],
            'fee_amount' => $quote['fee_amount'],
            'total_charge' => $quote['total_charge'],
            'status' => TransferStatus::PENDING,
            'compliance_tier' => $tier,
            'expires_at' => now()->addSeconds(60),
            'audit_trail' => [['status' => TransferStatus::PENDING, 'at' => now()->toIso8601String()]],
        ]);

        Event::dispatch(new RequestFXQuote(
            remittanceId: $remittanceId,
            fromCurrency: $fromCurrency,
            toCurrency: $toCurrency,
            amount: $sourceAmount,
        ));

        Log::channel('audit')->info('REMITTANCE_INITIATED', [
            'remittance_id' => $remittanceId,
            'sender_id' => $senderId,
            'amount' => $sourceAmount,
            'compliance_tier' => $tier,
        ]);

        return $remittance;
    }

    public function lockFxRate(string $remittanceId, int $buyRate, int $sellRate, int $spreadBps): void
    {
        $remittance = $this->findOrFail($remittanceId);
        $this->assertState($remittance, TransferStatus::PENDING);

        $remittance->update([
            'buy_rate' => $buyRate,
            'status' => TransferStatus::FX_LOCKED,
            'expires_at' => now()->addSeconds(60),
        ]);
        $this->appendAudit($remittance, TransferStatus::FX_LOCKED);

        Event::dispatch(new FXRateLocked(
            remittanceId: $remittanceId,
            fromCurrency: $remittance->from_currency,
            toCurrency: $remittance->to_currency,
            buyRate: $buyRate,
            sellRate: $sellRate,
            spreadBps: $spreadBps,
            expiresAt: now()->addSeconds(60)->unix(),
        ));
    }

    public function runComplianceCheck(string $remittanceId): void
    {
        $remittance = $this->findOrFail($remittanceId);
        $this->assertState($remittance, TransferStatus::FX_LOCKED);

        if ($remittance->expires_at && $remittance->expires_at->isPast()) {
            $this->expireTransfer($remittance);
            throw new RateExpiredException($remittanceId);
        }

        $remittance->update(['status' => TransferStatus::COMPLIANCE_CHECK]);
        $this->appendAudit($remittance, TransferStatus::COMPLIANCE_CHECK);

        $tier = $remittance->compliance_tier;
        $amount = $remittance->source_amount;

        if (ComplianceTier::requiresManualReview($tier, $amount)) {
            Event::dispatch(new ComplianceReviewRequired(
                remittanceId: $remittanceId,
                senderId: $remittance->sender_id,
                amount: $amount,
                reason: "Manual review required for tier {$tier} at amount {$amount}",
            ));
            return;
        }

        $this->approveAndTransfer($remittance);
    }

    public function approveCompliance(string $remittanceId): void
    {
        $remittance = $this->findOrFail($remittanceId);
        $this->assertState($remittance, TransferStatus::COMPLIANCE_CHECK);
        $this->approveAndTransfer($remittance);
    }

    public function completeTransfer(string $remittanceId): void
    {
        $remittance = $this->findOrFail($remittanceId);
        $this->assertState($remittance, TransferStatus::PROCESSING);

        $remittance->update([
            'status' => TransferStatus::SETTLED,
            'completed_at' => now(),
        ]);
        $this->appendAudit($remittance, TransferStatus::SETTLED);

        Event::dispatch(new RemittanceCompleted(
            remittanceId: $remittanceId,
            status: TransferStatus::SETTLED,
            completedAt: now()->unix(),
        ));

        Log::channel('audit')->info('REMITTANCE_COMPLETED', [
            'remittance_id' => $remittanceId,
        ]);
    }

    public function failTransfer(string $remittanceId, string $reason): void
    {
        $remittance = $this->findOrFail($remittanceId);
        $this->assertNotTerminal($remittance);

        $remittance->update([
            'status' => TransferStatus::REJECTED,
            'cancellation_reason' => $reason,
        ]);
        $this->appendAudit($remittance, TransferStatus::REJECTED);

        Event::dispatch(new ReleaseFXLock(
            remittanceId: $remittanceId,
            reason: $reason,
        ));

        Event::dispatch(new RemittanceCompleted(
            remittanceId: $remittanceId,
            status: TransferStatus::REJECTED,
            completedAt: now()->unix(),
        ));
    }

    public function cancelTransfer(string $remittanceId, string $reason): void
    {
        $remittance = $this->findOrFail($remittanceId);
        $this->assertNotTerminal($remittance);

        $remittance->update([
            'status' => TransferStatus::CANCELLED,
            'cancellation_reason' => $reason,
        ]);
        $this->appendAudit($remittance, TransferStatus::CANCELLED);

        Event::dispatch(new ReleaseFXLock(
            remittanceId: $remittanceId,
            reason: $reason,
        ));
    }

    public function expirePendingTransfers(): int
    {
        $expired = Remittance::whereIn('status', [
            TransferStatus::PENDING,
            TransferStatus::FX_LOCKED,
        ])
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $remittance) {
            $this->expireTransfer($remittance);
        }

        return $expired->count();
    }

    private function approveAndTransfer(Remittance $remittance): void
    {
        $remittance->update(['status' => TransferStatus::PROCESSING]);
        $this->appendAudit($remittance, TransferStatus::PROCESSING);

        Event::dispatch(new InitiateLedgerTransfer(
            remittanceId: $remittance->remittance_id,
            idempotencyKey: $remittance->idempotency_key,
            senderId: $remittance->sender_id,
            fromCurrency: $remittance->from_currency,
            toCurrency: $remittance->to_currency,
            sourceAmount: $remittance->source_amount,
            destinationAmount: $remittance->destination_amount,
            feeAmount: $remittance->fee_amount,
            totalCharge: $remittance->total_charge,
        ));
    }

    private function expireTransfer(Remittance $remittance): void
    {
        $remittance->update([
            'status' => TransferStatus::EXPIRED,
            'cancellation_reason' => 'Rate lock expired',
        ]);
        $this->appendAudit($remittance, TransferStatus::EXPIRED);

        Event::dispatch(new CancelExpiredTransfer(
            remittanceId: $remittance->remittance_id,
        ));
    }

    private function resolveComplianceTier(string $senderId): string
    {
        return $this->cache->get(
            self::COMPLIANCE_TIER_KEY . $senderId,
            ComplianceTier::LOW,
        );
    }

    private function isDuplicate(string $key): bool
    {
        return $this->cache->has(self::IDEMPOTENCY_PREFIX . $key);
    }

    private function markIdempotency(string $key): void
    {
        $this->cache->put(self::IDEMPOTENCY_PREFIX . $key, true, self::CACHE_TTL);
    }

    private function findOrFail(string $remittanceId): Remittance
    {
        return Remittance::where('remittance_id', $remittanceId)->firstOrFail();
    }

    private function assertState(Remittance $remittance, string $expected): void
    {
        TransferStatus::assertTransition($remittance->status, $expected);
    }

    private function assertNotTerminal(Remittance $remittance): void
    {
        if ($remittance->isTerminal()) {
            throw new \RuntimeException("Remittance {$remittance->remittance_id} is already in terminal state: {$remittance->status}");
        }
    }

    private function appendAudit(Remittance $remittance, string $status): void
    {
        $trail = $remittance->audit_trail ?? [];
        $trail[] = ['status' => $status, 'at' => now()->toIso8601String()];
        $remittance->updateQuietly(['audit_trail' => $trail]);
    }
}
