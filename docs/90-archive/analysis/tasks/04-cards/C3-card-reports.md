# C3 - تقارير الإنفاق للبطاقة

## الوصف
عرض تحليل الإنفاق على البطاقة مصنفاً حسب الفئة.

## المدخلات
- card_id (optional — all cards if omitted)
- from_date, to_date

## المخرجات
- total_spent
- transactions_count
- spending_by_category (food, travel, shopping, etc.)
- monthly_chart
- top_merchants

## API Endpoint
`GET /api/v1/cards/reports?from=...&to=...`

## أولوية التنفيذ
P2

## اختبارات
- عرض التقرير ← 200 مع بيانات
- تقرير لبطاقة غير موجودة ← 404
- تقرير بدون تاريخ ← 200 (آخر 30 يوماً)
