# T5 - إيداع عبر بطاقة (Visa/MasterCard)

## الوصف
إضافة رصيد للمحفظة باستخدام بطاقة ائتمان/خصم دولية عبر Stripe أو PayTabs.

## المدخلات
| الحقل | النوع |
|-------|-------|
| currency | enum: SYP, USD |
| amount | decimal, min:10 USD |
| card_token | string (من Stripe/PayTabs) |

## سير العمل
1. إنشاء Payment Intent في Stripe/PayTabs
2. المستخدم يُدخل بيانات البطاقة في واجهة الدفع الآمنة (Stripe Elements)
3. استقبال webhook من Stripe/PayTabs بنجاح الدفع
4. زيادة رصيد المحفظة
5. خصم رسوم الإيداع (2.5%)
6. تسجيل Transaction

## قواعد العمل
- رسوم الإيداع: 2.5% من المبلغ
- المبلغ الذي يضاف للمحفظة: `amount - (amount * 0.025)`
- يتم عبر Stripe (دولي) أو PayTabs (منطقة عربية)
- البطاقة لا تُخزن محلياً — فقط token

## جداول قاعدة البيانات
- transactions (type: 'deposit')
- wallets (balance)

## API Endpoint
`POST /api/v1/deposit/card`
`POST /api/v1/webhooks/stripe`
`POST /api/v1/webhooks/paytabs`

## واجهات المستخدم
- Flutter: CardDepositScreen
- React SPA: DepositPage

## اختبارات
- إيداع بمبلغ صحيح ← 200
- إيداع ببطاقة منتهية الصلاحية ← 400
- استقبال webhook ناجح ← 200 (زيادة الرصيد)
- استقبال webhook فاشل ← 200 (بدون تغيير)
