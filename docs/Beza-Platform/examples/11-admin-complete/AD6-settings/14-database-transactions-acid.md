# 14 - ACID في الإعدادات

الإعدادات عملية كتابة بسيطة (UPSERT). الضمانات المطلوبة:

## كتابة إعداد واحد

```php
// UPSERT — إذا موجود يحدث، إذا لا يوجد ينشئ
Setting::updateOrCreate(
    ['key' => $key],
    ['value' => $value, 'type' => $type, 'updated_by' => $updatedBy]
);
```

## تحديث مجموعة إعدادات

```php
DB::transaction(function () use ($data, $updatedBy) {
    foreach ($data as $key => $value) {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'updated_by' => $updatedBy]
        );
    }
});
```

## التوافقية (Concurrency)

- الإعدادات يقرأها عدد كبير من المستخدمين
- لكن التحديث نادر (مرات قليلة يومياً)
- **FOR UPDATE غير مطلوب** — لا توجد race conditions
- الـ Cache يضمن أن جميع الخوادم ترى القيمة الجديدة

## توقيت التطبيق

```
T1: Admin يحدث fee_transfer = 2.5
T2: Cache::forget('app_settings')
T3: مستخدم يقوم بتحويل → ConfigCacheService يقرأ القيمة الجديدة
T4: رسوم 2.5% تُطبق فوراً ← بدون تأخير
```
