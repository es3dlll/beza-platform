<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Listeners;

use App\Modules\Remittance\Enums\ComplianceTier;
use App\Modules\Remittance\Events\InitiateLedgerTransfer;
use App\Modules\Remittance\Models\Remittance;
use Illuminate\Support\Facades\Log;

final class ThresholdReportingListener
{
    private const DAILY_THRESHOLD = 10_000_000_00; // 10M SYP
    private const MONTHLY_THRESHOLD = 50_000_000_00; // 50M SYP

    public function handle(InitiateLedgerTransfer $event): void
    {
        $remittance = Remittance::where('remittance_id', $event->remittanceId)->first();

        if (!$remittance) {
            return;
        }

        $dailyTotal = Remittance::where('sender_id', $event->senderId)
            ->whereDate('created_at', today())
            ->whereIn('status', ['PROCESSING', 'SETTLED'])
            ->sum('source_amount');

        $monthlyTotal = Remittance::where('sender_id', $event->senderId)
            ->whereMonth('created_at', now()->month)
            ->whereIn('status', ['PROCESSING', 'SETTLED'])
            ->sum('source_amount');

        $needsReport = false;
        $tags = [];

        if ($dailyTotal >= self::DAILY_THRESHOLD) {
            $tags[] = 'requires_cbs_report';
            $needsReport = true;
        }

        if ($monthlyTotal >= self::MONTHLY_THRESHOLD) {
            $tags[] = 'requires_cbs_report';
            $needsReport = true;
        }

        if ($needsReport) {
            Log::channel('audit')->info('THRESHOLD_REPORT', [
                'remittance_id' => $event->remittanceId,
                'sender_id' => $event->senderId,
                'daily_total' => $dailyTotal,
                'monthly_total' => $monthlyTotal,
                'tags' => $tags,
            ]);
        }

        $remittance->updateQuietly([
            'compliance_tier' => $dailyTotal >= self::DAILY_THRESHOLD
                ? ComplianceTier::MEDIUM
                : $remittance->compliance_tier,
        ]);
    }
}
