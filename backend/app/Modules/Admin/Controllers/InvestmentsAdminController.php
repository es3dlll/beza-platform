<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\InvestmentsAdminService;

final class InvestmentsAdminController extends Controller
{
    public function __construct(
        private readonly InvestmentsAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->service->dashboard()]);
    }

    public function listFunds(): JsonResponse
    {
        return response()->json(['data' => $this->service->listFunds()]);
    }

    public function fundDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->fundDetail($id)]);
    }

    public function recordNav(Request $request): JsonResponse
    {
        $request->validate(['fund_id' => 'required|string', 'nav' => 'required|integer|min:1']);
        $this->service->recordNav($request->input('fund_id'), (int) $request->input('nav'));
        return response()->json(['data' => ['message' => 'NAV recorded']]);
    }

    public function subscriptionQueue(): JsonResponse
    {
        return response()->json(['data' => $this->service->subscriptionQueue()]);
    }

    public function settleSubscription(string $id): JsonResponse
    {
        $this->service->settleSubscription($id);
        return response()->json(['data' => ['message' => 'Subscription settled']]);
    }

    public function reconcileReport(Request $request, string $fundId): JsonResponse
    {
        return response()->json(['data' => $this->service->reconcileReport(
            $fundId,
            $request->query('from'),
            $request->query('to'),
        )]);
    }
}
