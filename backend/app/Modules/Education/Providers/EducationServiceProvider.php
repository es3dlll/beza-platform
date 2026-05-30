<?php

declare(strict_types=1);

namespace Modules\Education\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Education\Services\EducationService;

class EducationServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->singleton(EducationService::class); }
}
