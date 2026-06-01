<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class ApiWapAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('wap_token', '');

        if (blank($token)) {
            return $this->unauthorized('UNAUTHENTICATED', 'صلاحية الدخول منتهية. يرجى تسجيل الدخول مجدداً');
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || !$accessToken->can('wap')) {
            return $this->unauthorized('INVALID_TOKEN', 'رمز الدخول غير صالح أو منتهي الصلاحية');
        }

        Auth::loginUsingId($accessToken->tokenable_id);

        AuditLog::create([
            'user_id' => $accessToken->tokenable_id,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'fingerprint' => $request->cookie('wap_fp', ''),
        ]);

        return $next($request);
    }

    private function unauthorized(string $code, string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => compact('code', 'message'),
        ], 401);
    }
}
