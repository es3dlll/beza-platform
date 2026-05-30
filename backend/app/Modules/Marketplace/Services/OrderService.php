<?php

declare(strict_types=1);

namespace Modules\Marketplace\Services;

use Illuminate\Support\Str;
use Modules\Marketplace\Enums\OrderStatus;
use Modules\Marketplace\Events\OrderFulfilled;
use Modules\Marketplace\Events\OrderPlaced;
use Modules\Marketplace\Events\OrderRefunded;
use Modules\Marketplace\Exceptions\OrderNotInCartException;
use Modules\Marketplace\Exceptions\OrderNotFoundException;
use Modules\Marketplace\Models\Fulfillment;
use Modules\Marketplace\Models\Order;
use Modules\Marketplace\Models\OrderItem;
use Modules\Marketplace\Models\Product;

final class OrderService
{
    public function createOrder(string $userId, string $vendorId, array $items): Order
    {
        $orderNumber = 'ORD-' . strtoupper(Str::random(10));

        $order = Order::create([
            'user_id' => $userId,
            'vendor_id' => $vendorId,
            'order_number' => $orderNumber,
            'total_amount' => 0,
            'fee_amount' => 0,
            'net_amount' => 0,
            'currency' => 'SYP',
            'status' => OrderStatus::Cart,
        ]);

        $totalAmount = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $quantity = $item['quantity'] ?? 1;
            $unitPrice = $product->price;
            $totalPrice = $unitPrice * $quantity;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);

            $totalAmount += $totalPrice;
        }

        $order->update([
            'total_amount' => $totalAmount,
            'net_amount' => $totalAmount,
        ]);

        return $order->fresh()->load('items');
    }

    public function placeOrder(string $orderId): Order
    {
        $order = Order::findOrFail($orderId);

        if ($order->status !== OrderStatus::Cart) {
            throw new OrderNotInCartException('Order is not in cart status');
        }

        $order->update([
            'status' => OrderStatus::Paid,
            'placed_at' => now(),
        ]);

        OrderPlaced::dispatch($order->id, $order->user_id, $order->total_amount);

        return $order->fresh()->load('items');
    }

    public function fulfillOrder(string $orderId): Order
    {
        $order = Order::findOrFail($orderId);

        $order->update(['status' => OrderStatus::Fulfilling]);

        foreach ($order->items as $item) {
            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'type' => 'digital_delivery',
                'provider' => 'internal',
                'status' => 'completed',
                'fulfilled_at' => now(),
            ]);

            OrderFulfilled::dispatch($order->id, $fulfillment->id);
        }

        $order->update([
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ]);

        return $order->fresh()->load(['items', 'fulfillments']);
    }

    public function refundOrder(string $orderId): Order
    {
        $order = Order::findOrFail($orderId);

        $order->update([
            'status' => OrderStatus::Refunded,
        ]);

        OrderRefunded::dispatch($order->id, 'Requested refund');

        return $order->fresh();
    }

    public function listOrders(string $userId, ?string $status = null): iterable
    {
        $query = Order::where('user_id', $userId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->with('items')->orderBy('created_at', 'desc')->get();
    }

    public function findOrFail(string $id): Order
    {
        return Order::with(['items', 'fulfillments'])->findOrFail($id);
    }
}
