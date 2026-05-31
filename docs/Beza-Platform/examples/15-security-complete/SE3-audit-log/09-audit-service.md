# 09 - خدمة التدقيق (AuditService)

```php
<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * تسجيل حدث
     */
    public function log(
        string $eventType,
        ?Model $loggable = null,
        ?User $user = null,
        array $data = [],
    ): AuditLog {
        return AuditLog::log(
            eventType: $eventType,
            loggable: $loggable,
            user: $user ?? auth()->user(),
            data: $this->sanitizeData($data),
            ip: request()->ip(),
            userAgent: request()->userAgent(),
        );
    }

    /**
     * تسجيل حدث معاملة مالية
     */
    public function logTransaction(string $eventType, Model $transaction, User $user, array $extra = []): AuditLog
    {
        return $this->log(
            eventType: $eventType,
            loggable: $transaction,
            user: $user,
            data: array_merge([
                'amount' => $transaction->amount,
                'currency' => $transaction->fromWallet?->currency,
                'reference_number' => $transaction->reference_number,
                'type' => $transaction->type,
            ], $extra),
        );
    }

    /**
     * تسجيل إجراء مشرف
     */
    public function logAdminAction(string $action, User $targetUser, string $reason, array $extra = []): AuditLog
    {
        return $this->log(
            eventType: 'admin_action',
            loggable: $targetUser,
            user: auth()->user(),
            data: array_merge([
                'action' => $action,
                'target_user_id' => $targetUser->id,
                'target_user_name' => $targetUser->name,
                'reason' => $reason,
            ], $extra),
        );
    }

    /**
     * تنظيف البيانات الحساسة من السجل
     */
    private function sanitizeData(array $data): array
    {
        $sensitiveKeys = ['password', 'pin_code', 'two_factor_secret',
                          'two_factor_recovery_codes', 'cvv', 'card_number'];

        foreach ($sensitiveKeys as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * أرشفة السجلات القديمة
     */
    public function archiveOldLogs(): int
    {
        $cutoff = now()->subYear();
        $count = AuditLog::where('created_at', '<', $cutoff)->count();

        // نسخ لجدول أرشيف أو ملف
        AuditLog::where('created_at', '<', $cutoff)
            ->chunk(100, function ($logs) {
                // ArchiveJob::dispatch($logs->toArray());
            });

        // حذف من الجدول الأساسي
        AuditLog::where('created_at', '<', $cutoff)->delete();

        return $count;
    }
}
```
