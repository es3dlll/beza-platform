# 20 - أمان العملية خطوة بخطوة (Security Audit)

## 1. المصادقة (Authentication)
توكن JWT + التحقق من دور الوكيل. عملية مالية حساسة.

## 2. التفويض (Authorization)
```php
// الوكيل يمكنه فقط تسوية معاملاته الخاصة
$settlement = Settlement::where('id', $id)
    ->where('agent_id', auth()->user()->agent->id)
    ->firstOrFail();
```

## 3. منع الإنفاق المزدوج (Double-Spend Prevention)
```php
// تحديث الرصيد بشكل ذري يمنع التسوية المتزامنة
$affected = Wallet::where('id', $walletId)
    ->where('balance', '>=', $amount)
    ->decrement('balance', $amount);
```

## 4. تحديد المعدل (Rate Limiting)
```php
// عمليات مالية — تحديد معدل أشد
Route::middleware('throttle:10,1')->group(function () { ... });
```

## قائمة التحقق الأمني (Security Checklist)

| # | البند | الحالة |
|---|-------|--------|
| 1 | التحقق من المدخلات | ✅ |
| 2 | استعلامات SQL آمنة | ✅ |
| 3 | تحديد المعدل | ✅ |
| 4 | حماية IDOR | ✅ |
| 5 | منع الإنفاق المزدوج | ✅ |
| 6 | فحص الرصيد بشكل ذري | ✅ |
| 7 | HTTPS (في الإنتاج) | ⏳ |
| 8 | سجل تدقيق التسوية | ✅ |
