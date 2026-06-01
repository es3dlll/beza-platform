# 14 - ACID + الأقفال + حالات السباق (ACID + Locks + Race Conditions)

## التجميد والدفع (Freeze & Payment)
```php
DB::transaction(function () {
    $link->markAsPaid();
    $wallet = MerchantWallet::find($link->merchant_id);
    $wallet->increment('balance', $link->amount);
    $wallet->decrement('frozen_balance', $link->amount);
}, attempts: 3);
```

## إنشاء الرابط مع تجميد الرصيد
```php
DB::transaction(function () {
    $this->walletService->freeze($wallet, $amount);  // تجميد يمنع صرف الرصيد
    PaymentLink::create([...]);  // إنشاء الرابط
}, attempts: 3);
```

## Race Conditions
### مشكلة: دفع مكرر لنفس الرابط
الحل: التحقق من الحالة داخل transaction + قفل الصف (row lock).

```php
DB::transaction(function () use ($link) {
    $locked = PaymentLink::where('id', $link->id)->where('status', 'active')->lockForUpdate()->first();
    if (!$locked || $locked->isExpired()) throw new PaymentLinkExpiredException();
    $link->markAsPaid();
});
```

### مشكلة: إنشاء رابطين بنفس المبلغ في نفس الوقت
الحل: UNIQUE(token) + transaction مع isolation level SERIALIZABLE عند الحاجة.

الملخص: استخدام الأقفال على مستوى الصف (row-level locks) يمنع حالات السباق الحرجة.
