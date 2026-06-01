# D2 - المشاركة في صفقة

## الوصف
استثمار مبلغ في صفقة تمويل تجاري.

## المدخلات
| الحقل | النوع |
|-------|-------|
| deal_id | id |
| amount | decimal, min: حسب deal |
| pin | string, size:4 |

## سير العمل
1. التحقق من أن deal.status = 'open'
2. التحقق من أن deal.invested_amount + amount <= deal.target_amount
3. التحقق من PIN
4. التحقق من رصيد كافٍ
5. DB::beginTransaction()
6. خصم من محفظة المستخدم
7. زيادة deal.invested_amount
8. إنشاء DealInvestment (status: invested)
9. إذا اكتمل target_amount: deal.status = 'funded'
10. DB::commit()
11. إشعار المستخدم

## قواعد العمل
- الحد الأدنى للمشاركة يختلف حسب deal (عادة 50-500 USD)
- المبلغ مجمد حتى اكتمال الصفقة
- لا يمكن سحب المبلغ قبل إتمام الصفقة

## API Endpoint
`POST /api/v1/deals/{id}/invest`
`GET /api/v1/deals` (عرض الصفقات المتاحة)

## واجهات المستخدم
- React SPA: InvestPage (عرض الصفقات + المشاركة)
- Flutter: DealInvestmentScreen

## اختبارات
- المشاركة في صفقة مفتوحة ← 200
- المشاركة بمبلغ يتجاوز الهدف ← 400
- المشاركة بعد إغلاق الصفقة ← 400
- التحقق من حساب الربح: Invest 100 USD في صفقة 8% = 8 USD ربح
- استعراض الصفقات المتاحة ← 200
