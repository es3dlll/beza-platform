<?php

declare(strict_types=1);

namespace Modules\IAM\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAM\Services\AuthorizationService;

final class PermissionMiddleware
{
    public function __construct(private AuthorizationService $auth) {}

    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = $request->user();

        if ($user === null || !$this->auth->userHasPermission($user, $permission)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_FORBIDDEN',
                    'message' => 'Unauthorized action',
                    'message_ar' => 'ليس لديك صلاحية للقيام بهذا الإجراء',
                ],
            ], 403);
        }

        return $next($request);
    }
}
