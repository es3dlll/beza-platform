<?php

namespace App\Http\Controllers\Api\Wap;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'today_sales' => 0,
                'week_sales' => 0,
                'month_sales' => 0,
                'pending_settlement' => 0,
                'currency' => 'SYP',
            ],
        ]);
    }

    public function qr(Request $request): JsonResponse
    {
        $amount = $request->query('amount', 0);
        $format = $request->query('format', 'json');

        $qrData = json_encode([
            'merchant_id' => $request->user()->id,
            'amount' => (int) $amount,
            'currency' => 'SYP',
            'timestamp' => now()->toIso8601String(),
        ]);

        if ($format === 'svg') {
            return response()->json([
                'success' => true,
                'data' => ['qr_svg' => null, 'data' => $qrData],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => ['qr_data' => $qrData],
        ]);
    }

    public function settlements(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'settlements' => [],
                'total_pending' => 0,
                'last_settlement' => null,
            ],
        ]);
    }
}
