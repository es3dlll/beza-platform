<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\MarketplaceAdminService;
use App\Support\ApiResponse;

final class MarketplaceAdminController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly MarketplaceAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return $this->respond($this->service->dashboard());
    }

    public function listVendors(Request $request): JsonResponse
    {
        return $this->respond($this->service->listVendors($request->query('status')));
    }

    public function vendorDetail(string $id): JsonResponse
    {
        return $this->respond($this->service->vendorDetail($id));
    }

    public function approveVendor(string $id): JsonResponse
    {
        $this->service->approveVendor($id);
        return $this->respond(['message' => 'Vendor approved']);
    }

    public function suspendVendor(string $id): JsonResponse
    {
        $this->service->suspendVendor($id);
        return $this->respond(['message' => 'Vendor suspended']);
    }

    public function moderateProduct(Request $request, string $id): JsonResponse
    {
        $request->validate(['action' => 'required|in:approve,reject']);
        $this->service->moderateProduct($id, $request->input('action'), $request->input('reason'));
        return $this->respond(['message' => 'Product moderated']);
    }

    public function listOrders(Request $request): JsonResponse
    {
        return $this->respond($this->service->listOrders($request->query('status')));
    }

    public function orderDetail(string $id): JsonResponse
    {
        return $this->respond($this->service->orderDetail($id));
    }

    public function commissionReport(Request $request): JsonResponse
    {
        return $this->respond($this->service->commissionReport(
            $request->query('vendor_id'),
            $request->query('from'),
            $request->query('to'),
        ));
    }

    public function settlementReport(Request $request): JsonResponse
    {
        return $this->respond($this->service->settlementReport($request->query('vendor_id')));
    }
}
