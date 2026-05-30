<?php

declare(strict_types=1);

namespace Modules\Payroll\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payroll\Services\PayrollService;
use Modules\Payroll\Services\CsvParserService;
use Modules\Payroll\Services\BatchProcessingService;
use Modules\Payroll\Repositories\EmployerRepository;
use Modules\Payroll\Repositories\EmployeeRecordRepository;
use Modules\Payroll\Repositories\PayrollBatchRepository;
use Modules\Payroll\Repositories\PayrollDisbursementRepository;

class PayrollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmployerRepository::class);
        $this->app->singleton(EmployeeRecordRepository::class);
        $this->app->singleton(PayrollBatchRepository::class);
        $this->app->singleton(PayrollDisbursementRepository::class);

        $this->app->singleton(CsvParserService::class);
        $this->app->singleton(BatchProcessingService::class);
        $this->app->singleton(PayrollService::class);
    }
}
