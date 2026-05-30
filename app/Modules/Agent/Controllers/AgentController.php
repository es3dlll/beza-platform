<?php

declare(strict_types=1);

namespace Modules\Agent\Controllers;

use Modules\Agent\DTOs\RegisterAgentDto;
use Modules\Agent\DTOs\CashInDto;
use Modules\Agent\DTOs\CashOutDto;
use Modules\Agent\Http\Requests\RegisterAgentRequest;
use Modules\Agent\Http\Requests\CashInRequest;
use Modules\Agent\Http\Requests\CashOutRequest;
use Modules\Agent\Services\AgentService;
use Illuminate\Http\JsonResponse;

final class AgentController
{
    public function __construct(
        private readonly AgentService $agents,
    ) {}

    public function register(RegisterAgentRequest $request): JsonResponse
    {
        $dto = new RegisterAgentDto(
            userId: $request->user()->id,
            businessName: $request->input('business_name'),
            governorate: $request->input('governorate'),
            city: $request->input('city'),
            phone: $request->input('phone'),
            agentType: $request->input('agent_type', 'retail'),
            area: $request->input('area'),
            address: $request->input('address'),
            latitude: $request->input('latitude') ? (float) $request->input('latitude') : null,
            longitude: $request->input('longitude') ? (float) $request->input('longitude') : null,
            coverageRadius: (int) $request->input('coverage_radius', 5000),
            altPhone: $request->input('alt_phone'),
        );

        $agent = $this->agents->register($dto);
        return response()->json(['data' => $agent, 'message' => __('agent::messages.agent_registered')], 201);
    }

    public function approve(string $id): JsonResponse
    {
        $agent = $this->agents->approve($id, request()->user()->id ?? 'system');
        return response()->json(['data' => $agent, 'message' => __('agent::messages.agent_approved')]);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $summary = $this->agents->getTodaySummary($id);
            return response()->json(['data' => $summary]);
        } catch (\Modules\Agent\Exceptions\AgentNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'AGENT_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
        }
    }

    public function nearby(string $governorate): JsonResponse
    {
        $lat = request()->input('lat') ? (float) request()->input('lat') : null;
        $lng = request()->input('lng') ? (float) request()->input('lng') : null;
        $radius = request()->input('radius') ? (int) request()->input('radius') : null;

        $agents = $this->agents->getNearby($governorate, $lat, $lng, $radius);
        return response()->json(['data' => $agents]);
    }

    public function cashIn(CashInRequest $request, string $id): JsonResponse
    {
        $dto = new CashInDto(
            agentId: $id,
            userWalletId: $request->input('user_wallet_id'),
            amount: (int) $request->input('amount'),
            currency: $request->input('currency', 'SYP'),
            referenceId: $request->input('reference_id', uniqid('ci_', true)),
        );

        try {
            $result = $this->agents->cashIn($dto);
            return response()->json(['data' => $result, 'message' => __('agent::messages.cash_in_success')]);
        } catch (\Modules\Agent\Exceptions\AgentNotApprovedException $e) {
            return response()->json([
                'success' => false, 'error' => ['code' => 'AGENT_NOT_APPROVED', 'message' => $e->getMessage()],
            ], 403);
        } catch (\Modules\Agent\Exceptions\AgentLimitExceededException $e) {
            return response()->json([
                'success' => false, 'error' => ['code' => 'AGENT_LIMIT_EXCEEDED', 'message' => $e->getMessage()],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 'error' => ['code' => 'CASH_IN_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function cashOut(CashOutRequest $request, string $id): JsonResponse
    {
        $dto = new CashOutDto(
            agentId: $id,
            userWalletId: $request->input('user_wallet_id'),
            amount: (int) $request->input('amount'),
            currency: $request->input('currency', 'SYP'),
            referenceId: $request->input('reference_id', uniqid('co_', true)),
            channel: 'agent',
            applyFee: (bool) $request->input('apply_fee', true),
        );

        try {
            $result = $this->agents->cashOut($dto);
            return response()->json(['data' => $result, 'message' => __('agent::messages.cash_out_success')]);
        } catch (\Modules\Agent\Exceptions\AgentNotApprovedException $e) {
            return response()->json([
                'success' => false, 'error' => ['code' => 'AGENT_NOT_APPROVED', 'message' => $e->getMessage()],
            ], 403);
        } catch (\Modules\Agent\Exceptions\AgentLimitExceededException $e) {
            return response()->json([
                'success' => false, 'error' => ['code' => 'AGENT_LIMIT_EXCEEDED', 'message' => $e->getMessage()],
            ], 422);
        } catch (\Modules\Agent\Exceptions\AgentFloatInsufficientException $e) {
            return response()->json([
                'success' => false, 'error' => ['code' => 'AGENT_FLOAT_INSUFFICIENT', 'message' => $e->getMessage()],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 'error' => ['code' => 'CASH_OUT_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function liquidityScore(string $id): JsonResponse
    {
        try {
            $score = $this->agents->getLiquidityScore($id);
            return response()->json(['data' => $score]);
        } catch (\Modules\Agent\Exceptions\AgentNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'AGENT_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
        }
    }

    public function updateRadius(string $id): JsonResponse
    {
        $meters = (int) request()->input('coverage_radius', 5000);

        try {
            $agent = $this->agents->updateCoverageRadius($id, $meters);
            return response()->json(['data' => $agent, 'message' => 'Coverage radius updated']);
        } catch (\Modules\Agent\Exceptions\AgentNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'AGENT_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
        }
    }
}
