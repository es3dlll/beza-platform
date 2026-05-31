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
        /** @var ReconciliationReport|null $report */
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

        if ($report->cbs_report_code !== null) {
            Log::info('RetryCBSReportSubmission: already submitted, skipping', [
                'report_id' => $this->reportId,
                'cbs_report_code' => $report->cbs_report_code,
            ]);
            return;
        }

        try {
            $cbsReportingService->submitToCBS($report);
            Log::channel('audit')->info('CBS report submitted successfully', [
                'report_id' => $this->reportId,
                'user' => 'system',
                'context' => 'RetryCBSReportSubmission',
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('CBS report submission failed', [
                'report_id' => $this->reportId,
                'user' => 'system',
                'context' => 'RetryCBSReportSubmission',
                'error' => $e->getMessage(),
                'attempts_remaining' => $this->tries - $this->attempts(),
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
        Log::channel('audit')->critical('RetryCBSReportSubmission: permanently failed', [
            'report_id' => $this->reportId,
            'error' => $e->getMessage(),
        ]);
    }

    public function retryUntil(): \DateTimeImmutable
    {
        return now()->addHours(4)->toDateTimeImmutable();
    }

    public function maxExceptions(): int
    {
        return 2;
    }
}
