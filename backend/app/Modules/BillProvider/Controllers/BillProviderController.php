<?php

declare(strict_types=1);

namespace App\Modules\BillProvider\Controllers;

use App\Modules\BillProvider\Events\BillProviderDeactivated;
use App\Modules\BillProvider\Events\BillProviderRegistered;
use App\Modules\BillProvider\Models\BillProvider;
use App\Modules\BillProvider\Services\BillProviderCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class BillProviderController extends Controller
{
    public function __construct(
        private readonly BillProviderCatalogService $catalogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $providers = $this->catalogService->getAll($request->get('category'));
        return response()->json(['data' => $providers]);
    }

    public function show(string $id): JsonResponse
    {
        $provider = BillProvider::find($id);
        if (!$provider) {
            return response()->json(['error' => 'مزود الخدمة غير موجود'], 404);
        }
        return response()->json(['data' => $provider]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'external_id' => 'required|string|unique:bill_providers,external_id',
            'is_active' => 'boolean',
            'support_phone' => 'nullable|string|max:50',
            'config' => 'nullable|array',
        ]);

        $provider = $this->catalogService->register($validated);
        event(new BillProviderRegistered($provider));

        return response()->json(['data' => $provider], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'category' => 'string|max:50',
            'external_id' => 'string|unique:bill_providers,external_id,' . $id,
            'is_active' => 'boolean',
            'support_phone' => 'nullable|string|max:50',
            'config' => 'nullable|array',
        ]);

        $provider = $this->catalogService->updateProvider($id, $validated);
        if (!$provider) {
            return response()->json(['error' => 'مزود الخدمة غير موجود'], 404);
        }

        if (isset($validated['is_active']) && !$validated['is_active']) {
            event(new BillProviderDeactivated($provider));
        }

        return response()->json(['data' => $provider]);
    }

    public function toggle(string $id): JsonResponse
    {
        $provider = $this->catalogService->toggleActive($id);
        if (!$provider) {
            return response()->json(['error' => 'مزود الخدمة غير موجود'], 404);
        }

        if (!$provider->is_active) {
            event(new BillProviderDeactivated($provider));
        }

        return response()->json(['data' => $provider]);
    }
}
