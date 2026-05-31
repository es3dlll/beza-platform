# M6 - تسوية مدفوعات التاجر

## الوصف
تسوية دورية لمدفوعات التاجر (خصم العمولة وتحويل الصافي).

## سير العمل (يومياً - Cron)
1. حساب إجمالي المدفوعات المستلمة للتاجر (حيث status = completed)
2. حساب إجمالي العمولات (2.5% + 0.30 USD لكل معاملة)
3. المبلغ الصافي = إجمالي المدفوعات - إجمالي العمولات
4. تحويل المبلغ الصافي إلى حساب التاجر البنكي أو تركها في محفظته
5. تحديث settlement_records
6. إشعار التاجر

## الإعدادات
- settlement_period: daily, weekly, monthly (في جدول merchants)
- merchant.settlement_fee_percent: 2.5 (افتراضي)
- merchant.settlement_fee_fixed: 0.30 (افتراضي)

## API Endpoint
`GET /api/v1/merchant/settlements`

## Cron Job
`php artisan settlements:process-daily`

## جداول قاعدة البيانات
- merchants (settlement_fee_percent, settlement_fee_fixed, settlement_period)
- settlement_records
- transactions (metadata يحتوي merchant_payment_id)

## اختبارات
- عرض تاريخ التسوية ← 200
- تشغيل cron يدوياً ← تسوية صحيحة
- حساب العمولة لمبلغ 100 USD: 2.5 + 0.30 = 2.80 USD
