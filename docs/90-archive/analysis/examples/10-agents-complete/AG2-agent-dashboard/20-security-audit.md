# 20 - أمان العملية خطوة بخطوة (Security Audit)

## 1. المصادقة (Authentication)
مطلوب توكن JWT. دور الوكيل يُتحقق منه عبر middleware.

## 2. عزل البيانات (Data Isolation)
```php
// لوحة التحكم تعرض فقط بيانات الوكيل المصادق
$stats = AgentStat::where('agent_id', auth()->user()->agent->id)->first();
```

## 3. تحديد المعدل (Rate Limiting)
```php
// منع الاستعلام المفرط
Route::middleware('throttle:30,1')->group(function () { ... });
```

## 4. أمان الكاش (Cache Security)
```php
// مفاتيح الكاش خاصة بكل وكيل — لا تسريب بيانات بين الوكلاء
Cache::remember("agent_dashboard_{$agentId}", 60, fn() => ...);
```

## قائمة التحقق الأمني (Security Checklist)

| # | البند | الحالة |
|---|-------|--------|
| 1 | التحقق من المدخلات | ✅ |
| 2 | استعلامات SQL آمنة | ✅ |
| 3 | تحديد المعدل | ✅ |
| 4 | عزل البيانات حسب الوكيل | ✅ |
| 5 | عزل الكاش | ✅ |
| 6 | تسجيل التدقيق (Audit log) | ✅ |
| 7 | HTTPS (في الإنتاج) | ⏳ |
