<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\PromoService;

class PromoController extends Controller
{
    public function __construct(
        private PromoService $promos,
    ) {}

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:promo_codes,code',
            'discount_type' => 'required|string|in:percent,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_order_amount' => 'sometimes|integer|min:0',
            'max_uses' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'starts_at' => 'sometimes|date',
            'expires_at' => 'sometimes|date|after:starts_at',
        ]);

        $promo = $this->promos->create($data);

        return response()->json([
            'success' => true,
            'data' => $promo,
        ], 201);
    }

    public function listActive(): JsonResponse
    {
        $promos = $this->promos->listActive();

        return response()->json([
            'success' => true,
            'data' => $promos,
        ]);
    }

    public function validateCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'order_amount' => 'required|integer|min:0',
        ]);

        $promo = $this->promos->validate($data['code'], (int) $data['order_amount']);

        return response()->json([
            'success' => true,
            'data' => $promo,
        ]);
    }

    public function applyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'order_amount' => 'required|integer|min:0',
        ]);

        $discounted = $this->promos->apply($data['code'], (int) $data['order_amount']);

        return response()->json([
            'success' => true,
            'data' => ['discounted_amount' => $discounted],
        ]);
    }
}
