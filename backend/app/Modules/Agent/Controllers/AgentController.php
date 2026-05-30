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
use App\Support\ApiResponse;

final class AgentController
{
    use ApiResponse;
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
        return $this->respondCreated($agent, __('agent::messages.agent_registered'));
    }

    public function approve(string $id): JsonResponse
    {
        $agent = $this->agents->approve($id, request()->user()->id ?? 'system');
        return $this->respond($agent, __('agent::messages.agent_approved'));
    }

    public function show(string $id): JsonResponse
    {
        try {
            $summary = $this->agents->getTodaySummary($id);
            return $this->respond($summary);
        } catch (\Modules\Agent\Exceptions\AgentNotFoundException $e) {
            return $this->respondError('AGENT_NOT_FOUND', $e->getMessage(), null, 404);
        }
    }

    public function nearby(string $governorate): JsonResponse
    {
        $lat = request()->input('lat') ? (float) request()->input('lat') : null;
        $lng = request()->input('lng') ? (float) request()->input('lng') : null;
        $radius = request()->input('radius') ? (int) request()->input('radius') : null;

        $agents = $this->agents->getNearby($governorate, $lat, $lng, $radius);
        return $this->respond($agents);
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
            return $this->respond($result, __('agent::messages.cash_in_success'));
        } catch (\Modules\Agent\Exceptions\AgentNotApprovedException $e) {
            return $this->respondError('AGENT_NOT_APPROVED', $e->getMessage(), null, 403);
        } catch (\Modules\Agent\Exceptions\AgentLimitExceededException $e) {
            return $this->respondError('AGENT_LIMIT_EXCEEDED', $e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return $this->respondError('CASH_IN_FAILED', $e->getMessage(), null, 422);
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
            return $this->respond($result, __('agent::messages.cash_out_success'));
        } catch (\Modules\Agent\Exceptions\AgentNotApprovedException $e) {
            return $this->respondError('AGENT_NOT_APPROVED', $e->getMessage(), null, 403);
        } catch (\Modules\Agent\Exceptions\AgentLimitExceededException $e) {
            return $this->respondError('AGENT_LIMIT_EXCEEDED', $e->getMessage(), null, 422);
        } catch (\Modules\Agent\Exceptions\AgentFloatInsufficientException $e) {
            return $this->respondError('AGENT_FLOAT_INSUFFICIENT', $e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return $this->respondError('CASH_OUT_FAILED', $e->getMessage(), null, 422);
        }
    }

    public function liquidityScore(string $id): JsonResponse
    {
        try {
            $score = $this->agents->getLiquidityScore($id);
            return $this->respond($score);
        } catch (\Modules\Agent\Exceptions\AgentNotFoundException $e) {
            return $this->respondError('AGENT_NOT_FOUND', $e->getMessage(), null, 404);
        }
    }

    public function updateRadius(string $id): JsonResponse
    {
        $meters = (int) request()->input('coverage_radius', 5000);

        try {
            $agent = $this->agents->updateCoverageRadius($id, $meters);
            return $this->respond($agent, 'Coverage radius updated');
        } catch (\Modules\Agent\Exceptions\AgentNotFoundException $e) {
            return $this->respondError('AGENT_NOT_FOUND', $e->getMessage(), null, 404);
        }
    }
}
