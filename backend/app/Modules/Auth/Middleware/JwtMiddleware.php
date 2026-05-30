<?php

declare(strict_types=1);

namespace Modules\Auth\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Services\TokenService;

final class JwtMiddleware
{
    public function __construct(
        private TokenService $tokenService,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_TOKEN_MISSING',
                    'message' => __('identity::messages.session_expired'),
                ],
            ], 401);
        }

        $user = $this->tokenService->validateToken($token);

        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_TOKEN_INVALID',
                    'message' => __('identity::messages.session_expired'),
                ],
            ], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if ($header === null || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
