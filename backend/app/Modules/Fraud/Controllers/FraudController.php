<?php

declare(strict_types=1);

namespace Modules\Fraud\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Fraud\DTOs\FraudCheckDto;
use Modules\Fraud\DTOs\FraudRuleDto;
use Modules\Fraud\Http\Requests\FraudCheckRequest;
use Modules\Fraud\Http\Requests\CreateFraudRuleRequest;
use Illuminate\Support\Str;
use Modules\Fraud\Http\Requests\ReviewFraudCaseRequest;
use Modules\Fraud\Services\FraudEngine;
use Modules\Fraud\Repositories\FraudRuleRepository;
use Modules\Fraud\Repositories\FraudCaseRepository;
use Modules\Fraud\Repositories\FraudBlacklistRepository;
use Modules\Fraud\Exceptions\FraudTransactionBlockedException;
use Modules\Fraud\Exceptions\FraudReviewRequiredException;

final class FraudController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FraudEngine $fraudEngine,
        private readonly FraudRuleRepository $ruleRepository,
        private readonly FraudCaseRepository $caseRepository,
        private readonly FraudBlacklistRepository $blacklistRepository,
    ) {}

    public function check(FraudCheckRequest $request): JsonResponse
    {
        $dto = new FraudCheckDto(
            eventType: $request->input('event_type'),
            actorId: $request->user()->id,
            actorType: 'user',
            ipAddress: $request->input('ip_address', $request->ip()),
            deviceId: $request->input('device_id'),
            userAgent: $request->input('user_agent', $request->userAgent()),
            latitude: $request->float('latitude'),
            longitude: $request->float('longitude'),
            amount: $request->integer('amount'),
            iban: $request->input('iban'),
            phone: $request->input('phone'),
            email: $request->input('email'),
            fullName: $request->input('full_name'),
        );

        try {
            $event = $this->fraudEngine->evaluate($dto);
        } catch (FraudTransactionBlockedException $e) {
            return $this->respondForbidden($e->getMessage());
        } catch (FraudReviewRequiredException $e) {
            return $this->respondForbidden($e->getMessage());
        }

        return $this->respond($event, null, 200, ['risk_score' => $event->risk_score]);
    }

    public function rules(Request $request): JsonResponse
    {
        $rules = $this->ruleRepository->findAllActive();
        return $this->respond($rules);
    }

    public function createRule(CreateFraudRuleRequest $request): JsonResponse
    {
        $rule = $this->ruleRepository->create([
            'id' => (string) Str::ulid(),
            'name' => $request->input('name'),
            'rule_type' => $request->input('rule_type'),
            'description' => $request->input('description'),
            'parameters' => $request->input('parameters'),
            'risk_score' => (int) $request->input('risk_score'),
            'severity' => $request->input('severity', 'medium'),
        ]);

        return $this->respondCreated($rule);
    }

    public function updateRule(Request $request, string $id): JsonResponse
    {
        $rule = $this->ruleRepository->update($id, $request->only([
            'name', 'rule_type', 'description', 'parameters', 'risk_score', 'is_active', 'severity',
        ]));

        return $this->respond($rule);
    }

    public function cases(Request $request): JsonResponse
    {
        $cases = $this->caseRepository->paginate(
            (int) $request->input('per_page', 15),
            $request->input('status'),
        );

        return $this->respond($cases);
    }

    public function showCase(string $id): JsonResponse
    {
        $case = $this->caseRepository->findById($id);
        if (!$case) {
            return $this->respondNotFound('Fraud case');
        }
        return $this->respond($case);
    }

    public function reviewCase(ReviewFraudCaseRequest $request, string $id): JsonResponse
    {
        $case = $this->caseRepository->update($id, [
            'status' => $request->input('decision'),
            'review_notes' => $request->input('review_notes'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return $this->respond($case);
    }

    public function blacklist(Request $request): JsonResponse
    {
        $entries = $this->blacklistRepository->paginate(
            (int) $request->input('per_page', 15),
            $request->input('type'),
        );

        return $this->respond($entries);
    }

    public function addBlacklist(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:ip,device,phone,email,iban',
            'value' => 'required|string|max:255',
            'reason' => 'sometimes|nullable|string|max:500',
            'expires_at' => 'sometimes|nullable|date',
        ]);

        $entry = $this->blacklistRepository->add([
            'id' => (string) Str::ulid(),
            'type' => $request->input('type'),
            'value' => $request->input('value'),
            'reason' => $request->input('reason'),
            'source' => $request->input('source', 'manual'),
            'added_by' => $request->user()->id,
            'expires_at' => $request->input('expires_at'),
        ]);

        return $this->respondCreated($entry);
    }

    public function removeBlacklist(string $id): JsonResponse
    {
        $this->blacklistRepository->remove($id);
        return $this->respondDeleted();
    }
}
