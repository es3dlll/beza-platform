<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Modules\Investments\Models\InvestmentFund;
use Modules\Investments\Models\InvestmentSubscription;
use Modules\Investments\Services\InvestmentService;

final class InvestmentsAdminService
{
    public function __construct(
        private readonly InvestmentService $investments,
    ) {}

    public function dashboard(): array
    {
        return $this->investments->adminDashboard();
    }

    public function listFunds(): iterable
    {
        return InvestmentFund::all();
    }

    public function fundDetail(string $id): array
    {
        $fund = InvestmentFund::findOrFail($id);
        $subscriberCount = InvestmentSubscription::where('fund_id', $id)
            ->distinct('user_id')->count('user_id');
        return [
            'fund' => $fund,
            'subscriber_count' => $subscriberCount,
        ];
    }

    public function recordNav(string $fundId, int $nav): void
    {
        $this->investments->updateNav($fundId, $nav);
    }

    public function subscriptionQueue(): iterable
    {
        return InvestmentSubscription::with(['user', 'fund'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
    }

    public function settleSubscription(string $id): void
    {
        InvestmentSubscription::where('id', $id)->update([
            'status' => 'settled',
            'settled_at' => now(),
        ]);
    }

    public function reconcileReport(string $fundId, ?string $from, ?string $to): array
    {
        $q = InvestmentSubscription::where('fund_id', $fundId);
        if ($from) $q->whereDate('created_at', '>=', $from);
        if ($to) $q->whereDate('created_at', '<=', $to);

        $subscriptions = $q->orderByDesc('created_at')->get();
        $totalUnits = (int) $subscriptions->where('type', 'subscribe')->sum('units');
        $totalRedeemed = (int) $subscriptions->where('type', 'redeem')->sum('units');
        $netUnits = $totalUnits - $totalRedeemed;

        return [
            'fund_id' => $fundId,
            'total_subscriptions' => $subscriptions->where('type', 'subscribe')->count(),
            'total_redemptions' => $subscriptions->where('type', 'redeem')->count(),
            'total_units_subscribed' => $totalUnits,
            'total_units_redeemed' => $totalRedeemed,
            'net_units' => $netUnits,
            'from' => $from,
            'to' => $to,
        ];
    }
}
