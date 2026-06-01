<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckWapRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if ($request->user()->role !== $role) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'ليس لديك صلاحية الوصول إلى هذه الصفحة'],
            ], 403);
        }

        return $next($request);
    }
}
