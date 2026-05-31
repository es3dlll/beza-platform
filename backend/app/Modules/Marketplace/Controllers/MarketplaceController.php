<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use App\Modules\Marketplace\Models\Product;
use App\Modules\Marketplace\Models\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class MarketplaceController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $query = Product::with('seller')->active();
        if ($cat = $request->get('category')) $query->byCategory($cat);
        if ($loc = $request->get('location')) $query->where('location', $loc);
        if ($min = $request->get('price_min')) $query->where('price_fils', '>=', (int)$min);
        if ($max = $request->get('price_max')) $query->where('price_fils', '<=', (int)$max);
        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('dir', 'desc');
        if (in_array($sort, ['price_fils', 'rating', 'created_at'])) $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');

        return response()->json(['data' => $query->paginate(20)]);
    }

    public function productShow(string $id): JsonResponse
    {
        $product = Product::with('seller')->find($id);
        if (!$product || $product->status !== 'active') {
            return response()->json(['error' => 'المنتج غير موجود'], 404);
        }
        return response()->json(['data' => $product]);
    }

    public function sellers(Request $request): JsonResponse
    {
        $query = Seller::with('user')->approved();
        if ($cat = $request->get('category')) $query->where('category', $cat);
        return response()->json(['data' => $query->orderBy('rating', 'desc')->paginate(20)]);
    }

    public function sellerShow(string $id): JsonResponse
    {
        $seller = Seller::with(['user', 'products' => fn($q) => $q->active()])->find($id);
        if (!$seller || $seller->status !== 'approved') {
            return response()->json(['error' => 'التاجر غير موجود'], 404);
        }
        return response()->json(['data' => $seller]);
    }

    // Admin endpoints
    public function adminSellers(Request $request): JsonResponse
    {
        $query = Seller::with('user');
        if ($s = $request->get('status')) $query->where('status', $s);
        return response()->json(['data' => $query->orderBy('created_at', 'desc')->paginate(20)]);
    }

    public function approveSeller(string $id): JsonResponse
    {
        $seller = Seller::find($id);
        if (!$seller) return response()->json(['error' => 'التاجر غير موجود'], 404);
        $seller->update(['status' => 'approved']);
        return response()->json(['data' => $seller->fresh()]);
    }

    public function suspendSeller(string $id): JsonResponse
    {
        $seller = Seller::find($id);
        if (!$seller) return response()->json(['error' => 'التاجر غير موجود'], 404);
        $seller->update(['status' => 'suspended']);
        Product::where('seller_id', $id)->update(['status' => 'suspended']);
        return response()->json(['data' => $seller->fresh()]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_products' => Product::active()->count(),
                'total_sellers' => Seller::approved()->count(),
                'pending_sellers' => Seller::pending()->count(),
                'categories' => Product::active()->select('category')->distinct()->pluck('category'),
            ],
        ]);
    }
}
