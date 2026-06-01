# 14 - ACID + الأقفال + الـ Race Conditions

## تحديات توزيع الأرباح

### مشكلة: الفشل في منتصف التوزيع
إذا فشل التوزيع بعد إضافة الربح لـ 10 مستثمرين من أصل 50:

```
الحل: DB::transaction واحدة لكل شيء
- الربح لا يضاف جزئياً
- كل التوزيع ينجح أو كلّه يفشل
```

### مشكلة: Deadlock عند توزيع 100 مستثمر
```php
// الحل: قفل بترتيب تصاعدي لـ wallet IDs
$walletIds = collect($distributions)->pluck('wallet_id')->sort()->values();
foreach ($walletIds as $id) {
    Wallet::where('id', $id)->lockForUpdate()->first();
}
```

## Atomicity

```php
DB::transaction(function () use ($deal, $investments, $totalProfit) {
    foreach ($investments as $inv) {
        // خصم من محفظة الصفقة (رأس المال)
        // إضافة الربح لمحفظة المستثمر
        // تسجيل معاملة investment_profit
        // تحديث deal_investment.profit_earned
    }
    // تحديث deal.status = completed
}, attempts: 5); // 5 محاولات بسبب كثرة العمليات
```
