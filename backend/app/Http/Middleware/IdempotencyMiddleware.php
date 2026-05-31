<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\FinancialCore\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->isMethod('post') && !$request->isMethod('patch') && !$request->isMethod('put')) {
            return $next($request);
        }

        $key = $request->header('X-Idempotency-Key') ?? $request->input('idempotency_key');

        if ($key === null) {
            return $next($request);
        }

        $existing = IdempotencyKey::where('key', $key)->first();

        if ($existing !== null && $existing->response !== null) {
            return new JsonResponse(['data' => $existing->response], 200);
        }

        if ($existing === null) {
            IdempotencyKey::create([
                'key' => $key,
                'expires_at' => now()->addHours(24),
            ]);
        }

        return $next($request);
    }
}
