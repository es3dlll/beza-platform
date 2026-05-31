# W3 - تحويل بين العملات (Currency Exchange)

## الوصف
تحويل أموال بين محفظة SYP ومحفظة USD لنفس المستخدم.

## المدخلات
| الحقل | النوع | المتطلبات |
|-------|-------|-----------|
| from_currency | enum | SYP, USD |
| to_currency | enum | SYP, USD (مختلفة عن from) |
| amount | decimal | min:1 |
| pin | string | size:4 |

## سير العمل
1. التحقق من PIN
2. التحقق من رصيد المحفظة المصدر
3. الحصول على سعر الصرف من Cache (exchange_rate_syp_usd)
4. حساب المبلغ المحول: `amount * rate` (أو `amount / rate`)
5. DB::beginTransaction()
6. خصم من المحفظة المصدر
7. إضافة إلى المحفظة الوجهة
8. تسجيل Transaction من نوع 'exchange'
9. خصم رسوم 0.5%
10. DB::commit()

## قواعد العمل
- رسوم التحويل: 0.5% من المبلغ
- سعر الصرف من Cache + هامش ربح 0.5%
- الحد الأدنى للتحويل: 10 USD أو ما يعادله SYP
- لا يمكن التحويل لنفس العملة

## جداول قاعدة البيانات
- wallets (balance)
- transactions (type: 'exchange')
- exchange_rates (Cache/Redis)

## API Endpoint
`POST /api/v1/wallet/exchange`

## اختبارات
- تحويل SYP → USD بمبلغ صحيح ← 200
- تحويل برصيد غير كاف ← 400
- تحويل بنفس العملة ← 400
- التحقق من خصم الرسوم (0.5%)
