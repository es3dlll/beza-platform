# 14 - ACID في النزاعات

## Refund — الأكثر أهمية (تعديل أرصدة)

```php
DB::transaction(function () use ($dispute, $amount) {
    // 1. تحديث حالة النزاع
    $dispute->update([
        'status'     => 'resolved',
        'resolution' => 'refund',
        'resolved_at'=> now(),
    ]);

    // 2. خصم من محفظة التاجر
    Wallet::where('user_id', $dispute->respondent_id)
        ->where('currency', $transaction->fromWallet->currency)
        ->decrement('balance', $amount);

    // 3. إضافة لمحفظة المشتكي
    Wallet::where('user_id', $dispute->complainant_id)
        ->where('currency', $transaction->fromWallet->currency)
        ->increment('balance', $amount);

    // 4. تحديث حالة المعاملة
    Transaction::where('id', $dispute->transaction_id)
        ->update(['status' => 'refunded']);
});
// → كل شيء ينجح أو كل شيء يفشل
```

## لماذا Atomic؟

| السيناريو | بدون Atomic | مع Atomic |
|-----------|------------|-----------|
| خصم من التاجر → فشل الإضافة للمشتكي | التاجر خسر المبلغ والمشتكي ما استلمه | كل شيء يتراجع |
| تم حل النزاع لكن فشل تحديث الرصيد | النزاع مقفول والفلوس ما تحركت | يتراجع |

## FOR UPDATE للنزاعات

عند حل النزاع، نستخدم `lockForUpdate` على محافظ الطرفين:

```php
$respondentWallet = Wallet::where('user_id', $dispute->respondent_id)
    ->lockForUpdate()
    ->first();

$complainantWallet = Wallet::where('user_id', $dispute->complainant_id)
    ->lockForUpdate()
    ->first();
```
