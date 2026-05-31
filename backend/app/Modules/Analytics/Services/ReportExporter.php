<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\AnalyticsSnapshot;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Support\Facades\DB;

final class ReportExporter
{
    public function exportFinancialCsv(string $from, string $to): string
    {
        $logs = AuditLog::whereIn('action', [
            'remittance_completed', 'bill_payment_completed',
            'escrow_funded', 'escrow_released', 'escrow_refunded',
        ])->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->orderBy('created_at')
            ->get();

        $csv = "التاريخ,العملية,المعرف,المبلغ (فلس),النتيجة\n";
        foreach ($logs as $log) {
            $amount = $log->metadata['amount_fils'] ?? 0;
            $csv .= "{$log->created_at},{$log->action},{$log->resource_id},{$amount},{$log->result}\n";
        }
        return $csv;
    }

    public function exportOperationalCsv(string $from, string $to): string
    {
        $snapshots = AnalyticsSnapshot::dateRange($from, $to)->orderBy('snapshot_date')->get();

        $csv = "التاريخ,إجمالي المعاملات,حجم التداول (فلس),محافظ نشطة,إجمالي الأرصدة (فلس),تنبيهات الاحتيال,معاملات ناجحة,فاشلة,إشعارات\n";
        foreach ($snapshots as $s) {
            $m = $s->metrics;
            $csv .= implode(',', [
                $s->snapshot_date,
                $m['total_transactions'] ?? 0,
                $m['total_volume_fils'] ?? 0,
                $m['active_wallets'] ?? 0,
                $m['total_balance_fils'] ?? 0,
                $m['fraud_alerts'] ?? 0,
                $m['successful_transactions'] ?? 0,
                $m['failed_transactions'] ?? 0,
                $m['notifications_sent'] ?? 0,
            ]) . "\n";
        }
        return $csv;
    }

    public function generateSummaryText(string $from, string $to): string
    {
        $snapshots = AnalyticsSnapshot::dateRange($from, $to)->orderBy('snapshot_date')->get();
        if ($snapshots->isEmpty()) return 'لا توجد بيانات للفترة المحددة';

        $total = ['transactions' => 0, 'volume' => 0, 'fraud' => 0, 'success' => 0, 'failed' => 0];
        foreach ($snapshots as $s) {
            $m = $s->metrics;
            $total['transactions'] += $m['total_transactions'] ?? 0;
            $total['volume'] += $m['total_volume_fils'] ?? 0;
            $total['fraud'] += $m['fraud_alerts'] ?? 0;
            $total['success'] += $m['successful_transactions'] ?? 0;
            $total['failed'] += $m['failed_transactions'] ?? 0;
        }

        $successRate = $total['transactions'] > 0
            ? round(($total['success'] / $total['transactions']) * 100, 1)
            : 0;

        return "تقرير الأداء من {$from} إلى {$to}\n"
            . "────────────────────────────────────\n"
            . "إجمالي المعاملات: {$total['transactions']}\n"
            . "حجم التداول: {$total['volume']} فلس\n"
            . "المعاملات الناجحة: {$total['success']}\n"
            . "المعاملات الفاشلة: {$total['failed']}\n"
            . "نسبة النجاح: {$successRate}%\n"
            . "تنبيهات الاحتيال: {$total['fraud']}\n"
            . "الفترة: {$from} → {$to}\n";
    }
}
