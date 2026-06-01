# 10 - مسجل الأنشطة (Activity Logger)

## Activity Trait

```php
<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Services\AuditService;

trait LogsActivity
{
    /**
     * تسجيل حدث متعلق بهذا الموديل
     */
    public function logEvent(string $eventType, array $data = []): AuditLog
    {
        return app(AuditService::class)->log(
            eventType: $eventType,
            loggable: $this,
            data: $data,
        );
    }

    /**
     * Boot the trait — تسجيل تلقائي عند إنشاء/تحديث/حذف
     */
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            if (property_exists($model, 'auditEvents') && in_array('created', $model->auditEvents)) {
                $model->logEvent(class_basename($model) . '_created');
            }
        });

        static::updated(function ($model) {
            if (property_exists($model, 'auditEvents') && in_array('updated', $model->auditEvents)) {
                $changes = [];
                foreach ($model->getChanges() as $key => $value) {
                    if (!in_array($key, ['updated_at'])) {
                        $changes[$key] = [
                            'old' => $model->getOriginal($key),
                            'new' => $value,
                        ];
                    }
                }
                if (!empty($changes)) {
                    $model->logEvent(class_basename($model) . '_updated', ['changes' => $changes]);
                }
            }
        });
    }
}
```

## الاستخدام في الموديلات

```php
class Wallet extends Model
{
    use LogsActivity;

    // تفعيل التسجيل التلقائي
    protected array $auditEvents = ['created', 'updated'];
}

class Transaction extends Model
{
    use LogsActivity;

    protected array $auditEvents = ['created'];
}
```

## Activity Facade (اختياري)

```php
// App\Facades\Activity.php
class Activity extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuditService::class;
    }
}

// الاستخدام
Activity::log('transfer_created', $transaction, $user, [
    'amount' => 100,
    'currency' => 'USD',
]);

Activity::logAdminAction('block_user', $targetUser, 'نشاط مشبوه');
```
