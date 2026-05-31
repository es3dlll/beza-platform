# 14 - Middleware للتدقيق (Audit Middleware)

## LogRequests Middleware

```php
<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRequestsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // تسجيل بداية الطلب للـ API
        if ($request->is('api/*')) {
            $startTime = microtime(true);
        }

        $response = $next($request);

        // تسجيل الطلبات المهمة
        if (isset($startTime) && $this->shouldLog($request)) {
            $duration = (microtime(true) - $startTime) * 1000;

            AuditLog::log(
                eventType: 'api_request',
                loggable: null,
                user: $request->user(),
                data: [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                    'duration_ms' => round($duration, 2),
                ],
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        // سجل فقط POST, PUT, DELETE (وليس GET)
        return in_array($request->method(), ['POST', 'PUT', 'DELETE']);
    }
}
```

## التسجيل في Kernel

```php
// app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\LogRequestsMiddleware::class,
];
```
