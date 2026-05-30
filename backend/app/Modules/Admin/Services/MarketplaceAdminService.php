<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Models\Order;
use Modules\Marketplace\Models\Product;
use Modules\Marketplace\Models\ProductCategory;
use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Services\CatalogService;
use Modules\Marketplace\Services\OrderService;
use Modules\Marketplace\Services\VendorService;

final class MarketplaceAdminService
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly OrderService $orders,
        private readonly VendorService $vendors,
    ) {}

    public function dashboard(): array
    {
        $totalGmv = (int) Order::sum('total_amount');
        $totalOrders = Order::count();
        $activeVendors = Vendor::where('status', 'approved')->count();
        $topCategories = ProductCategory::leftJoin('products', 'product_categories.id', '=', 'products.category_id')
            ->select('product_categories.id', 'product_categories.name', DB::raw('COUNT(products.id) as products_count'))
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderByDesc('products_count')->limit(5)->get()->map(fn($c) => [
                'name' => $c->name, 'products_count' => $c->products_count,
            ]);
        $fulfilled = Order::whereIn('status', ['completed', 'fulfilling'])->count();
        $fulfillmentRate = $totalOrders > 0 ? round(($fulfilled / $totalOrders) * 100, 2) : 0;

        return [
            'total_gmv' => $totalGmv,
            'total_orders' => $totalOrders,
            'active_vendors' => $activeVendors,
            'top_categories_by_gmv' => $topCategories,
            'fulfillment_rate' => $fulfillmentRate,
        ];
    }

    public function listVendors(?string $status): iterable
    {
        $q = Vendor::query();
        if ($status) $q->where('status', $status);
        return $q->orderByDesc('created_at')->get();
    }

    public function vendorDetail(string $id): array
    {
        $vendor = Vendor::with('products')->findOrFail($id);
        $recentOrders = Order::where('vendor_id', $id)->with('items')->latest()->limit(20)->get();
        return [
            'vendor' => $vendor,
            'recent_orders' => $recentOrders,
        ];
    }

    public function approveVendor(string $id): void
    {
        $this->vendors->approve($id);
    }

    public function suspendVendor(string $id): void
    {
        $this->vendors->suspend($id);
    }

    public function moderateProduct(string $id, string $action, ?string $reason): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => $action === 'approve']);
    }

    public function listOrders(?string $status): iterable
    {
        $q = Order::with('items');
        if ($status) $q->where('status', $status);
        return $q->orderByDesc('created_at')->get();
    }

    public function orderDetail(string $id): array
    {
        return Order::with(['items', 'fulfillments', 'vendor'])->findOrFail($id)->toArray();
    }

    public function commissionReport(?string $vendorId, ?string $from, ?string $to): array
    {
        $q = Order::query();
        if ($vendorId) $q->where('vendor_id', $vendorId);
        if ($from) $q->whereDate('created_at', '>=', $from);
        if ($to) $q->whereDate('created_at', '<=', $to);

        $totalGmv = (int) $q->clone()->sum('total_amount');
        $totalFee = (int) $q->clone()->sum('fee_amount');
        $totalNet = (int) $q->clone()->sum('net_amount');

        return [
            'total_gmv' => $totalGmv,
            'total_commission' => $totalFee,
            'total_net' => $totalNet,
            'vendor_id' => $vendorId,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function settlementReport(?string $vendorId): array
    {
        $q = Order::whereIn('status', ['completed', 'paid']);
        if ($vendorId) $q->where('vendor_id', $vendorId);

        $pending = (int) $q->clone()->where('status', 'paid')->sum('net_amount');
        $due = (int) $q->clone()->where('status', 'completed')->sum('net_amount');

        return [
            'pending_settlement' => $pending,
            'due_settlement' => $due,
            'vendor_id' => $vendorId,
        ];
    }
}
