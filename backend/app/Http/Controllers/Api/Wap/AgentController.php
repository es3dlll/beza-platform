<?php

namespace App\Http\Controllers\Api\Wap;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function limits(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'daily_deposit_limit' => 5000000,
                'daily_withdrawal_limit' => 3000000,
                'used_deposit_today' => 0,
                'used_withdrawal_today' => 0,
                'currency' => 'SYP',
            ],
        ]);
    }

    public function commissions(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'today_commission' => 0,
                'week_commission' => 0,
                'month_commission' => 0,
                'pending_commission' => 0,
                'currency' => 'SYP',
            ],
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'pending_transactions' => [],
                'total_count' => 0,
            ],
        ]);
    }
}
