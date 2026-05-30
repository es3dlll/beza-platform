<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\InvestmentsAdminService;
use App\Support\ApiResponse;

final class InvestmentsAdminController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly InvestmentsAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return $this->respond($this->service->dashboard());
    }

    public function listFunds(): JsonResponse
    {
        return $this->respond($this->service->listFunds());
    }

    public function fundDetail(string $id): JsonResponse
    {
        return $this->respond($this->service->fundDetail($id));
    }

    public function recordNav(Request $request): JsonResponse
    {
        $request->validate(['fund_id' => 'required|string', 'nav' => 'required|integer|min:1']);
        $this->service->recordNav($request->input('fund_id'), (int) $request->input('nav'));
        return $this->respond(['message' => 'NAV recorded']);
    }

    public function subscriptionQueue(): JsonResponse
    {
        return $this->respond($this->service->subscriptionQueue());
    }

    public function settleSubscription(string $id): JsonResponse
    {
        $this->service->settleSubscription($id);
        return $this->respond(['message' => 'Subscription settled']);
    }

    public function reconcileReport(Request $request, string $fundId): JsonResponse
    {
        return $this->respond($this->service->reconcileReport(
            $fundId,
            $request->query('from'),
            $request->query('to'),
        ));
    }
}
