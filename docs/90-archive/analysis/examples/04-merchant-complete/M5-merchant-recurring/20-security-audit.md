# 20 - أمان العملية

## موافقة العميل
```php
// الاشتراك لا ينشط إلا بموافقة العميل
if ($sub->status !== 'pending') throw new \RuntimeException('الاشتراك ليس في حالة انتظار');
$sub->update(['status' => 'active', 'customer_consented_at' => now()]);
```

## التحقق عند كل شحن
```php
// لا يمكن شحن اشتراك ملغي أو مكتمل
$dueSubs = MerchantSubscription::where('status', 'active')
    ->where('next_charge_at', '<=', now())
    ->whereColumn('current_cycle', '<', 'max_cycles')
    ->get();
```

## قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | موافقة العميل مطلوبة | ✅ |
| 2 | التحقق من max_cycles | ✅ |
| 3 | منع الشحن المكرر | ✅ |
| 4 | Authentication | ✅ |
| 5 | إشعار مسبق (3 أيام) | ✅ |
