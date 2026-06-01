<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\ApiWapAuth;
use App\Http\Middleware\CheckAdminPermission;
use App\Http\Middleware\CheckWapRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('app/Modules/Auth/routes/api.php'));

            Route::middleware('api')
                ->group(base_path('routes/api-wap.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('app/Modules/Admin/routes/api.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('app/Modules/Wallet/routes/api.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('app/Modules/Team/routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            'wap_token',
            'wap_fp',
            'admin_token',
        ]);

        $middleware->alias([
            'auth.admin' => AdminAuth::class,
            'auth.wap' => ApiWapAuth::class,
            'wap.role' => CheckWapRole::class,
            'admin.permission' => CheckAdminPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
