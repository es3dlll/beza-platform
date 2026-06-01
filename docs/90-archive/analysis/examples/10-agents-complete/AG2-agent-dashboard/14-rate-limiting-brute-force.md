# 14 - منع الهجمات — لوحة الوكيل

## Rate Limiting

```php
<?php
// في Route

Route::get('/agent/dashboard', [AgentDashboardController::class, 'index'])
    ->middleware('throttle:60,1');
    // 60 طلب كحد أقصى في الدقيقة — قراءة فقط
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('agent-dashboard', function (Request $request) {
    return Limit::perMinute(60)
        ->by($request->user()?->id)
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات كثيرة. حاول بعد دقيقة',
        ], 429));
});
```

## Brute Force Protection

```php
<?php
const MAX_DASHBOARD_REFRESH = 120;

function checkDashboardRefresh(string $agentId): void
{
    $key = 'dashboard_refresh_' . $agentId;
    $count = Cache::get($key, 0);
    if ($count >= MAX_DASHBOARD_REFRESH) {
        throw new \App\Exceptions\DashboardRateExceededException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:dashboard:agent:{$agentId}");
Redis::expire("brute:dashboard:agent:{$agentId}", 60);
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 60 req/min |
| Dashboard Refresh Limit | 120 req/min |
