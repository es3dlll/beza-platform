# 20 - أمان العملية خطوة بخطوة (Security Audit)

## 1. منع الإلغاء غير المصرح به

```php
// ✅ Admin فقط
Route::middleware(['auth:api', 'is_admin'])->post('/admin/deals/{deal}/cancel', ...);

// ✅ التحقق من حالة الصفقة
if (!$deal->canBeCancelled()) {
    throw new DealNotCancellableException($deal->status);
}
```

## 2. Atomicity في الاسترجاع

```php
// ✅ كل الاسترجاعات في معاملة واحدة — يضمن عدم فقدان أي مبلغ
DB::transaction(function () use ($deal) {
    // قفل الصفقة ← منع أي استثمار جديد
    Deal::where('id', $deal->id)->lockForUpdate()->first();
    // استرجاع لكل المستثمرين
}, attempts: 3);
```

## 3. منع الاسترجاع المزدوج

```php
// ✅ التحقق من status = 'active' قبل الاسترجاع
$investments = $deal->investments()->where('status', 'active')->get();
// لا يمكن استرجاع المستثمر مرتين لأن status سيصبح 'refunded'
```

## 4. قائمة التحقق

| # | البند | الحالة |
|---|-------|--------|
| 1 | Admin-only | ✅ |
| 2 | Atomic refund | ✅ |
| 3 | Status check before cancel | ✅ |
| 4 | Double-refund prevention | ✅ |
| 5 | Reason required | ✅ |
| 6 | Concurrent investment lock | ✅ |
| 7 | Audit trail (transactions) | ✅ |
