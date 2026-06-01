# 14 - حظر IPs (IP Blocking)

## نظام حظر IPs

```php
<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;

class BlockIpMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        if (BlockedIp::isBlocked($ip)) {
            return response()->json([
                'success' => false,
                'message' => 'تم حظر عنوان IP الخاص بك',
            ], 403);
        }

        return $next($request);
    }
}
```

## التسجيل في Kernel

```php
// app/Http/Kernel.php
protected $middleware = [
    \App\Http\Middleware\BlockIpMiddleware::class,
    // ...
];
```

## إدارة IPs المحظورة (Admin Controller)

```php
public function listBlockedIps(): JsonResponse
{
    $ips = BlockedIp::with('blocker')->where('is_active', true)->get();

    return response()->json([
        'success' => true,
        'data' => $ips,
    ]);
}

public function unblockIp(int $id): JsonResponse
{
    $blocked = BlockedIp::findOrFail($id);
    $blocked->update(['is_active' => false]);

    return response()->json([
        'success' => true,
        'message' => 'تم إلغاء حظر IP',
    ]);
}

public function blockIp(Request $request): JsonResponse
{
    $request->validate([
        'ip' => 'required|ip',
        'reason' => 'required|string|max:255',
    ]);

    BlockedIp::create([
        'ip' => $request->ip,
        'reason' => $request->reason,
        'is_active' => true,
        'blocked_by' => auth()->id(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'تم حظر IP بنجاح',
    ]);
}
```

## التكامل مع قوائم IPs الموثوقة

```php
// مزامنة أسبوعية مع قواعد بيانات IPs المعروفة بالاحتيال
public function syncBlockedIps(): void
{
    $urls = [
        'https://lists.blocklist.de/lists/all.txt',
        'https://www.spamhaus.org/drop/drop.txt',
    ];

    foreach ($urls as $url) {
        $ips = file($url, FILE_IGNORE_NEW_LINES);
        foreach ($ips as $ip) {
            $ip = trim(explode(';', $ip)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                BlockedIp::firstOrCreate(
                    ['ip' => $ip],
                    ['reason' => 'قائمة حظر تلقائية', 'blocked_by' => 1]
                );
            }
        }
    }
}
```
