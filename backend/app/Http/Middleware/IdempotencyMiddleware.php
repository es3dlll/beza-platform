<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class IdempotencyMiddleware
{
    private const TTL = 86400;

    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->isMethod('post') && !$request->isMethod('patch') && !$request->isMethod('put')) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key') ?? $request->header('idempotency-key');

        if (!$key) {
            return $next($request);
        }

        $cacheKey = "idempotency:{$key}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return response()->json(
                $cached['response'],
                $cached['status']
            );
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'response' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
            ], self::TTL);
        }

        return $response;
    }
}
