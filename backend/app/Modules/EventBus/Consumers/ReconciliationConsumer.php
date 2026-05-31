<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Consumers;

use App\Modules\EventBus\Contracts\EventConsumer;
use App\Modules\EventBus\Models\EventDeliveryLog;
use App\Modules\Ledger\Events\ReconciliationCompleted;
use App\Modules\Ledger\Services\CBSReportingService;
use App\Modules\Ledger\Jobs\RetryCBSReportSubmission;
use App\Modules\Ledger\Services\NotificationService;
use App\Modules\Ledger\Services\ReconciliationService;
use Illuminate\Support\Facades\Log;

final class ReconciliationConsumer implements EventConsumer
{
    public function __construct(
        private readonly ReconciliationService $reconciliationService,
        private readonly NotificationService $notificationService,
        private readonly CBSReportingService $cbsReportingService,
    ) {}

    public function getName(): string
    {
        return 'reconciliation';
    }

    public function handle(string $eventType, array $payload, EventDeliveryLog $log): void
    {
        Log::info('ReconciliationConsumer: auto-reconciliation triggered', [
            'event_type' => $eventType,
        ]);

        try {
            $report = $this->reconciliationService->reconcile();

            if ($report->total_discrepancies_found > 0) {
                $criticalCount = $report->discrepancies()
                    ->whereIn('severity', ['critical', 'high'])
                    ->count();

                if ($criticalCount > 0) {
                    $this->notificationService->alertFinanceTeam(
                        "Critical reconciliation discrepancies detected",
                        [
                            'report_id' => $report->id,
                            'critical_count' => $criticalCount,
                            'total_discrepancies' => $report->total_discrepancies_found,
                        ]
                    );
                }
            }

            if ($report->cbs_report_code) {
                try {
                    $this->cbsReportingService->submitToCBS($report);
                } catch (\Throwable $e) {
                    Log::error('ReconciliationConsumer: CBS submission failed, scheduling retry', [
                        'report_id' => $report->id,
                        'error' => $e->getMessage(),
                    ]);
                    dispatch(new RetryCBSReportSubmission($report->id))
                        ->delay(now()->addMinutes(5));
                }
            }

        } catch (\Throwable $e) {
            Log::error('ReconciliationConsumer: reconciliation failed', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
            ]);
        }
    }
}
