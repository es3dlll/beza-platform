<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $modulePath = app_path('Modules');
        $pattern = $modulePath . '/*/Providers/*ServiceProvider.php';

        $files = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($modulePath, RecursiveDirectoryIterator::SKIP_DOTS)
            ),
            '/\/Providers\/\w+ServiceProvider\.php$/',
            RegexIterator::GET_MATCH
        );

        $providers = [];
        foreach ($files as $file) {
            $path = $file[0];
            $relativePath = str_replace([$modulePath . '/', '.php', '/'], ['', '', '\\'], $path);
            $class = 'Modules\\' . $relativePath;

            if (class_exists($class)) {
                $providers[] = $class;
            }
        }

        sort($providers);

        foreach ($providers as $provider) {
            $this->app->register($provider);
        }
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
        }
    }
}
