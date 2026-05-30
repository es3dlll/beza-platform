<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Modules\Takaful\Models\TakafulClaim;
use Modules\Takaful\Models\TakafulPolicy;
use Modules\Takaful\Services\TakafulService;

final class TakafulAdminService
{
    public function __construct(
        private readonly TakafulService $takaful,
    ) {}

    public function dashboard(): array
    {
        return $this->takaful->adminDashboard();
    }

    public function listPolicies(?string $status): iterable
    {
        $q = TakafulPolicy::with('product')->orderByDesc('created_at');
        if ($status) $q->where('status', $status);
        return $q->get();
    }

    public function policyDetail(string $id): array
    {
        return TakafulPolicy::with(['product', 'claims'])->findOrFail($id)->toArray();
    }

    public function listClaims(?string $status): iterable
    {
        $q = TakafulClaim::with('policy.product')->orderByDesc('created_at');
        if ($status) $q->where('status', $status);
        return $q->get();
    }

    public function claimDetail(string $id): array
    {
        return TakafulClaim::with('policy.product')->findOrFail($id)->toArray();
    }

    public function approveClaim(string $id, int $approvedAmount): void
    {
        $this->takaful->approveClaim($id, $approvedAmount);
    }

    public function rejectClaim(string $id, string $reason): void
    {
        $this->takaful->rejectClaim($id, $reason);
    }

    public function claimApprovalQueue(): iterable
    {
        return TakafulClaim::with('policy.product')->where('status', 'under_review')->orderByDesc('created_at')->get();
    }
}
