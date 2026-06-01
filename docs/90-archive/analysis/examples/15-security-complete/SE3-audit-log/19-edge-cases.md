# 19 - حالات الحافة (Edge Cases)

## 1. سجلات كثيرة (ملايين السجلات)

```php
// المشكلة: استعلامات بطيئة على audit_logs
// الحلول:
// 1. فهارس مركبة (event_type + created_at)
// 2. أرشفة شهرية
// 3. pagination للواجهة
// 4. query limits للمستخدمين العاديين
```

## 2. فشل تسجيل السجل

```php
// لا تمنع فشل audit من إيقاف العملية
try {
    AuditLog::create([...]);
} catch (\Exception $e) {
    Log::channel('audit_failures')->error('فشل تسجيل', [
        'error' => $e->getMessage(),
        'event' => $eventType,
    ]);
}
```

## 3. بيانات حساسة في السجل

```php
// ❌ لا تسجل: كلمات المرور، PIN، CCV، أرقام البطاقات
// ✅ سجل: المعرفات فقط (user_id, transaction_id)
// ✅ استخدم $sanitizeData() لتنظيف البيانات
```

## 4. أحرف Unicode/Arabic في JSON

```php
// عند تخزين JSON مع عربي
AuditLog::create([
    'data' => ['message' => 'تم تحويل 100 دولار'],
]);
// ✅ MySQL utf8mb4 يدعم العربية
// ✅ JSON_UNESCAPED_UNICODE للتصدير
```

## 5. توقيت غير صحيح (Clock Skew)

```php
// استخدم توقيت الخادم (DB) وليس توقيت المستخدم
// created_at: automatically set by MySQL
// تم تسجيل الأحداث بترتيب زمني دقيق
```

## 6. مستخدم محذوف (Soft Delete)

```php
// user_id قد يشير إلى مستخدم محذوف
// الحل: nullOnDelete في المفتاح الخارجي
$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
// عرض اسم المستخدم حتى بعد الحذف → تخزين الاسم في data
```
