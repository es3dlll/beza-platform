<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Http\Controllers;

use App\Modules\Ledger\Models\ReconciliationReport;
use App\Modules\Ledger\Services\ReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class ReconciliationController
{
    public function __construct(
        private readonly ReconciliationService $reconciliationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $reports = ReconciliationReport::orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reports);
    }

    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:reconciliation,cbs_trial_balance,cbs_balance_sheet,cbs_income_statement',
            'scope' => 'required|string|in:full,account,date_range',
            'account_id' => 'required_if:scope,account|string|size:26',
            'start_date' => 'required_if:scope,date_range|date_format:Y-m-d',
            'end_date' => 'required_if:scope,date_range|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $accountId = $validated['account_id'] ?? null;
        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null;
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null;

        try {
            $report = $this->reconciliationService->reconcile(
                reportType: $validated['type'],
                scope: $validated['scope'],
                accountId: $accountId,
                startDate: $startDate,
                endDate: $endDate,
                initiatedBy: $request->user()?->getAuthIdentifier(),
            );

            return response()->json([
                'message' => 'Reconciliation accepted',
                'report_id' => $report->id,
                'status' => $report->status,
            ], 202);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Reconciliation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
