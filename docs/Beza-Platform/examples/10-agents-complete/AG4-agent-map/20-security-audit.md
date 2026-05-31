# 20 - أمان العملية خطوة بخطوة (Security Audit)

## 1. المصادقة (Authentication)
توكن JWT + التحقق من دور الوكيل.

## 2. خصوصية الموقع (Location Privacy)
```php
// دقة الموقع محدودة — لا يتم كشف العنوان الدقيق
$location->setVisible(['latitude', 'longitude', 'updated_at']);
```

## 3. تحديد المعدل (Rate Limiting)
```php
// منع إساءة استخدام تتبع الموقع
Route::middleware('throttle:60,1')->group(function () { ... });
```

## 4. الاحتفاظ بالبيانات (Data Retention)
```php
// محو سجل المواقع تلقائياً بعد 30 يوماً
AgentLocation::where('created_at', '<', now()->subDays(30))->delete();
```

## قائمة التحقق الأمني (Security Checklist)

| # | البند | الحالة |
|---|-------|--------|
| 1 | التحقق من المدخلات | ✅ |
| 2 | استعلامات SQL آمنة | ✅ |
| 3 | تحديد المعدل | ✅ |
| 4 | التحكم بدقة الموقع | ✅ |
| 5 | سياسة الاحتفاظ بالبيانات | ✅ |
| 6 | تسجيل التدقيق | ✅ |
| 7 | HTTPS (في الإنتاج) | ⏳ |
