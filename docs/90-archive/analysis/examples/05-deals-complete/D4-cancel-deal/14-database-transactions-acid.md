# 14 - ACID + الأقفال + الـ Race Conditions

## تحديات إلغاء الصفقة

### مشكلة: الإلغاء أثناء الاستثمار
إذا حاول مستثمر الاستثمار في نفس وقت الإلغاء:

```
T1: إلغاء → يقرأ investments (5 مستثمرين نشطين)
T2: استثمار → يضيف مستثمر سادس
T1: يرجع المبالغ لـ 5 فقط ← المستثمر السادس يخسر ماله!
```

**الحل**: `SELECT ... FOR UPDATE` على deal قبل الإلغاء

```php
DB::transaction(function () use ($deal, $reason) {
    // قفل الصفقة — يمنع أي استثمار جديد
    Deal::where('id', $deal->id)->lockForUpdate()->first();

    // التحقق من أن الصفقة لا تزال قابلة للإلغاء
    $deal->refresh();
    if (!$deal->canBeCancelled()) {
        throw new DealNotCancellableException($deal->status);
    }

    // الآن آمن — لن يتم إضافة مستثمرين جدد
    $investments = $deal->investments()->where('status', 'active')->get();
    foreach ($investments as $investment) {
        // استرجاع...
    }
    $deal->markAsCancelled($reason);
}, attempts: 3);
```

## Atomicity

```php
DB::transaction(function () {
    // كل الاسترجاعات في معاملة واحدة
    // إذا فشل استرجاع واحد → كل شيء يُلغى
}, attempts: 3);
```
