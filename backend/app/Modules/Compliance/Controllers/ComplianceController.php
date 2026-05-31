<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Controllers;

use App\Modules\Compliance\Models\Alert;
use App\Modules\Compliance\Models\ComplianceCase;
use App\Modules\Compliance\Services\FraudDetectionEngine;
use App\Modules\Compliance\Services\RuleEngine;
use App\Modules\Compliance\Services\SanctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ComplianceController
{
    public function __construct(
        private readonly FraudDetectionEngine $engine,
        private readonly RuleEngine $ruleEngine,
        private readonly SanctionService $sanctionService,
    ) {}

    public function alerts(Request $request): JsonResponse
    {
        $query = Alert::query();

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('rule_id')) {
            $query->where('rule_id', $request->rule_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($alerts);
    }

    public function showCase(string $id): JsonResponse
    {
        $case = $this->engine->getCase($id);

        if (!$case) {
            return response()->json(['error' => 'Case not found'], 404);
        }

        $auditTrails = \App\Modules\Compliance\Models\AuditTrail::where('context->case_id', $id)->get();
        $alerts = Alert::where('case_id', $id)->get();

        return response()->json([
            'case' => $case->toArray(),
            'audit_trails' => $auditTrails->toArray(),
            'alerts' => $alerts->toArray(),
        ]);
    }

    public function reviewCase(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'resolution' => 'required|string|in:RESOLVED_FALSE_POSITIVE,RESOLVED_TRUE_POSITIVE,ESCALATED,CLOSED',
            'reason' => 'required|string|min:10|max:2000',
        ]);

        $result = $this->engine->reviewCase(
            caseId: $id,
            resolution: $validated['resolution'],
            reason: $validated['reason'],
            reviewerId: $request->user()?->id,
        );

        return response()->json(['data' => $result]);
    }

    public function evaluateRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'account_id' => 'required|string',
            'recipient_id' => 'required|string',
            'amount' => 'required|integer',
            'currency' => 'required|string|size:3',
            'device_fingerprint' => 'nullable|string',
            'country' => 'nullable|string|size:2',
            'daily_transaction_count' => 'nullable|integer|min:0',
        ]);

        $context = [
            'transaction_id' => $validated['transaction_id'],
            'account_id' => $validated['account_id'],
            'recipient_id' => $validated['recipient_id'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'device_fingerprint' => $validated['device_fingerprint'] ?? 'unknown',
            'country' => $validated['country'] ?? 'SY',
            'is_new_device' => false,
            'is_untrusted_device' => false,
            'is_new_recipient' => false,
            'recipient_repeat_amount' => 0,
            'daily_transaction_count' => $validated['daily_transaction_count'] ?? 0,
        ];

        $result = $this->ruleEngine->evaluateWithDetails($context);

        return response()->json(['data' => $result]);
    }

    public function checkSanctions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'device_fingerprint' => 'nullable|string|max:100',
        ]);

        $hits = $this->sanctionService->check(
            name: $validated['name'],
            phone: $validated['phone'] ?? null,
            deviceFingerprint: $validated['device_fingerprint'] ?? null,
        );

        return response()->json([
            'data' => [
                'hits' => $hits,
                'blocked' => count($hits) > 0,
            ],
        ]);
    }
}
