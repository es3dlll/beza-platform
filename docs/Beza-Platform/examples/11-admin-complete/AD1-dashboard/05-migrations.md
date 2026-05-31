# 05 - كود الميغريشن للوحة التحكم (Migrations)

لوحة التحكم لا تحتاج جداول خاصة — تستخدم الجداول الموجودة (users, wallets, transactions, merchants, agents). لكن يمكن إضافة جدول cache للتسريع:

## جدول dashboard_cache (اختياري — لتقليل ضغط DB)

```php
<?php
// database/migrations/2024_06_01_000001_create_dashboard_cache_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_cache', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('data');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_cache');
    }
};
```

## جدول daily_active_users_log (لتتبع النشاط اليومي)

```php
<?php
// database/migrations/2024_06_01_000002_create_daily_active_users_log_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_active_users_log', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('active_count')->default(0);
            $table->integer('new_registrations')->default(0);
            $table->integer('transaction_count')->default(0);
            $table->decimal('transaction_volume', 15, 2)->default(0);
            $table->json('breakdown')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_active_users_log');
    }
};
```

## Cron Job لتوليد البيانات اليومية

```php
<?php
// app/Console/Commands/GenerateDailyStats.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Admin\DashboardStatsService;

class GenerateDailyStats extends Command
{
    protected $signature = 'dashboard:generate-daily-stats';
    protected $description = 'توليد إحصائيات اليوم للمخططات البيانية';

    public function handle(DashboardStatsService $service): void
    {
        $service->generateDailyLog();
        $this->info('تم توليد إحصائيات اليوم بنجاح');
    }
}
```

```php
// routes/console.php
Artisan::command('dashboard:generate-daily-stats', function () {
    $this->call(GenerateDailyStats::class);
})->dailyAt('23:55');
```
