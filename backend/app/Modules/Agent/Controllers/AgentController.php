<?php

declare(strict_types=1);

namespace App\Modules\Agent\Controllers;

use App\Modules\Agent\Events\AgentRegistered;
use App\Modules\Agent\Events\CommissionCalculated;
use App\Modules\Agent\Events\LiquidityRequested;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Services\AgentCommissionCalculator;
use App\Modules\Agent\Services\LiquidityPoolService;
use App\Modules\Core\ValueObjects\Currency;
use App\Modules\Core\ValueObjects\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgentController
{
    public function __construct(
        private readonly LiquidityPoolService $liquidityPool,
        private readonly AgentCommissionCalculator $commissionCalc,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region' => 'required|string|max:100',
        ]);

        if ($request->user()->agent) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم مسجل كعميل بالفعل',
                'data' => null,
                'errors' => ['agent' => ['لديك حساب عميل بالفعل']],
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-Id'),
            ], 422);
        }

        $agent = Agent::create([
            'user_id' => $request->user()->id,
            'status' => 'pending',
            'region' => $validated['region'],
        ]);

        event(new AgentRegistered($agent));

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل طلب انضمام كعميل',
            'data' => [
                'agent_id' => $agent->id,
                'status' => $agent->status,
            ],
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => $request->header('X-Request-Id'),
        ], 201);
    }

    public function requestLiquidity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_fils' => 'required|integer|min:1',
            'currency' => 'required|string|in:SYP,USD,EUR,TRY',
        ]);

        $agent = $request->user()->agent;

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس عميلاً',
                'data' => null,
                'errors' => ['agent' => ['يجب أن تكون عميلاً لطلب السيولة']],
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-Id'),
            ], 403);
        }

        $money = Money::fromFils($validated['amount_fils'], Currency::from($validated['currency']));

        try {
            $result = $this->liquidityPool->requestLiquidity($agent, $money);
            event(new LiquidityRequested($agent, $money, true, $request->header('X-Request-Id')));

            return response()->json([
                'success' => true,
                'message' => 'تم طلب السيولة بنجاح',
                'data' => $result,
                'errors' => null,
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-Id'),
            ]);
        } catch (\RuntimeException $e) {
            event(new LiquidityRequested($agent, $money, false, $request->header('X-Request-Id')));

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'errors' => ['liquidity' => [$e->getMessage()]],
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-Id'),
            ], 422);
        }
    }

    public function calculateCommission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_fils' => 'required|integer|min:1',
            'currency' => 'required|string|in:SYP,USD,EUR,TRY',
            'client_type' => 'required|string|in:retail,business,premium',
        ]);

        $agent = $request->user()->agent;

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس عميلاً',
                'data' => null,
                'errors' => ['agent' => ['يجب أن تكون عميلاً']],
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-Id'),
            ], 403);
        }

        $money = Money::fromFils($validated['amount_fils'], Currency::from($validated['currency']));
        $commission = $this->commissionCalc->calculate($agent, $money, $validated['client_type']);

        event(new CommissionCalculated($agent, $money, $commission, $validated['client_type']));

        return response()->json([
            'success' => true,
            'message' => 'تم حساب العمولة',
            'data' => [
                'commission_fils' => $commission->fils(),
                'currency' => $commission->currency()->value,
                'formatted' => $commission->format(),
            ],
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => $request->header('X-Request-Id'),
        ]);
    }

    public function previewCommission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_fils' => 'required|integer|min:1',
            'currency' => 'required|string|in:SYP,USD,EUR,TRY',
            'client_type' => 'required|string|in:retail,business,premium',
        ]);

        $money = Money::fromFils($validated['amount_fils'], Currency::from($validated['currency']));
        $preview = $this->commissionCalc->previewCommission($money, $validated['client_type']);

        return response()->json([
            'success' => true,
            'message' => 'معاينة العمولة',
            'data' => $preview,
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => $request->header('X-Request-Id'),
        ]);
    }
}
