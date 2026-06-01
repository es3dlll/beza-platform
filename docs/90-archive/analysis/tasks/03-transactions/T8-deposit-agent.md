# T8 - إيداع نقدي عبر وكيل (Agent Cash In)

## الوصف
إضافة رصيد للمحفظة عبر تسليم نقد للوكيل.

## المدخلات (Agent Side)
| الحقل | النوع |
|-------|-------|
| user_phone | string (رقم العميل) |
| amount | decimal, min:1 |
| currency | enum: SYP, USD |

## سير العمل
1. التحقق من أن الوكيل لديه رصيد نقدي كافٍ لصرفه
2. DB::beginTransaction()
3. خفض cash_balance للوكيل
4. زيادة balance لمحفظة العميل
5. إنشاء AgentTransaction (type: cash_in, completed)
6. إنشاء Transaction رئيسي (type: agent_cash_in)
7. ربط AgentTransaction بالـ Transaction
8. DB::commit()
9. إشعار العميل

## قواعد العمل
- الوكيل يجب أن يملك رصيداً نقدياً كافياً
- عمولة الوكيل: 1% (تضاف لحساب الوكيل)
- لا رسوم على العميل للإيداع النقدي
- الحد الأقصى للمعاملة: 5,000 USD

## API Endpoint
`POST /api/v1/agent/cash-in`

## واجهات المستخدم
- Flutter Agent App: CashInScreen

## اختبارات
- إيداع نقدي بمبلغ صحيح ← 200
- إيداع برصيد وكيل غير كاف ← 400
- إيداع لرقم غير موجود ← 404
- التحقق من زيادة رصيد العميل
