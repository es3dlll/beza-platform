<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Modules\OpenFinance\Models\OpenFinanceApp;
use Modules\OpenFinance\Models\OpenFinanceAccessToken;
use Modules\OpenFinance\Models\OpenFinancePayment;
use Modules\OpenFinance\Models\OpenFinanceWebhookDelivery;
use Modules\OpenFinance\Services\OpenFinanceService;

final class OpenFinanceAdminService
{
    public function __construct(
        private readonly OpenFinanceService $openFinance,
    ) {}

    public function dashboard(): array
    {
        $totalApps = OpenFinanceApp::count();
        $activeKeys = OpenFinanceAccessToken::whereNull('revoked_at')->where('expires_at', '>', now())->count();
        $apiCallsToday = OpenFinancePayment::whereDate('created_at', now()->toDateString())->count();
        $webhookFailures = OpenFinanceWebhookDelivery::where('status', 'failed')
            ->whereDate('created_at', now()->toDateString())->count();

        return [
            'total_developer_apps' => $totalApps,
            'active_api_keys' => $activeKeys,
            'total_api_calls_today' => $apiCallsToday,
            'webhook_failures_today' => $webhookFailures,
        ];
    }

    public function listApps(?string $status): iterable
    {
        $q = OpenFinanceApp::query();
        if ($status === 'active') $q->where('is_active', true);
        if ($status === 'inactive') $q->where('is_active', false);
        return $q->orderByDesc('created_at')->get();
    }

    public function appDetail(string $id): array
    {
        $app = OpenFinanceApp::findOrFail($id);
        $keys = OpenFinanceAccessToken::whereHas('consent', fn($q) => $q->where('app_id', $id))->get();
        $calls = OpenFinancePayment::whereHas('consent', fn($q) => $q->where('app_id', $id))->latest()->limit(20)->get();
        return [
            'app' => $app,
            'api_keys' => $keys,
            'recent_calls' => $calls,
        ];
    }

    public function revokeApp(string $id, string $reason): void
    {
        OpenFinanceApp::where('id', $id)->update(['is_active' => false]);
    }

    public function suspendKey(string $id): void
    {
        OpenFinanceAccessToken::where('id', $id)->update(['revoked_at' => now()]);
    }

    public function usageMetrics(string $appId, ?string $from, ?string $to): array
    {
        $q = OpenFinancePayment::whereHas('consent', fn($query) => $query->where('app_id', $appId));
        if ($from) $q->whereDate('created_at', '>=', $from);
        if ($to) $q->whereDate('created_at', '<=', $to);

        return [
            'total_calls' => $q->count(),
            'total_amount' => (int) $q->sum('amount'),
            'from' => $from,
            'to' => $to,
        ];
    }

    public function webhookLogs(?string $appId, int $limit): iterable
    {
        $q = OpenFinanceWebhookDelivery::with('webhook');
        if ($appId) {
            $q->whereHas('webhook', fn($wh) => $wh->where('app_id', $appId));
        }
        return $q->orderByDesc('created_at')->limit($limit)->get();
    }
}
