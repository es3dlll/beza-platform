<?php

declare(strict_types=1);

namespace Modules\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class RateLimitMiddleware
{
    public const int OTP_LIMIT = 10;

    public const int OTP_WINDOW = 1;

    public const int API_LIMIT = 100;

    public const int API_WINDOW = 1;

    public function handle(Request $request, Closure $next, string $type = 'api'): mixed
    {
        $key = $this->resolveKey($request, $type);

        [$limit, $windowMinutes] = $type === 'otp'
            ? [self::OTP_LIMIT, self::OTP_WINDOW]
            : [self::API_LIMIT, self::API_WINDOW];

        $current = (int) Cache::get($key, 0);

        if ($current >= $limit) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => __('identity::messages.otp_rate_limit_exceeded'),
                    'retry_after_seconds' => Cache::ttl($key),
                ],
            ], 429);
        }

        Cache::put($key, $current + 1, now()->addMinutes($windowMinutes));

        $response = $next($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->headers->set('X-RateLimit-Limit', (string) $limit);
            $response->headers->set('X-RateLimit-Remaining', (string) max(0, $limit - $current - 1));
        }

        return $response;
    }

    private function resolveKey(Request $request, string $type): string
    {
        $identifier = $request->input('phone', $request->ip());

        return "rate_limit:{$type}:{$identifier}";
    }
}
