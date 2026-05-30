<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Wallet\Contracts\LedgerAclInterface;
use Modules\Wallet\Services\LedgerAclService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $modulePath = str_replace('\\', '/', app_path('Modules'));

        $files = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($modulePath, RecursiveDirectoryIterator::SKIP_DOTS)
            ),
            '/\/Providers\/\w+ServiceProvider\.php$/',
            RegexIterator::GET_MATCH
        );

        $providers = [];
        foreach ($files as $file) {
            $normalizedPath = str_replace('\\', '/', $file[0]);
            $relativePath = str_replace([$modulePath . '/', '.php', '/'], ['', '', '\\'], $normalizedPath);
            $class = 'Modules\\' . $relativePath;

            if (class_exists($class)) {
                $providers[] = $class;
            }
        }

        sort($providers);

        foreach ($providers as $provider) {
            $this->app->register($provider);
        }

        // Ensure critical ACL binding is always available
        $this->app->bind(
            \Modules\Wallet\Contracts\LedgerAclInterface::class,
            \Modules\Wallet\Services\LedgerAclService::class,
        );
    }

    public function boot(): void
    {
        $modulePath = app_path('Modules');
        $modules = array_filter(glob($modulePath . '/*'), 'is_dir');

        foreach ($modules as $moduleDir) {
            $routesFile = $moduleDir . '/Routes/api.php';
            if (file_exists($routesFile)) {
                $moduleName = basename($moduleDir);
                Route::prefix('api')
                    ->middleware('api')
                    ->name("{$moduleName}.")
                    ->group($routesFile);
            }

            $migrationsPath = $moduleDir . '/Database/Migrations';
            if (is_dir($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }

            $langPath = $moduleDir . '/Resources/lang';
            if (is_dir($langPath)) {
                $this->loadTranslationsFrom($langPath, basename($moduleDir));
            }
        }
    }
}
