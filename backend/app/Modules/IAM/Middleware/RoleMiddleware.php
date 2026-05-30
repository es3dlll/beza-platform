<?php

declare(strict_types=1);

namespace Modules\IAM\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAM\Services\AuthorizationService;

final class RoleMiddleware
{
    public function __construct(private AuthorizationService $auth) {}

    public function handle(Request $request, Closure $next, string $role): mixed
    {
        $user = $request->user();

        if ($user === null || !$this->auth->userHasRole($user, $role)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_FORBIDDEN',
                    'message' => 'You do not have the required role',
                    'message_ar' => 'ليس لديك الدور المطلوب للقيام بهذا الإجراء',
                ],
            ], 403);
        }

        return $next($request);
    }
}
