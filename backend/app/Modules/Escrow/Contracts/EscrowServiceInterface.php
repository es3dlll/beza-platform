<?php

declare(strict_types=1);

namespace Modules\Escrow\Contracts;

use Modules\Escrow\Models\EscrowAgreement;
use Modules\Escrow\Models\EscrowDispute;

interface EscrowServiceInterface
{
    public function create(string $buyerId, string $sellerId, int $amount, string $referenceType, string $referenceId, ?string $description = null, ?int $feePercent = 1): EscrowAgreement;

    public function release(string $id): EscrowAgreement;

    public function refund(string $id): EscrowAgreement;

    public function openDispute(string $escrowId, string $userId, string $reason): EscrowDispute;

    public function resolveDispute(string $disputeId, string $resolvedBy, string $resolution, string $action = 'release'): EscrowDispute;

    public function findOrFail(string $id): EscrowAgreement;

    public function listByUser(string $userId): iterable;

    public function listByStatus(string $status, int $perPage = 15): iterable;
}
