<?php

declare(strict_types=1);

namespace Modules\Takaful\Services;

use Modules\Takaful\Enums\ClaimStatus;
use Modules\Takaful\Enums\PolicyStatus;
use Modules\Takaful\Events\ClaimApproved;
use Modules\Takaful\Events\ClaimFiled;
use Modules\Takaful\Events\PolicySubscribed;
use Modules\Takaful\Exceptions\ClaimNotFoundException;
use Modules\Takaful\Exceptions\PolicyExpiredException;
use Modules\Takaful\Exceptions\PolicyNotFoundException;
use Modules\Takaful\Exceptions\TakafulProductNotFoundException;
use Modules\Takaful\Models\TakafulClaim;
use Modules\Takaful\Models\TakafulPolicy;
use Modules\Takaful\Models\TakafulProduct;
use Illuminate\Support\Str;

final class TakafulService
{
    public function listProducts(?string $type = null): iterable
    {
        $query = TakafulProduct::where('is_active', true);

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    public function subscribe(
        string $userId,
        string $productId,
        int $premium,
        int $coverageAmount,
        string $startDate,
        string $endDate,
    ): TakafulPolicy {
        $product = TakafulProduct::find($productId);

        if (!$product) {
            throw new TakafulProductNotFoundException($productId);
        }

        $policy = new TakafulPolicy();
        $policy->id = (string) Str::ulid();
        $policy->user_id = $userId;
        $policy->product_id = $productId;
        $policy->policy_number = 'TPL-' . strtoupper(Str::random(10));
        $policy->premium = $premium;
        $policy->coverage_amount = $coverageAmount;
        $policy->start_date = $startDate;
        $policy->end_date = $endDate;
        $policy->status = PolicyStatus::Active->value;
        $policy->save();

        PolicySubscribed::dispatch($policy->id, $userId, $productId, $premium);

        return $policy;
    }

    public function listPolicies(string $userId): iterable
    {
        return TakafulPolicy::where('user_id', $userId)->with('product')->get();
    }

    public function findPolicyOrFail(string $id): TakafulPolicy
    {
        $policy = TakafulPolicy::with('product')->find($id);

        if (!$policy) {
            throw new PolicyNotFoundException($id);
        }

        return $policy;
    }

    public function fileClaim(string $policyId, int $amount, string $reason): TakafulClaim
    {
        $policy = TakafulPolicy::find($policyId);

        if (!$policy) {
            throw new PolicyNotFoundException($policyId);
        }

        if ($policy->status !== PolicyStatus::Active->value) {
            throw new PolicyExpiredException($policyId);
        }

        $claim = new TakafulClaim();
        $claim->id = (string) Str::ulid();
        $claim->policy_id = $policyId;
        $claim->claim_number = 'CLM-' . strtoupper(Str::random(10));
        $claim->amount = $amount;
        $claim->reason = $reason;
        $claim->status = ClaimStatus::Filed->value;
        $claim->filed_at = now();
        $claim->save();

        ClaimFiled::dispatch($claim->id, $policyId, $amount);

        return $claim;
    }

    public function approveClaim(string $claimId, int $approvedAmount): TakafulClaim
    {
        $claim = TakafulClaim::find($claimId);

        if (!$claim) {
            throw new ClaimNotFoundException($claimId);
        }

        $claim->status = ClaimStatus::Approved->value;
        $claim->approved_amount = $approvedAmount;
        $claim->approved_at = now();
        $claim->save();

        ClaimApproved::dispatch($claim->id, $approvedAmount);

        return $claim;
    }

    public function rejectClaim(string $claimId, string $reason): TakafulClaim
    {
        $claim = TakafulClaim::find($claimId);

        if (!$claim) {
            throw new ClaimNotFoundException($claimId);
        }

        $claim->status = ClaimStatus::Rejected->value;
        $claim->rejected_at = now();
        $claim->rejection_reason = $reason;
        $claim->save();

        return $claim;
    }

    public function listClaims(string $userId): iterable
    {
        return TakafulClaim::whereHas('policy', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with('policy.product')->get();
    }

    public function adminDashboard(): array
    {
        $totalPolicies = TakafulPolicy::count();
        $activePolicies = TakafulPolicy::where('status', PolicyStatus::Active->value)->count();
        $totalClaims = TakafulClaim::count();
        $approvedClaims = TakafulClaim::where('status', ClaimStatus::Approved->value)->count();

        $totalPremium = TakafulPolicy::sum('premium');
        $totalApprovedAmount = TakafulClaim::where('status', ClaimStatus::Approved->value)->sum('approved_amount');

        $lossRatio = $totalPremium > 0
            ? round(($totalApprovedAmount / $totalPremium) * 100, 2)
            : 0;

        return [
            'total_policies' => $totalPolicies,
            'active_policies' => $activePolicies,
            'total_claims' => $totalClaims,
            'approved_claims' => $approvedClaims,
            'loss_ratio' => $lossRatio,
        ];
    }
}
