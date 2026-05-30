<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\MarketplaceAdminService;

final class MarketplaceAdminController extends Controller
{
    public function __construct(
        private readonly MarketplaceAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->service->dashboard()]);
    }

    public function listVendors(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listVendors($request->query('status'))]);
    }

    public function vendorDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->vendorDetail($id)]);
    }

    public function approveVendor(string $id): JsonResponse
    {
        $this->service->approveVendor($id);
        return response()->json(['data' => ['message' => 'Vendor approved']]);
    }

    public function suspendVendor(string $id): JsonResponse
    {
        $this->service->suspendVendor($id);
        return response()->json(['data' => ['message' => 'Vendor suspended']]);
    }

    public function moderateProduct(Request $request, string $id): JsonResponse
    {
        $request->validate(['action' => 'required|in:approve,reject']);
        $this->service->moderateProduct($id, $request->input('action'), $request->input('reason'));
        return response()->json(['data' => ['message' => 'Product moderated']]);
    }

    public function listOrders(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listOrders($request->query('status'))]);
    }

    public function orderDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->orderDetail($id)]);
    }

    public function commissionReport(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->commissionReport(
            $request->query('vendor_id'),
            $request->query('from'),
            $request->query('to'),
        )]);
    }

    public function settlementReport(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->settlementReport($request->query('vendor_id'))]);
    }
}
