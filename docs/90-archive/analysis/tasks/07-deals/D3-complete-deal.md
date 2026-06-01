# D3 - إتمام الصفقة (توزيع الأرباح)

## الوصف
عند اكتمال الصفقة (بيع الشحنة)، يتم توزيع الأرباح على المستثمرين.

## سير العمل (Admin/Cron)
1. تحديث deal.status = 'completed'
2. لكل DealInvestment في الصفقة:
   - حساب الربح: `amount * (expected_profit_percent / 100)`
   - total_return = amount + profit
   - إضافة total_return إلى محفظة المستثمر
   - تحديث DealInvestment (profit_earned, total_return, status: completed, completed_at)
3. إشعار جميع المستثمرين

## المثال
- استثمر المستخدم 100 USD في صفقة بـ 8% ربح
- الربح: 8 USD
- الإجمالي المسترد: 108 USD

## API Endpoint
`POST /api/v1/admin/deals/{id}/complete`

## Cron Job
`php artisan deals:check-completed`

## اختبارات
- إتمام صفقة ← توزيع الأرباح بشكل صحيح
- التحقق من رصيد المستثمر بعد الإتمام
- استثمار 500 USD في صفقة 8% ← 540 USD مسترد
- إشعار المستثمرين بعد الإتمام
