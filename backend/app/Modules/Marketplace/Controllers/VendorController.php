<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\VendorService;

class VendorController extends Controller
{
    public function __construct(
        private VendorService $vendors,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|string',
            'shop_name' => 'required|string|max:100',
            'shop_name_ar' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'governorate' => 'required|string|max:50',
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
        ]);

        $vendor = $this->vendors->register($data);

        return response()->json([
            'success' => true,
            'data' => $vendor,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $vendors = $this->vendors->listByStatus(
            $request->query('status', 'approved'),
            (int) $request->query('per_page', 15),
        );

        return response()->json([
            'success' => true,
            'data' => $vendors,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $vendor = $this->vendors->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $vendor,
        ]);
    }

    public function approve(string $id): JsonResponse
    {
        $vendor = $this->vendors->approve($id);

        return response()->json([
            'success' => true,
            'data' => $vendor,
        ]);
    }

    public function suspend(string $id): JsonResponse
    {
        $vendor = $this->vendors->suspend($id);

        return response()->json([
            'success' => true,
            'data' => $vendor,
        ]);
    }
}
