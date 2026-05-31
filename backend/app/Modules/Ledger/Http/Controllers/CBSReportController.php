<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Http\Controllers;

use App\Modules\Ledger\Models\ReconciliationReport;
use Illuminate\Http\JsonResponse;

final class CBSReportController
{
    public function show(string $id): JsonResponse
    {
        $report = ReconciliationReport::with('discrepancies')->find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        $cbsTypes = [
            ReconciliationReport::TYPE_DAILY_TRIAL_BALANCE,
            ReconciliationReport::TYPE_SETTLEMENT_REPORT,
            ReconciliationReport::TYPE_BALANCE_SHEET,
            ReconciliationReport::TYPE_INCOME_STATEMENT,
        ];

        if (!in_array($report->report_type, $cbsTypes, true)) {
            return response()->json(['message' => 'Report is not a CBS report type'], 404);
        }

        return response()->json([
            'id' => $report->id,
            'report_type' => $report->report_type,
            'status' => $report->status,
            'summary' => $report->summary,
            'discrepancies' => $report->discrepancies,
            'reporting_date' => $report->reporting_date?->format('Y-m-d'),
            'completed_at' => $report->completed_at?->toISOString(),
            'cbs_report_code' => $report->cbs_report_code,
        ]);
    }
}
