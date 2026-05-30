<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\LoyaltyService;
use App\Support\ApiResponse;

final class LoyaltyController extends Controller
{
    use ApiResponse;
    public function __construct(
        private LoyaltyService $loyalty,
    ) {}

    public function getBalance(Request $request): JsonResponse
    {
        $balance = $this->loyalty->getBalance($request->user()->id);

        return $this->respond(['points' => $balance]);
    }

    public function redeemPoints(Request $request): JsonResponse
    {
        $data = $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $sypValue = $this->loyalty->redeemPoints(
            $request->user()->id,
            (int) $data['points'],
        );

        return $this->respond(['syp_value' => $sypValue, 'points_redeemed' => (int) $data['points']]);
    }

    public function getHistory(Request $request): JsonResponse
    {
        $history = $this->loyalty->getHistory($request->user()->id);

        return $this->respond($history);
    }
}
