<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: __DIR__.'/..')
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => \Modules\IAM\Middleware\PermissionMiddleware::class,
            'role' => \Modules\IAM\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $status = 500;
                $code = 'INTERNAL_ERROR';
                $message = __('identity::messages.internal_error');

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                    $code = 'UNAUTHENTICATED';
                    $message = __('identity::messages.unauthenticated');
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $status = 422;
                    $code = 'VALIDATION_ERROR';
                    $message = $e->getMessage();
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $status = 404;
                    $code = 'NOT_FOUND';
                    $message = __('identity::messages.resource_not_found');
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    $status = $e->getStatusCode();
                    $code = 'HTTP_ERROR';
                    $message = $e->getMessage() ?: __('identity::messages.internal_error');
                } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $status = 404;
                    $code = 'MODEL_NOT_FOUND';
                    $message = __('identity::messages.resource_not_found');
                } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    $status = 403;
                    $code = 'FORBIDDEN';
                    $message = __('identity::messages.forbidden');
                } elseif ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                    $status = 429;
                    $code = 'TOO_MANY_REQUESTS';
                    $message = __('identity::messages.too_many_requests');
                }

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => $code,
                        'message' => $message,
                    ],
                ], $status);
            }
        });
    })->create();
