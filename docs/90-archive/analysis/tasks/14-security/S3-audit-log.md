# SE3 - سجل التدقيق (Audit Log)

## الوصف
تسجيل جميع العمليات الحساسة للامتثال والمراجعة.

## الأحداث المسجلة
| الحدث | البيانات المسجلة |
|-------|-----------------|
| تسجيل دخول | user_id, IP, device_id, timestamp |
| تغيير PIN | user_id, timestamp |
| تغيير كلمة السر | user_id, timestamp |
| تفعيل 2FA | user_id, timestamp |
| معاملة > 1000 USD | transaction_id, user_id, amount, IP |
| تغيير الحالة (حظر/تعليق) | admin_id, user_id, action, reason |
| تغيير الإعدادات | admin_id, setting_key, old_value, new_value |
| الموافقة/الرفض (KYC) | admin_id, user_id, action |
| محاولات PIN خاطئة | user_id, count, IP |
| استرجاع (Refund) | admin_id, transaction_id, amount |

## جدول audit_logs
```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('event_type'); // login, pin_change, etc.
    $table->morphs('loggable'); // polymorphic relation
    $table->foreignId('user_id')->nullable()->constrained();
    $table->json('data'); // تفاصيل الحدث
    $table->ipAddress('ip')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();
    $table->index(['event_type', 'created_at']);
});
```

## الاحتفاظ
- 7 سنوات للامتثال القانوني
- أرشفة تلقائية بعد سنة

## API Endpoint
`GET /api/v1/admin/audit-logs?event_type=...&from=...&to=...`

## اختبارات
- تغيير PIN ← تسجيل في audit_logs
- محاولة PIN خاطئة ← تسجيل
- عرض سجل التدقيق (Admin) ← 200
