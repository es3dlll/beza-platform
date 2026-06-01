<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('admin_token', '');

        if (blank($token)) {
            $token = $request->bearerToken() ?? '';
        }

        if (blank($token)) {
            return $this->unauthorized('UNAUTHENTICATED', 'يرجى تسجيل الدخول');
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken || ! $accessToken->can('admin')) {
            return $this->unauthorized('INVALID_TOKEN', 'رمز الدخول غير صالح');
        }

        Auth::loginUsingId($accessToken->tokenable_id);

        return $next($request);
    }

    private function unauthorized(string $code, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => compact('code', 'message'),
        ], 401);
    }
}
