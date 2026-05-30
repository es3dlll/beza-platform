<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orders,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|string',
            'vendor_id' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = $this->orders->createOrder(
            $data['user_id'],
            $data['vendor_id'],
            $data['items'],
        );

        return response()->json([
            'success' => true,
            'data' => $order,
        ], 201);
    }

    public function place(string $id): JsonResponse
    {
        $order = $this->orders->placeOrder($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function fulfill(string $id): JsonResponse
    {
        $order = $this->orders->fulfillOrder($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function refund(string $id): JsonResponse
    {
        $order = $this->orders->refundOrder($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orders->listOrders(
            $request->query('user_id', ''),
            $request->query('status'),
        );

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->orders->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }
}
