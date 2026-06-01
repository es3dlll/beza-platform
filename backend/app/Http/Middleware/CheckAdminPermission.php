<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'غير مصرح بالوصول'],
            ], 403);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'MISSING_PERMISSION', 'message' => 'لا تملك الصلاحية المطلوبة'],
            ], 403);
        }

        return $next($request);
    }
}
