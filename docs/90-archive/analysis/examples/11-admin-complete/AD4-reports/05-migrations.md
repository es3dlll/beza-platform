# 05 - كود الميغريشن (Migrations)

## جدول سجل التقارير اليومية (لتسريع العرض)

```php
<?php
// database/migrations/2024_06_01_000030_create_daily_reports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_volume', 15, 2)->default(0);
            $table->decimal('total_fees', 15, 2)->default(0);
            $table->integer('new_users')->default(0);
            $table->integer('active_users')->default(0);
            $table->json('transaction_breakdown')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
```

## جدول التكاليف التشغيلية

```php
<?php
// database/migrations/2024_06_01_000031_create_operational_costs_table.php

Schema::create('operational_costs', function (Blueprint $table) {
    $table->id();
    $table->date('date');
    $table->string('category'); // stripe, twilio, sms, hosting, salaries
    $table->string('description');
    $table->decimal('amount', 15, 2);
    $table->string('currency', 3)->default('USD');
    $table->timestamps();

    $table->index(['date', 'category']);
});
```

## Command cron

```php
<?php
// app/Console/Commands/GenerateDailyReport.php

class GenerateDailyReport extends Command
{
    protected $signature = 'reports:generate-daily {date?}';

    public function handle(DailyReportService $service): void
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : yesterday();
        $service->generateForDate($date);
        $this->info("Daily report generated for {$date->format('Y-m-d')}");
    }
}
```
