# 14 - منع الهجمات — لوحة المشرف

## Rate Limiting

```php
<?php
// في Route

Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
    ->middleware('throttle:30,1');
    // 30 طلب كحد أقصى في الدقيقة
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('admin-dashboard', function (Request $request) {
    return Limit::perMinute(30)
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
const MAX_ADMIN_DASHBOARD_REFRESH = 60;

function checkAdminDashboardRefresh(string $adminId): void
{
    $key = 'admin_dashboard_refresh_' . $adminId;
    $count = Cache::get($key, 0);
    if ($count >= MAX_ADMIN_DASHBOARD_REFRESH) {
        throw new \App\Exceptions\AdminDashboardRateExceededException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:admin:dashboard:{$adminId}");
Redis::expire("brute:admin:dashboard:{$adminId}", 60);
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 30 req/min |
| Dashboard Refresh Limit | 60 req/min |
