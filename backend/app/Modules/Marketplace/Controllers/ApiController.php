<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Models\Order;
use Modules\Marketplace\Models\Product;
use Modules\Marketplace\Models\ProductCategory;
use Modules\Marketplace\Services\OrderService;

class ApiController extends Controller
{
    public function __construct(
        private OrderService $orders,
    ) {}

    public function products(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        $products = Product::where('is_active', true)
            ->with(['vendor', 'category'])
            ->paginate(min($perPage, 100));

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function productDetail(string $id): JsonResponse
    {
        $product = Product::with(['vendor', 'category'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = $this->orders->createOrder(
            $request->user()->id,
            $data['vendor_id'],
            $data['items'],
        );

        return response()->json([
            'success' => true,
            'data' => $order,
        ], 201);
    }

    public function orderStatus(string $id): JsonResponse
    {
        $order = $this->orders->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'placed_at' => $order->placed_at,
                'completed_at' => $order->completed_at,
            ],
        ]);
    }

    public function fulfillmentWebhook(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|string',
            'status' => 'required|string|max:30',
            'provider' => 'sometimes|string|max:50',
            'provider_reference' => 'sometimes|string|max:100',
            'tracking_number' => 'sometimes|string|max:100',
        ]);

        $order = Order::findOrFail($data['order_id']);

        $order->fulfillments()->create([
            'type' => 'physical_delivery',
            'provider' => $data['provider'] ?? '3pl',
            'provider_reference' => $data['provider_reference'] ?? null,
            'status' => $data['status'],
            'metadata' => ['tracking_number' => $data['tracking_number'] ?? null],
            'fulfilled_at' => $data['status'] === 'completed' ? now() : null,
        ]);

        if ($data['status'] === 'completed') {
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed',
        ]);
    }
}
