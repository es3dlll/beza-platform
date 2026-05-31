# 19 - حالات الحافة (Edge Cases)

## السيناريوهات (Scenarios)
1. **دفع رابط منتهي الصلاحية**: يظهر "منتهي الصلاحية" والمبلغ المجمد يُعاد للتاجر.
2. **دفع مكرر لنفس الرابط**: الرابط يتحول إلى used بعد أول دفعة.
3. **رصيد التاجر يتغير بين إنشاء الرابط والدفع**: الرابط يضمن المبلغ (تم تجميده).
4. **انتهاء صلاحية الرابط قبل الدفع**: Cron job يحرر التجميد ويعيد الرصيد.
5. **Webhook فاشل**: يُعاد المحاولة 3 مرات عبر Queue.
6. **إنشاء رابط برصيد غير كافٍ**: يتم رفض الإنشاء مع رسالة واضحة.
7. **إلغاء رابط مدفوع**: التحقق من الحالة يمنع الإلغاء بعد الدفع.

## جدول حالات الحافة
| # | الحالة | النتيجة |
|---|--------|---------|
| 1 | رابط منتهٍ | رفض الدفع، فك التجميد |
| 2 | دفع مكرر | رفض (status=used) |
| 3 | رصيد غير كافٍ | منع إنشاء الرابط |
| 4 | Webhook فاشل | 3 محاولات + تسجيل |
| 5 | إلغاء رابط نشط | مسموح، فك التجميد |
| 6 | إلغاء رابط منتهٍ | ممنوع (تم فك التجميد تلقائياً) |
| 7 | إنشاء بعملة غير مدعومة | رفض validation |

## كود معالجة حالات الحافة (Edge Case Handling Code)

### 1. منع الدفع على رابط منتهي الصلاحية
```php
public function processPayment(string $token): array
{
    $link = PaymentLink::where('token', $token)->firstOrFail();

    if ($link->isExpired()) {
        throw new PaymentLinkExpiredException();
    }

    if ($link->status !== 'active') {
        throw new PaymentLinkAlreadyUsedException();
    }

    return DB::transaction(function () use ($link) {
        $link->markAsPaid();
        $wallet = MerchantWallet::where('merchant_id', $link->merchant_id)
            ->where('currency', $link->currency)->first();
        $wallet->decrement('frozen_balance', $link->amount);
        return ['redirect_url' => $link->redirect_url];
    }, attempts: 3);
}
```

### 2. منع الدفع المكرر باستخدام Row Lock
```php
DB::transaction(function () use ($token) {
    // قفل الصف لمنع أي عملية متزامنة على نفس الرابط
    $link = PaymentLink::where('token', $token)
        ->lockForUpdate()
        ->firstOrFail();

    if ($link->status !== 'active') {
        throw new PaymentLinkAlreadyUsedException();
    }

    $link->markAsPaid();
}, attempts: 3);
```

### 3. إنشاء رابط برصيد غير كافٍ
```php
public function checkSufficientBalance(Merchant $merchant, float $amount, string $currency): void
{
    $wallet = MerchantWallet::where('merchant_id', $merchant->id)
        ->where('currency', $currency)
        ->first();

    if (!$wallet || $wallet->available_balance < $amount) {
        throw new InsufficientMerchantBalanceException();
    }
}
```

### 4. إلغاء رابط نشط (مع إعادة التجميد)
```php
public function cancel(int $linkId): void
{
    $link = PaymentLink::findOrFail($linkId);

    if ($link->status !== 'active') {
        throw new RuntimeException('لا يمكن إلغاء رابط منتهي أو مستخدم');
    }

    DB::transaction(function () use ($link) {
        $link->update(['status' => 'cancelled']);
        // إعادة المبلغ المجمد إلى رصيد التاجر
        $wallet = MerchantWallet::where('merchant_id', $link->merchant_id)
            ->where('currency', $link->currency)->first();
        $wallet->increment('balance', $link->amount);
        $wallet->decrement('frozen_balance', $link->amount);
    });
}
```

## ملخص
معالجة حالات الحافة في بوابة الدفع أمر بالغ الأهمية لأنها تتعامل مع أموال حقيقية. استخدام الأقفال على مستوى الصف (row-level locks) مع المعاملات (transactions) يضمن عدم حدوث أي تلاعب أو ازدواجية في المدفوعات.
