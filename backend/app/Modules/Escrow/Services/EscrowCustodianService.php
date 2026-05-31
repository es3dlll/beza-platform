<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Services;

use App\Models\User;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Escrow\Events\EscrowDisputed;
use App\Modules\Escrow\Events\EscrowFunded;
use App\Modules\Escrow\Events\EscrowInitiated;
use App\Modules\Escrow\Events\EscrowRefunded;
use App\Modules\Escrow\Events\EscrowReleased;
use App\Modules\Escrow\Exceptions\EscrowException;
use App\Modules\Escrow\Models\DisputeCase;
use App\Modules\Escrow\Models\EscrowTransaction;
use App\Modules\Ledger\Services\CoreFinancialEngine;
use App\Modules\Marketplace\Models\Seller;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Support\Facades\DB;

final class EscrowCustodianService
{
    public function __construct(
        private readonly CoreFinancialEngine $cfe,
    ) {}

    public function initiate(User $buyer, string $sellerId, int $amountFils, ?string $marketplaceRef = null): EscrowTransaction
    {
        $seller = \App\Modules\Marketplace\Models\Seller::find($sellerId);
        if (!$seller) throw new EscrowException('التاجر غير موجود');
        if ($amountFils <= 0) throw new EscrowException('المبلغ يجب أن يكون أكبر من صفر');

        $feeFils = max(1000, (int)round($amountFils * 0.01));
        $totalFils = $amountFils + $feeFils;

        $buyerWallet = Wallet::where('user_id', $buyer->id)->first();
        if (!$buyerWallet || $buyerWallet->balance_fils < $totalFils) {
            throw new EscrowException('رصيد غير كاف لتغطية المبلغ والرسوم');
        }

        $transaction = EscrowTransaction::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'marketplace_ref_id' => $marketplaceRef,
            'amount_fils' => $amountFils,
            'fee_fils' => $feeFils,
            'status' => 'initiated',
        ]);

        event(new EscrowInitiated($transaction, Money::fromFils($amountFils), $buyer->id));

        return $transaction->fresh();
    }

    public function fund(EscrowTransaction $transaction): EscrowTransaction
    {
        $escrowUserId = config('escrow.system_wallet_user_id', config('bills.system_wallet_user_id', 'escrow'));
        $escrowWallet = Wallet::firstOrCreate(
            ['user_id' => $escrowUserId],
            ['balance_fils' => 0, 'currency' => 'SYP'],
        );

        $buyerWallet = Wallet::where('user_id', $transaction->buyer_id)->first();
        if (!$buyerWallet) throw new EscrowException('محفظة المشتري غير موجودة');

        $totalFils = $transaction->amount_fils + $transaction->fee_fils;
        $amount = Money::fromFils($totalFils);

        $entry = $this->cfe->transfer(
            amount: $amount,
            from: $buyerWallet,
            to: $escrowWallet,
            description: 'حجز ضمان - ' . $transaction->id,
            referenceType: 'escrow',
            referenceId: $transaction->id,
        );

        $transaction->update([
            'status' => 'funded',
            'metadata' => array_merge($transaction->metadata ?? [], [
                'funded_at' => now()->toIso8601String(),
                'ledger_entry_id' => $entry->id,
            ]),
        ]);

        event(new EscrowFunded($transaction->fresh()));

        return $transaction->fresh();
    }

    public function release(EscrowTransaction $transaction): EscrowTransaction
    {
        $seller = Seller::find($transaction->seller_id);
        if (!$seller) throw new EscrowException('التاجر غير موجود');

        $escrowUserId = config('escrow.system_wallet_user_id', config('bills.system_wallet_user_id', 'escrow'));
        $escrowWallet = Wallet::where('user_id', $escrowUserId)->first();
        if (!$escrowWallet) throw new EscrowException('محفظة الضمان غير موجودة');

        $sellerWallet = Wallet::where('user_id', $seller->user_id)->firstOr(function () {
            throw new EscrowException('محفظة البائع غير موجودة');
        });

        $amount = Money::fromFils($transaction->amount_fils);

        $entry = $this->cfe->transfer(
            amount: $amount,
            from: $escrowWallet,
            to: $sellerWallet,
            description: 'إطلاق ضمان - ' . $transaction->id,
            referenceType: 'escrow_release',
            referenceId: $transaction->id,
        );

        $transaction->update([
            'status' => 'released',
            'metadata' => array_merge($transaction->metadata ?? [], [
                'released_at' => now()->toIso8601String(),
                'release_entry_id' => $entry->id,
            ]),
        ]);

        event(new EscrowReleased($transaction->fresh()));

        return $transaction->fresh();
    }

    public function refund(EscrowTransaction $transaction): EscrowTransaction
    {
        $escrowUserId = config('escrow.system_wallet_user_id', config('bills.system_wallet_user_id', 'escrow'));
        $escrowWallet = Wallet::where('user_id', $escrowUserId)->first();
        if (!$escrowWallet) throw new EscrowException('محفظة الضمان غير موجودة');

        $buyerWallet = Wallet::where('user_id', $transaction->buyer_id)->firstOr(function () {
            throw new EscrowException('محفظة المشتري غير موجودة');
        });

        $totalFils = $transaction->amount_fils + $transaction->fee_fils;
        $amount = Money::fromFils($totalFils);

        $entry = $this->cfe->transfer(
            amount: $amount,
            from: $escrowWallet,
            to: $buyerWallet,
            description: 'إرجاع ضمان - ' . $transaction->id,
            referenceType: 'escrow_refund',
            referenceId: $transaction->id,
        );

        $transaction->update([
            'status' => 'refunded',
            'metadata' => array_merge($transaction->metadata ?? [], [
                'refunded_at' => now()->toIso8601String(),
                'refund_entry_id' => $entry->id,
            ]),
        ]);

        event(new EscrowRefunded($transaction->fresh()));

        return $transaction->fresh();
    }

    public function openDispute(EscrowTransaction $transaction, string $raisedBy, string $reason, string $description, array $documents = []): DisputeCase
    {
        if ($transaction->status !== 'funded' && $transaction->status !== 'shipped' && $transaction->status !== 'delivered') {
            throw new EscrowException('النزاع مسموح فقط للمعاملات الممولة أو المشحونة أو المسلمة');
        }

        $dispute = DisputeCase::create([
            'escrow_transaction_id' => $transaction->id,
            'raised_by' => $raisedBy,
            'reason' => $reason,
            'description' => $description,
            'documents' => $documents,
            'status' => 'open',
        ]);

        $transaction->update(['status' => 'disputed']);

        event(new EscrowDisputed($transaction->fresh(), $dispute));

        return $dispute->fresh();
    }

    public function resolveDispute(DisputeCase $dispute, string $decision, string $reason, string $resolvedBy): array
    {
        $transaction = $dispute->transaction;
        if (!$transaction) throw new EscrowException('المعاملة غير موجودة');

        DB::transaction(function () use ($dispute, $transaction, $decision, $reason, $resolvedBy) {
            $dispute->update([
                'status' => 'resolved',
                'decision' => $decision,
                'decision_reason' => $reason,
                'resolved_by' => $resolvedBy,
                'resolved_at' => now(),
            ]);

            match ($decision) {
                'buyer' => $this->refund($transaction),
                'seller' => $this->release($transaction),
                'split' => $this->splitAmount($transaction, 50),
                default => throw new EscrowException('قرار غير معروف: ' . $decision),
            };
        });

        return [
            'dispute' => $dispute->fresh(),
            'transaction' => $transaction->fresh(),
        ];
    }

    private function splitAmount(EscrowTransaction $transaction, int $buyerPercent): void
    {
        $seller = Seller::find($transaction->seller_id);
        if (!$seller) throw new EscrowException('التاجر غير موجود');

        $escrowUserId = config('escrow.system_wallet_user_id', config('bills.system_wallet_user_id', 'escrow'));
        $escrowWallet = Wallet::where('user_id', $escrowUserId)->first();
        if (!$escrowWallet) throw new EscrowException('محفظة الضمان غير موجودة');

        $buyerShare = (int)round($transaction->amount_fils * $buyerPercent / 100);
        $sellerShare = $transaction->amount_fils - $buyerShare;

        $buyerWallet = Wallet::where('user_id', $transaction->buyer_id)->firstOr(fn() => throw new EscrowException('محفظة المشتري'));
        $sellerWallet = Wallet::where('user_id', $seller->user_id)->firstOr(fn() => throw new EscrowException('محفظة البائع'));

        if ($buyerShare > 0) {
            $this->cfe->transfer(Money::fromFils($buyerShare), $escrowWallet, $buyerWallet, 'تقسيم نزاع - حصة المشتري', 'escrow_split', $transaction->id);
        }
        if ($sellerShare > 0) {
            $this->cfe->transfer(Money::fromFils($sellerShare), $escrowWallet, $sellerWallet, 'تقسيم نزاع - حصة البائع', 'escrow_split', $transaction->id);
        }

        $transaction->update(['status' => 'split']);
    }
}
