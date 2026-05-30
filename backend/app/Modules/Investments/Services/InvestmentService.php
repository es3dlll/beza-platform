<?php

declare(strict_types=1);

namespace Modules\Investments\Services;

use Modules\Investments\Enums\SubscriptionStatus;
use Modules\Investments\Events\Redeemed;
use Modules\Investments\Events\Subscribed;
use Modules\Investments\Exceptions\FundNotFoundException;
use Modules\Investments\Exceptions\MinimumInvestmentException;
use Modules\Investments\Exceptions\SubscriptionNotFoundException;
use Modules\Investments\Models\InvestmentFund;
use Modules\Investments\Models\InvestmentNav;
use Modules\Investments\Models\InvestmentSubscription;
use Illuminate\Support\Str;

final class InvestmentService
{
    public function listFunds(): iterable
    {
        return InvestmentFund::where('is_active', true)->get();
    }

    public function findFundOrFail(string $id): InvestmentFund
    {
        $fund = InvestmentFund::find($id);
        if (!$fund) {
            throw new FundNotFoundException($id);
        }
        return $fund;
    }

    public function subscribe(string $userId, string $fundId, int $amount): InvestmentSubscription
    {
        $fund = $this->findFundOrFail($fundId);

        if ($amount < $fund->min_investment) {
            throw new MinimumInvestmentException($fund->min_investment, $amount);
        }

        $unitPrice = $fund->current_nav;
        $units = intdiv($amount * 100000, $unitPrice);

        $subscription = new InvestmentSubscription();
        $subscription->id = Str::ulid()->toBase32();
        $subscription->user_id = $userId;
        $subscription->fund_id = $fundId;
        $subscription->type = 'subscribe';
        $subscription->units = $units;
        $subscription->unit_price = $unitPrice;
        $subscription->total_amount = $amount;
        $subscription->status = SubscriptionStatus::SETTLED->value;
        $subscription->settled_at = now();
        $subscription->save();

        event(new Subscribed(
            subscriptionId: $subscription->id,
            userId: $userId,
            fundId: $fundId,
            amount: $amount,
            units: $units,
        ));

        return $subscription;
    }

    public function redeem(string $userId, string $fundId, int $units): InvestmentSubscription
    {
        $fund = $this->findFundOrFail($fundId);

        $amount = intdiv($units * $fund->current_nav, 100000);

        $subscription = new InvestmentSubscription();
        $subscription->id = Str::ulid()->toBase32();
        $subscription->user_id = $userId;
        $subscription->fund_id = $fundId;
        $subscription->type = 'redeem';
        $subscription->units = $units;
        $subscription->unit_price = $fund->current_nav;
        $subscription->total_amount = $amount;
        $subscription->status = SubscriptionStatus::REDEEMED->value;
        $subscription->settled_at = now();
        $subscription->save();

        event(new Redeemed(
            subscriptionId: $subscription->id,
            userId: $userId,
            fundId: $fundId,
            units: $units,
            amount: $amount,
        ));

        return $subscription;
    }

    public function listSubscriptions(string $userId): iterable
    {
        return InvestmentSubscription::where('user_id', $userId)
            ->with('fund')
            ->orderByDesc('created_at')
            ->get();
    }

    public function updateNav(string $fundId, int $nav): InvestmentNav
    {
        $fund = $this->findFundOrFail($fundId);

        $investmentNav = new InvestmentNav();
        $investmentNav->id = Str::ulid()->toBase32();
        $investmentNav->fund_id = $fundId;
        $investmentNav->nav = $nav;
        $investmentNav->recorded_at = now()->toDateString();
        $investmentNav->save();

        $fund->current_nav = $nav;
        $fund->nav_updated_at = now();
        $fund->save();

        return $investmentNav;
    }

    public function getNavHistory(string $fundId, int $days = 30): iterable
    {
        $this->findFundOrFail($fundId);

        return InvestmentNav::where('fund_id', $fundId)
            ->where('recorded_at', '>=', now()->subDays($days)->toDateString())
            ->orderByDesc('recorded_at')
            ->get();
    }

    public function calculateZakat(int $amount): int
    {
        return (int) round($amount * 0.025);
    }

    public function adminDashboard(): array
    {
        $totalAum = InvestmentFund::sum('current_nav');
        $totalSubscribers = InvestmentSubscription::distinct('user_id')->count('user_id');
        $fundsCount = InvestmentFund::count();
        $totalVolume = InvestmentSubscription::sum('total_amount');

        return [
            'total_aum' => $totalAum,
            'total_subscribers' => $totalSubscribers,
            'funds_count' => $fundsCount,
            'total_subscriptions_volume' => $totalVolume,
        ];
    }
}
