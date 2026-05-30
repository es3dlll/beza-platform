<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Modules\Escrow\Models\EscrowAgreement;
use Modules\Escrow\Models\EscrowDispute;
use Modules\Escrow\Services\EscrowService;

final class EscrowAdminService
{
    public function __construct(
        private readonly EscrowService $escrow,
    ) {}

    public function dashboard(): array
    {
        $total = EscrowAgreement::count();
        $held = (int) EscrowAgreement::where('status', 'held')->sum('total_amount');
        $pendingDisputes = EscrowDispute::where('status', 'open')->count();
        $resolvedDisputes = EscrowDispute::where('status', 'resolved')->get();
        $avgResolutionTime = $resolvedDisputes->filter(fn($d) => $d->resolved_at && $d->created_at)
            ->avg(fn($d) => $d->resolved_at->diffInHours($d->created_at));

        return [
            'total_escrows' => $total,
            'held_amount' => $held,
            'pending_disputes' => $pendingDisputes,
            'avg_resolution_time_hours' => round($avgResolutionTime ?? 0, 2),
        ];
    }

    public function disputeQueue(): iterable
    {
        return EscrowDispute::with('escrow')->where('status', 'open')->orderByDesc('created_at')->get();
    }

    public function disputeDetail(string $id): array
    {
        return EscrowDispute::with('escrow.buyer', 'escrow.seller')->findOrFail($id)->toArray();
    }

    public function resolveDispute(string $id, string $resolution, string $action): void
    {
        $this->escrow->resolveDispute($id, 'admin', $resolution, $action);
    }

    public function listAgreements(?string $status): iterable
    {
        return $this->escrow->listByStatus($status ?? 'held');
    }

    public function agreementDetail(string $id): array
    {
        return EscrowAgreement::with(['buyer', 'seller', 'milestones', 'disputes'])->findOrFail($id)->toArray();
    }
}
