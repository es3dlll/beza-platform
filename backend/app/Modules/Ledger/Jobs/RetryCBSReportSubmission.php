<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Jobs;

use App\Modules\Ledger\Models\ReconciliationReport;
use App\Modules\Ledger\Services\CBSReportingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class RetryCBSReportSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 300;

    public function __construct(
        public readonly string $reportId,
    ) {}

    public function handle(CBSReportingService $cbsReportingService): void
    {
        $report = ReconciliationReport::find($this->reportId);

        if (!$report) {
            Log::warning('RetryCBSReportSubmission: report not found', ['report_id' => $this->reportId]);
            return;
        }

        if ($report->status !== ReconciliationReport::STATUS_COMPLETED) {
            Log::warning('RetryCBSReportSubmission: report not completed', [
                'report_id' => $this->reportId,
                'status' => $report->status,
            ]);
            return;
        }

        try {
            $cbsReportingService->submitToCBS($report);
            Log::info('RetryCBSReportSubmission: submitted successfully', [
                'report_id' => $this->reportId,
                'attempt' => $this->attempts(),
            ]);
        } catch (\Throwable $e) {
            Log::error('RetryCBSReportSubmission: failed', [
                'report_id' => $this->reportId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                Log::critical('RetryCBSReportSubmission: max attempts reached, moving to DLQ', [
                    'report_id' => $this->reportId,
                ]);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('RetryCBSReportSubmission: permanently failed', [
            'report_id' => $this->reportId,
            'error' => $e->getMessage(),
        ]);
    }
}
