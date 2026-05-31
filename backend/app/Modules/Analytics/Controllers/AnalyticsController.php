<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Controllers;

use App\Modules\Analytics\Models\AnalyticsSnapshot;
use App\Modules\Analytics\Services\AnalyticsAggregator;
use App\Modules\Analytics\Services\ReportExporter;
use App\Modules\AuditLog\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsAggregator $aggregator,
        private readonly ReportExporter $exporter,
    ) {}

    public function snapshot(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $snapshot = AnalyticsSnapshot::forDate($date)->first();

        if (!$snapshot) {
            $snapshot = $this->aggregator->aggregateDaily($date);
        }

        return response()->json(['data' => $snapshot]);
    }

    public function aggregate(Request $request): JsonResponse
    {
        $metrics = $this->aggregator->aggregateOnDemand();
        return response()->json(['data' => $metrics]);
    }

    public function range(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $data = AnalyticsSnapshot::dateRange($validated['from'], $validated['to'])
            ->orderBy('snapshot_date')
            ->get();

        $totals = $this->aggregator->aggregateRange($validated['from'], $validated['to']);

        return response()->json(['data' => ['snapshots' => $data, 'totals' => $totals]]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $snapshot = $this->aggregator->aggregateDaily($date);
        AuditLog::create([
            'user_id' => $request->get('user_id', 'system'),
            'action' => 'analytics_refreshed',
            'resource_type' => 'analytics',
            'resource_id' => $snapshot->id,
            'result' => 'success',
            'metadata' => ['snapshot_date' => $date],
        ]);
        return response()->json(['data' => $snapshot]);
    }

    public function exportCsv(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'type' => 'nullable|in:financial,operational',
        ]);

        $type = $validated['type'] ?? 'operational';
        $csv = $type === 'financial'
            ? $this->exporter->exportFinancialCsv($validated['from'], $validated['to'])
            : $this->exporter->exportOperationalCsv($validated['from'], $validated['to']);

        return response()->json(['data' => ['csv' => $csv]]);
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $text = $this->exporter->generateSummaryText($validated['from'], $validated['to']);
        return response()->json(['data' => ['summary' => $text]]);
    }

    public function dashboard(): JsonResponse
    {
        $today = now()->toDateString();
        $weekAgo = now()->subDays(7)->toDateString();

        return response()->json([
            'data' => [
                'today' => AnalyticsSnapshot::forDate($today)->first()?->metrics ?? [],
                'weekly' => $this->aggregator->aggregateRange($weekAgo, $today),
                'latest_snapshots' => AnalyticsSnapshot::dateRange($weekAgo, $today)
                    ->orderBy('snapshot_date')->get(),
            ],
        ]);
    }
}
