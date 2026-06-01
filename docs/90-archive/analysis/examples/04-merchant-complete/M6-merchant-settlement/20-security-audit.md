# 20 - أمان العملية

## حساب المبلغ بدقة
```php
// خصم دقيق: المبيعات - رسوم Beza - المرتجعات - رسوم تحويل
$netAmount = $totalSales - $bezaFee - $refunds - $transferFee;
```

## التحقق من عدم وجود تسوية معلقة
```php
$pending = MerchantSettlement::where('merchant_id', $merchant->id)
    ->where('currency', $currency)
    ->whereIn('status', ['pending', 'processing'])
    ->exists();
if ($pending) throw new PendingSettlementExistsException();
```

## قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | الحد الأدنى للتسوية | ✅ 50 USD |
| 2 | منع التسوية المزدوجة | ✅ |
| 3 | حساب دقيق للرسوم | ✅ |
| 4 | Authentication | ✅ |
| 5 | Admin approval | ✅ |
| 6 | HTTPS | ✅ |
