<?php

declare(strict_types=1);

namespace App\Modules\Identity\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

final class TokenAuth
{
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'الرمز غير موجود',
                'data' => null,
                'errors' => ['auth' => ['الرجاء تسجيل الدخول']],
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-Id'),
            ], 401);
        }

        $hashed = hash('sha256', $token);
        $user = User::where('remember_token', $hashed)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'الرمز غير صالح',
                'data' => null,
                'errors' => ['auth' => ['الرجاء تسجيل الدخول مرة أخرى']],
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-Id'),
            ], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}
