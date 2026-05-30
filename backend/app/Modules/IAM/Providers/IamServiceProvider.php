<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\IAM\Middleware\PermissionMiddleware;
use Modules\IAM\Middleware\RoleMiddleware;
use Modules\IAM\Repositories\PermissionRepository;
use Modules\IAM\Repositories\RoleRepository;
use Modules\IAM\Services\AuthorizationService;
use Modules\IAM\Services\IamService;

final class IamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RoleRepository::class);
        $this->app->singleton(PermissionRepository::class);

        $this->app->singleton(IamService::class, function ($app) {
            return new IamService(
                $app->make(RoleRepository::class),
                $app->make(PermissionRepository::class),
            );
        });

        $this->app->singleton(AuthorizationService::class, function ($app) {
            return new AuthorizationService(
                $app->make(RoleRepository::class),
                $app->make(PermissionRepository::class),
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role', RoleMiddleware::class);
    }
}
