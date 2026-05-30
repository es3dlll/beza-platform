<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\OpenFinanceAdminService;
use App\Support\ApiResponse;

final class OpenFinanceAdminController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly OpenFinanceAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return $this->respond($this->service->dashboard());
    }

    public function listApps(Request $request): JsonResponse
    {
        return $this->respond($this->service->listApps($request->query('status')));
    }

    public function appDetail(string $id): JsonResponse
    {
        return $this->respond($this->service->appDetail($id));
    }

    public function revokeApp(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->revokeApp($id, $request->input('reason'));
        return $this->respond(['message' => 'App revoked']);
    }

    public function suspendKey(string $id): JsonResponse
    {
        $this->service->suspendKey($id);
        return $this->respond(['message' => 'Key suspended']);
    }

    public function usageMetrics(Request $request, string $appId): JsonResponse
    {
        return $this->respond($this->service->usageMetrics(
            $appId,
            $request->query('from'),
            $request->query('to'),
        ));
    }

    public function webhookLogs(Request $request): JsonResponse
    {
        return $this->respond($this->service->webhookLogs(
            $request->query('app_id'),
            (int) $request->query('limit', 50),
        ));
    }
}
