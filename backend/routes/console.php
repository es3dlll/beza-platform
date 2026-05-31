<?php

use App\Models\Feedback;
use App\Modules\Ledger\Console\Commands\GenerateOpenApi;
use App\Modules\Ledger\Console\Commands\LedgerReconcile;
use App\Modules\Ledger\Jobs\RetryCBSReportSubmission;
use App\Modules\Ledger\Services\LedgerHealthCheck;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(LedgerReconcile::class, ['--type=reconciliation', '--scope=full', '--initiated-by=scheduler'])
    ->dailyAt('00:30')
    ->timezone('Asia/Damascus')
    ->onSuccess(function () {
        Log::channel('cfe')->info('Scheduled reconciliation completed successfully');
    })
    ->onFailure(function () {
        Log::channel('cfe')->error('Scheduled reconciliation FAILED');
    })
    ->withoutOverlapping(60)
    ->description('Daily ledger reconciliation');

Schedule::command(LedgerReconcile::class, ['--type=cbs_trial_balance', '--scope=full', '--initiated-by=scheduler'])
    ->dailyAt('01:00')
    ->timezone('Asia/Damascus')
    ->withoutOverlapping(30)
    ->description('Daily CBS trial balance report');

Schedule::command(LedgerReconcile::class, ['--type=cbs_balance_sheet', '--scope=full', '--initiated-by=scheduler'])
    ->dailyAt('01:30')
    ->timezone('Asia/Damascus')
    ->withoutOverlapping(30)
    ->description('Daily CBS balance sheet report');

Schedule::command(LedgerReconcile::class, ['--type=cbs_income_statement', '--scope=full', '--initiated-by=scheduler'])
    ->dailyAt('02:00')
    ->timezone('Asia/Damascus')
    ->withoutOverlapping(30)
    ->description('Daily CBS income statement report');

Schedule::call(function () {
    $healthCheck = app(LedgerHealthCheck::class);
    $result = $healthCheck->check();

    if ($result['status'] !== 'healthy') {
        Log::channel('cfe')->warning('Ledger health check degraded', [
            'status' => $result['status'],
            'summary' => $result['summary'],
        ]);

        $criticalDisc = $result['summary']['critical_discrepancies'] ?? 0;
        if ($criticalDisc > 0) {
            $notificationService = app(\App\Modules\Ledger\Services\NotificationService::class);
            $notificationService->alertOpsTeam('Critical ledger discrepancies detected by health check', [
                'critical_count' => $criticalDisc,
            ]);
        }
    }
})->everySixHours()->description('Ledger health check');

Schedule::call(function () {
    $orchestrator = app(\App\Modules\Core\Services\CacheOrchestrator::class);
    $dashboardData = $orchestrator->cacheAside('dashboard', 'summary', 300, function () {
        return [];
    });
    $alerting = app(\App\Modules\Ledger\Services\AlertingService::class);
})->everySixHours()->description('Beta monitoring check (placeholder)');

Schedule::command(\App\Modules\Core\Console\Commands\GenerateBetaWeeklyReport::class)
    ->weeklyOn(1, '09:00')
    ->timezone('Asia/Damascus')
    ->description('Generate weekly beta feedback report');

Schedule::call(function () {
    $newFeedback = Feedback::query()
        ->where('module', 'ledger')
        ->where('status', 'new')
        ->where('created_at', '>=', now()->subWeek())
        ->get();

    if ($newFeedback->isNotEmpty()) {
        Log::channel('audit')->info('WEEKLY_FEEDBACK_SUMMARY', [
            'count' => $newFeedback->count(),
            'categories' => $newFeedback->groupBy('category')->map->count()->toArray(),
            'high_priority' => $newFeedback->where('severity', 'high')->count(),
        ]);
    }
})->weeklyOn(1, '09:00')->timezone('Asia/Damascus')->description('Review ledger user feedback');
