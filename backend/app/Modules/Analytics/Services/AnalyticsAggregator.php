<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\AnalyticsSnapshot;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Support\Facades\DB;

final class AnalyticsAggregator
{
    public function aggregateDaily(string $date): AnalyticsSnapshot
    {
        $metrics = [
            'total_transactions' => $this->countTransactions($date),
            'total_volume_fils' => $this->sumVolume($date),
            'active_wallets' => $this->countActiveWallets(),
            'total_balance_fils' => $this->sumBalances(),
            'fraud_alerts' => $this->countFraudAlerts($date),
            'successful_transactions' => $this->countSuccessful($date),
            'failed_transactions' => $this->countFailed($date),
            'escrow_held_fils' => $this->sumEscrowHeld(),
            'notifications_sent' => $this->countNotifications($date),
            'new_users' => $this->countNewUsers($date),
        ];

        $existing = AnalyticsSnapshot::whereDate('snapshot_date', $date)->first();
        if ($existing) {
            $existing->update(['metrics' => $metrics]);
            return $existing->fresh();
        }

        return AnalyticsSnapshot::create([
            'snapshot_date' => $date,
            'metrics' => $metrics,
        ]);
    }

    public function aggregateRange(string $from, string $to): array
    {
        $snapshots = AnalyticsSnapshot::dateRange($from, $to)
            ->orderBy('snapshot_date')
            ->get();

        if ($snapshots->isEmpty()) return [];

        $aggregated = [];
        foreach ($snapshots as $s) {
            $m = $s->metrics;
            foreach ($m as $key => $val) {
                $aggregated[$key] = ($aggregated[$key] ?? 0) + $val;
            }
        }
        $count = $snapshots->count();
        if ($count > 0) {
            foreach ($aggregated as $key => $val) {
                $aggregated[$key . '_avg'] = (int)round($val / $count);
            }
        }
        $aggregated['days'] = $count;
        return $aggregated;
    }

    public function aggregateOnDemand(): array
    {
        $today = now()->toDateString();
        $this->aggregateDaily($today);
        return AnalyticsSnapshot::forDate($today)->first()?->metrics ?? [];
    }

    private function countTransactions(string $date): int
    {
        return AuditLog::whereIn('action', [
            'remittance_completed', 'bill_payment_completed',
            'escrow_funded', 'escrow_released',
        ])->whereDate('created_at', $date)->count();
    }

    private function sumVolume(string $date): int
    {
        $entries = AuditLog::whereIn('action', [
            'remittance_completed', 'bill_payment_completed',
            'escrow_funded', 'escrow_released',
        ])->whereDate('created_at', $date)->get();

        $total = 0;
        foreach ($entries as $e) {
            $total += (int)($e->metadata['amount_fils'] ?? 0);
        }
        return $total;
    }

    private function countActiveWallets(): int
    {
        return Wallet::where('balance_fils', '>', 0)->count();
    }

    private function sumBalances(): int
    {
        return (int)Wallet::sum('balance_fils');
    }

    private function countFraudAlerts(string $date): int
    {
        return AuditLog::where('action', 'fraud_alert_triggered')
            ->whereDate('created_at', $date)->count();
    }

    private function countSuccessful(string $date): int
    {
        return AuditLog::whereIn('action', [
            'remittance_completed', 'bill_payment_completed',
            'escrow_released',
        ])->where('result', 'success')
            ->whereDate('created_at', $date)->count();
    }

    private function countFailed(string $date): int
    {
        return AuditLog::whereIn('action', [
            'bill_payment_failed', 'remittance_failed',
        ])->whereDate('created_at', $date)->count();
    }

    private function sumEscrowHeld(): int
    {
        return (int)DB::table('escrow_transactions')
            ->whereIn('status', ['funded', 'shipped', 'delivered'])
            ->sum(DB::raw('amount_fils + fee_fils'));
    }

    private function countNotifications(string $date): int
    {
        return \App\Modules\Notification\Models\NotificationMessage::whereDate('created_at', $date)->count();
    }

    private function countNewUsers(string $date): int
    {
        return \App\Models\User::whereDate('created_at', $date)->count();
    }
}
