# 13. نظام المدفوعات والمقاصة (Payments & Settlement)

## 13.1 جدول الرسوم والعمولات (من إعدادات النظام)

```php
// config/beza.php
return [
    'fees' => [
        'transfer' => 0,
        'exchange' => 0.5,
        'card_load' => 1.5,
        'card_payment' => 0,
        'merchant_settlement_percent' => 2.5,
        'merchant_settlement_fixed' => 0.30,
        'agent_cash_out' => 1.0,
        'agent_commission' => 1.0,
        'withdraw_to_bank' => 1.0,
        'deposit_by_card' => 2.5,
    ],
    'limits' => [
        'daily_transfer_usd' => 2000,
        'daily_transfer_syp' => 2000000,
        'min_deposit_usd' => 10,
        'min_deposit_syp' => 10000,
    ],
    'exchange_rate_margin' => 0.5,
];
```

## 13.2 عملية التسوية مع التجار

يومياً (تلقائياً في منتصف الليل):

1. يتم حساب إجمالي المدفوعات التي استلمها التاجر
2. يتم خصم العمولة (2.5% + 0.3 دولار لكل معاملة)
3. يتم إرسال المبلغ الصافي إلى حساب التاجر البنكي (إذا تم ربطه)
4. أو يمكن تركه في محفظة التاجر في Beza لاستخدامه في عمليات أخرى
