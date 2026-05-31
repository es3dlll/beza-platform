<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Ledger\Models\ReconciliationReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class CBSAutoSyncService
{
    public function __construct(
        private readonly string $cbsApiBaseUrl,
        private readonly string $cbsApiToken,
        private readonly int $timeoutSeconds = 30,
    ) {}

    public function submitReport(ReconciliationReport $report): array
    {
        if (!in_array($report->report_type, CBSReportGenerator::CBS_REPORT_TYPES, true)) {
            return ['status' => 'skipped', 'reason' => 'Not a CBS report type'];
        }

        if (empty($report->summary) || empty($report->summary['signature'])) {
            return ['status' => 'failed', 'reason' => 'Report not signed'];
        }

        try {
            $response = Http::withToken($this->cbsApiToken)
                ->timeout($this->timeoutSeconds)
                ->withHeaders([
                    'X-Institution-Code' => config('beza.cbs_institution_code'),
                    'X-Report-Type' => $report->report_type,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->cbsApiBaseUrl}/reports/submit", [
                    'report_id' => $report->id,
                    'report_code' => $report->cbs_report_code,
                    'reporting_date' => $report->reporting_date?->format('Y-m-d'),
                    'payload' => $report->summary,
                    'metadata' => [
                        'generated_at' => $report->completed_at?->toISOString(),
                        'total_accounts' => $report->total_accounts_checked,
                        'discrepancy_count' => $report->total_discrepancies_found,
                        'execution_time_ms' => $report->execution_time_ms,
                    ],
                ]);

            if ($response->successful()) {
                $cbsReference = $response->json('reference_id') ?? $response->json('cbs_reference');
                Log::channel('audit')->info('CBS_AUTO_SYNC_SUCCESS', [
                    'report_id' => $report->id,
                    'cbs_reference' => $cbsReference,
                    'response_time_ms' => $response->handlerStats()['total_time'] * 1000 ?? null,
                ]);
                return ['status' => 'success', 'cbs_reference' => $cbsReference];
            }

            Log::warning('CBS_AUTO_SYNC_FAILED', [
                'report_id' => $report->id,
                'status_code' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['status' => 'failed', 'http_code' => $response->status(), 'body' => $response->body()];

        } catch (\Throwable $e) {
            Log::error('CBS_AUTO_SYNC_EXCEPTION', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['status' => 'exception', 'error' => $e->getMessage()];
        }
    }

    public function checkSubmissionStatus(string $cbsReference): array
    {
        try {
            $response = Http::withToken($this->cbsApiToken)
                ->timeout(15)
                ->get("{$this->cbsApiBaseUrl}/reports/status/{$cbsReference}");

            return $response->successful()
                ? ['status' => 'received', 'data' => $response->json()]
                : ['status' => 'failed', 'http_code' => $response->status()];
        } catch (\Throwable $e) {
            return ['status' => 'exception', 'error' => $e->getMessage()];
        }
    }
}
