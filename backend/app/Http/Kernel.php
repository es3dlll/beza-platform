<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

final class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\IdempotencyMiddleware::class,
    ];

    protected $middlewareGroups = [
        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $routeMiddleware = [
        'idempotent' => \App\Http\Middleware\IdempotencyMiddleware::class,
        'permission' => \Modules\IAM\Middleware\PermissionMiddleware::class,
        'role' => \Modules\IAM\Middleware\RoleMiddleware::class,
    ];
}
