<?php

declare(strict_types=1);

namespace Modules\Escrow\Services;

use Illuminate\Support\Str;
use Modules\Escrow\Enums\EscrowStatus;
use Modules\Escrow\Events\EscrowCreated;
use Modules\Escrow\Events\EscrowReleased;
use Modules\Escrow\Events\EscrowDisputed;
use Modules\Escrow\Events\EscrowResolved;
use Modules\Escrow\Exceptions\EscrowNotFoundException;
use Modules\Escrow\Exceptions\EscrowAlreadyResolvedException;
use Modules\Escrow\Exceptions\EscrowExpiredException;
use Modules\Escrow\Exceptions\EscrowDisputeNotFoundException;
use Modules\Escrow\Models\EscrowAgreement;
use Modules\Escrow\Models\EscrowMilestone;
use Modules\Escrow\Models\EscrowDispute;

final class EscrowService
{
    public function create(string $buyerId, string $sellerId, int $amount, string $referenceType, string $referenceId, ?string $description = null, ?int $feePercent = 1): EscrowAgreement
    {
        $feeAmount = (int) round($amount * $feePercent / 100);
        $netAmount = $amount - $feeAmount;
        $id = (string) Str::ulid();

        $agreement = EscrowAgreement::create([
            'id' => $id,
            'buyer_id' => $buyerId,
            'seller_id' => $sellerId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'total_amount' => $amount,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'currency' => 'SYP',
            'status' => EscrowStatus::PENDING->value,
            'description' => $description,
            'expires_at' => now()->addDays(30),
        ]);

        $cfeHoldId = (string) Str::ulid();
        $agreement->update(['cfe_hold_id' => $cfeHoldId, 'status' => EscrowStatus::HELD->value]);

        EscrowCreated::dispatch($agreement->id, $buyerId, $sellerId, $amount);

        return $agreement;
    }

    public function release(string $id): EscrowAgreement
    {
        $agreement = $this->findOrFail($id);

        if ($agreement->status === EscrowStatus::RELEASED->value) throw new EscrowAlreadyResolvedException;
        if (now()->isAfter($agreement->expires_at)) throw new EscrowExpiredException;

        $agreement->update([
            'status' => EscrowStatus::RELEASED->value,
            'completed_at' => now(),
        ]);

        EscrowReleased::dispatch($agreement->id, $agreement->cfe_hold_id);

        return $agreement;
    }

    public function refund(string $id): EscrowAgreement
    {
        $agreement = $this->findOrFail($id);

        if ($agreement->status === EscrowStatus::REFUNDED->value) throw new EscrowAlreadyResolvedException;
        if (now()->isAfter($agreement->expires_at)) throw new EscrowExpiredException;

        $agreement->update([
            'status' => EscrowStatus::REFUNDED->value,
            'completed_at' => now(),
        ]);

        return $agreement;
    }

    public function openDispute(string $escrowId, string $userId, string $reason): EscrowDispute
    {
        $this->findOrFail($escrowId);

        $dispute = EscrowDispute::create([
            'id' => (string) Str::ulid(),
            'escrow_id' => $escrowId,
            'opened_by' => $userId,
            'reason' => $reason,
            'status' => 'open',
        ]);

        EscrowAgreement::where('id', $escrowId)->update(['status' => EscrowStatus::DISPUTED->value]);

        EscrowDisputed::dispatch($dispute->id, $escrowId, $reason);

        return $dispute;
    }

    public function resolveDispute(string $disputeId, string $resolvedBy, string $resolution, string $action = 'release'): EscrowDispute
    {
        $dispute = EscrowDispute::find($disputeId);
        if (!$dispute) throw new EscrowDisputeNotFoundException($disputeId);

        $dispute->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
        ]);

        $newStatus = $action === 'release' ? EscrowStatus::RELEASED : EscrowStatus::REFUNDED;
        EscrowAgreement::where('id', $dispute->escrow_id)->update([
            'status' => $newStatus->value,
            'completed_at' => now(),
        ]);

        EscrowResolved::dispatch($dispute->escrow_id, $disputeId, $resolution);

        return $dispute;
    }

    public function findOrFail(string $id): EscrowAgreement
    {
        $agreement = EscrowAgreement::find($id);
        if (!$agreement) throw new EscrowNotFoundException($id);
        return $agreement;
    }

    public function listByUser(string $userId): iterable
    {
        return EscrowAgreement::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function listByStatus(string $status, int $perPage = 15): iterable
    {
        return EscrowAgreement::where('status', $status)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
