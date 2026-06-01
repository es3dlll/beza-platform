# AD4 - التقارير (Admin)

## الوصف
تقارير يومية/شهرية/مالية للمنصة.

## تقرير يومي
`GET /api/v1/admin/reports/daily?date=YYYY-MM-DD`

### المخرجات
- إجمالي المعاملات
- إجمالي الإيرادات (الرسوم)
- المستخدمون الجدد
- نسبة الإيداع الأول بعد التسجيل
- حجم معاملات كل نوع (تحويل، إيداع، سحب، ...)

## تقرير شهري
`GET /api/v1/admin/reports/monthly?year=2024&month=1`

### المخرجات
- نفس اليومي + مقارنة بالشهر السابق
- MAU (المستخدمون النشطون)
- Cohort analysis للاحتفاظ
- GMV (إجمالي قيمة المعاملات)

## تقرير مالي
`GET /api/v1/admin/reports/financial?from=...&to=...`

### المخرجات
- P&L (أرباح وخسائر)
- هامش الربح
- تحليل الرسوم
- تحليل التكاليف (Stripe fees, Twilio, إلخ)

## Cron Job
`php artisan reports:generate-daily`

## واجهات المستخدم
- React Admin: DailyReport, MonthlyReport, FinancialReport

## اختبارات
- عرض التقرير اليومي ← 200
- عرض التقرير الشهري ← 200
- عرض التقرير المالي ← 200
- تاريخ بدون معاملات ← 200 (أصفار)
