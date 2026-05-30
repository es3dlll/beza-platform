<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\OrderService;
use App\Support\ApiResponse;

final class OrderController extends Controller
{
    use ApiResponse;
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

        return $this->respondCreated($order);
    }

    public function place(string $id): JsonResponse
    {
        $order = $this->orders->placeOrder($id);

        return $this->respond($order);
    }

    public function fulfill(string $id): JsonResponse
    {
        $order = $this->orders->fulfillOrder($id);

        return $this->respond($order);
    }

    public function refund(string $id): JsonResponse
    {
        $order = $this->orders->refundOrder($id);

        return $this->respond($order);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orders->listOrders(
            $request->query('user_id', ''),
            $request->query('status'),
        );

        return $this->respond($orders);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->orders->findOrFail($id);

        return $this->respond($order);
    }
}
