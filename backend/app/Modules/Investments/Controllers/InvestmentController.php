<?php

declare(strict_types=1);

namespace Modules\Investments\Controllers;

use Modules\Investments\Services\InvestmentService;
use Modules\Investments\Exceptions\FundNotFoundException;
use Modules\Investments\Exceptions\MinimumInvestmentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvestmentController
{
    public function __construct(
        private readonly InvestmentService $investments,
    ) {}

    public function listFunds(): JsonResponse
    {
        $funds = $this->investments->listFunds();
        return response()->json(['data' => $funds]);
    }

    public function showFund(string $id): JsonResponse
    {
        try {
            $fund = $this->investments->findFundOrFail($id);
            $latestNav = $fund->latestNav();
            return response()->json([
                'data' => [
                    'fund' => $fund,
                    'latest_nav' => $latestNav,
                ],
            ]);
        } catch (FundNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FUND_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
        }
    }

    public function navHistory(string $id, Request $request): JsonResponse
    {
        try {
            $days = (int) $request->input('days', 30);
            $history = $this->investments->getNavHistory($id, $days);
            return response()->json(['data' => $history]);
        } catch (FundNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FUND_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
        }
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'fund_id' => 'required|string',
            'amount' => 'required|integer|min:1',
        ]);

        try {
            $subscription = $this->investments->subscribe(
                userId: $request->user()->id,
                fundId: $request->input('fund_id'),
                amount: (int) $request->input('amount'),
            );
            return response()->json(['data' => $subscription], 201);
        } catch (FundNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FUND_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
        } catch (MinimumInvestmentException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'MINIMUM_INVESTMENT', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function redeem(Request $request): JsonResponse
    {
        $request->validate([
            'fund_id' => 'required|string',
            'units' => 'required|integer|min:1',
        ]);

        try {
            $subscription = $this->investments->redeem(
                userId: $request->user()->id,
                fundId: $request->input('fund_id'),
                units: (int) $request->input('units'),
            );
            return response()->json(['data' => $subscription], 201);
        } catch (FundNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FUND_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
        }
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $subscriptions = $this->investments->listSubscriptions($request->user()->id);
        return response()->json(['data' => $subscriptions]);
    }

    public function recordNav(Request $request): JsonResponse
    {
        $request->validate([
            'fund_id' => 'required|string',
            'nav' => 'required|integer|min:1',
        ]);

        try {
            $nav = $this->investments->updateNav(
                fundId: $request->input('fund_id'),
                nav: (int) $request->input('nav'),
            );
            return response()->json(['data' => $nav], 201);
        } catch (FundNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FUND_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
        }
    }

    public function calculateZakat(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $amount = (int) $request->input('amount');
        $zakat = $this->investments->calculateZakat($amount);

        return response()->json([
            'data' => [
                'amount' => $amount,
                'zakat_due' => $zakat,
                'rate' => '2.5%',
            ],
        ]);
    }

    public function adminDashboard(): JsonResponse
    {
        $dashboard = $this->investments->adminDashboard();
        return response()->json(['data' => $dashboard]);
    }
}
