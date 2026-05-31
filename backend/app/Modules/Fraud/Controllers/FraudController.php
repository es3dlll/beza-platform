<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Controllers;

use App\Modules\Core\Services\ApiResponse;
use App\Modules\Fraud\Models\ComplianceRule;
use App\Modules\Fraud\Models\RiskScore;
use App\Modules\Fraud\Services\ComplianceRuleManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class FraudController extends Controller
{
    public function __construct(
        private readonly ComplianceRuleManager $ruleManager,
    ) {}

    public function pendingReviews(): JsonResponse
    {
        $scores = RiskScore::where('status', 'suspended')
            ->orderBy('score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return ApiResponse::success($scores);
    }

    public function reviewDecision(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'reason' => 'nullable|string|max:500',
        ]);

        $score = RiskScore::findOrFail($id);

        if ($score->status !== 'suspended') {
            return ApiResponse::error('المعاملة ليست معلقة للمراجعة', null, 400);
        }

        $score->update([
            'status' => $validated['decision'],
            'metadata' => array_merge($score->metadata ?? [], [
                'review_decision' => $validated['decision'],
                'review_reason' => $validated['reason'] ?? null,
                'reviewed_at' => now()->toIso8601String(),
            ]),
        ]);

        return ApiResponse::success(
            $score->fresh(),
            $validated['decision'] === 'approved' ? 'تمت الموافقة على المعاملة' : 'تم رفض المعاملة',
        );
    }

    public function requestDocuments(Request $request, string $id): JsonResponse
    {
        $score = RiskScore::findOrFail($id);

        $validated = $request->validate([
            'documents_needed' => 'required|array',
            'message' => 'nullable|string|max:500',
        ]);

        $score->update([
            'metadata' => array_merge($score->metadata ?? [], [
                'documents_requested' => true,
                'documents_needed' => $validated['documents_needed'],
                'document_request_message' => $validated['message'] ?? null,
                'documents_requested_at' => now()->toIso8601String(),
            ]),
        ]);

        return ApiResponse::success($score->fresh(), 'تم طلب الوثائق الإضافية');
    }

    public function riskDashboard(): JsonResponse
    {
        $total = RiskScore::count();
        $approved = RiskScore::where('status', 'approved')->count();
        $suspended = RiskScore::where('status', 'suspended')->count();
        $rejected = RiskScore::where('status', 'rejected')->count();
        $avgScore = RiskScore::avg('score') ?? 0;

        $regionBreakdown = RiskScore::selectRaw('region, COUNT(*) as count, AVG(score) as avg_score')
            ->groupBy('region')
            ->get();

        $recentPatterns = RiskScore::where('created_at', '>=', now()->subDay())
            ->selectRaw("JSON_EXTRACT(reasons, '$') as reason_group, COUNT(*) as count")
            ->groupBy('reason_group')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();

        return ApiResponse::success([
            'summary' => [
                'total' => $total,
                'approved' => $approved,
                'suspended' => $suspended,
                'rejected' => $rejected,
                'avg_score' => round($avgScore, 1),
            ],
            'region_breakdown' => $regionBreakdown,
            'recent_patterns' => $recentPatterns,
        ]);
    }

    public function listRules(): JsonResponse
    {
        return ApiResponse::success(ComplianceRule::orderBy('priority', 'desc')->get());
    }

    public function createRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'key' => 'required|string|max:100|unique:compliance_rules,key',
            'description' => 'nullable|string|max:500',
            'rule_type' => 'required|in:amount,frequency,region,device,behavioral',
            'parameters' => 'required|array',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'risk_score_impact' => 'integer|min:1|max:100',
            'decision' => 'required|in:approved,suspended,rejected',
        ]);

        $rule = $this->ruleManager->createRule($validated);

        return ApiResponse::success($rule, 'تم إنشاء القاعدة بنجاح', 201);
    }

    public function updateRule(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'description' => 'sometimes|nullable|string|max:500',
            'parameters' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0|max:100',
            'risk_score_impact' => 'sometimes|integer|min:1|max:100',
            'decision' => 'sometimes|in:approved,suspended,rejected',
        ]);

        $rule = $this->ruleManager->updateRule($key, $validated);

        return ApiResponse::success($rule, 'تم تحديث القاعدة بنجاح');
    }

    public function toggleRule(string $key): JsonResponse
    {
        $rule = ComplianceRule::where('key', $key)->firstOrFail();
        $updated = $this->ruleManager->toggleRule($key, !$rule->is_active);

        return ApiResponse::success($updated, $updated->is_active ? 'تم تفعيل القاعدة' : 'تم تعطيل القاعدة');
    }

    public function previewRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'parameters' => 'required|array',
            'risk_score_impact' => 'required|integer|min:1|max:100',
        ]);

        $impact = $this->ruleManager->previewImpact(
            $validated['key'],
            $validated['parameters'],
            $validated['risk_score_impact'],
        );

        return ApiResponse::success($impact, 'تقدير تأثير القاعدة على المعاملات الأخيرة');
    }
}
