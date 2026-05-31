# 12 - سياسة الاحتفاظ (Retention Policy)

## مدة الاحتفاظ

| الفترة | المدة | الإجراء |
|--------|-------|--------|
| نشطة | سنة واحدة | متاحة للبحث الفوري |
| مؤرشفة | 6 سنوات إضافية | مخزنة في جدول أرشيف |
| المجموع | 7 سنوات | متطلب قانوني |

## أمر الأرشفة (Artisan Command)

```php
<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\AuditLogArchive;
use Illuminate\Console\Command;

class ArchiveAuditLogs extends Command
{
    protected $signature = 'audit:archive';
    protected $description = 'أرشفة سجلات التدقيق الأقدم من سنة';

    public function handle(): int
    {
        $cutoff = now()->subYear();
        $count = 0;

        AuditLog::where('created_at', '<', $cutoff)
            ->chunk(100, function ($logs) use (&$count) {
                foreach ($logs as $log) {
                    AuditLogArchive::create($log->toArray());
                    $log->delete();
                    $count++;
                }
            });

        $this->info("تمت أرشفة {$count} سجل تدقيق");
        return Command::SUCCESS;
    }
}
```

## جدول الأرشفة

```php
Schema::create('audit_log_archives', function (Blueprint $table) {
    $table->id();
    $table->string('event_type');
    $string->morphs('loggable');
    $table->foreignId('user_id')->nullable();
    $table->json('data');
    $table->ipAddress('ip')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamp('original_created_at');
    $table->timestamp('archived_at');
    $table->index(['event_type', 'original_created_at']);
});
```

## جدولة الأرشفة

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('audit:archive')->monthly();
    $schedule->command('audit:purge-archives')
        ->yearly()
        ->when(function () {
            return now()->month === 1; // مرة في السنة
        });
}
```

## حذف السجلات بعد 7 سنوات

```php
public function purgeOldArchives(): void
{
    $cutoff = now()->subYears(7);
    AuditLogArchive::where('original_created_at', '<', $cutoff)->delete();
}
```
