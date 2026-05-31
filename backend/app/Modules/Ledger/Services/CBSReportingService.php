<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Ledger\Models\ReconciliationReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class CBSReportingService
{
    public function submitToCBS(ReconciliationReport $report): array
    {
        $cbsEndpoint = config('services.cbs.api_endpoint');
        $cbsApiKey = config('services.cbs.api_key');
        $institutionCode = config('services.cbs.institution_code', 'BEZA');

        if (!$cbsEndpoint || !$cbsApiKey) {
            Log::info('CBSReportingService: CBS not configured, simulating submission', [
                'report_id' => $report->id,
                'report_code' => $report->cbs_report_code,
            ]);

            return [
                'status' => 'simulated',
                'reference' => 'SIM-' . strtoupper(substr($report->id, 0, 8)),
                'message' => 'CBS integration not configured — report logged locally',
            ];
        }

        $payload = array_merge($report->toCBSFormat(), [
            'institution_code' => $institutionCode,
            'submitted_at' => now()->toISOString(),
        ]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$cbsApiKey}",
            'Content-Type' => 'application/json',
            'X-Institution-Code' => $institutionCode,
        ])->timeout(30)->post("{$cbsEndpoint}/api/v1/reports/submit", $payload);

        if ($response->failed()) {
            Log::error('CBSReportingService: submission failed', [
                'report_id' => $report->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException("CBS submission failed: HTTP {$response->status()}");
        }

        $result = $response->json();
        Log::info('CBSReportingService: submitted successfully', [
            'report_id' => $report->id,
            'cbs_reference' => $result['reference'] ?? null,
        ]);

        return $result;
    }

    public function checkReportStatus(string $cbsReference): array
    {
        $cbsEndpoint = config('services.cbs.api_endpoint');
        $cbsApiKey = config('services.cbs.api_key');

        if (!$cbsEndpoint || !$cbsApiKey) {
            return ['status' => 'unknown', 'message' => 'CBS not configured'];
        }

        $response = Http::withToken($cbsApiKey)
            ->timeout(15)
            ->get("{$cbsEndpoint}/api/v1/reports/{$cbsReference}/status");

        return $response->successful() ? $response->json() : ['status' => 'error', 'message' => $response->body()];
    }

    public function resubmitFailedReport(ReconciliationReport $report): array
    {
        if (!in_array($report->report_type, CBSReportGenerator::CBS_REPORT_TYPES)) {
            throw new \InvalidArgumentException("Report type {$report->report_type} is not a CBS report type");
        }

        $report->update(['cbs_report_code' => null]);
        $generator = app(CBSReportGenerator::class);
        $generator->generateFromReport($report);

        return $this->submitToCBS($report->fresh());
    }
}
