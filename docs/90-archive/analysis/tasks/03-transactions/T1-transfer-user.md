# T1 - تحويل بين المستخدمين

## الوصف
تحويل أموال من مستخدم إلى آخر باستخدام رقم الهاتف.

## المدخلات
| الحقل | النوع | المتطلبات |
|-------|-------|-----------|
| to_phone | string | موجود في users,phone |
| amount | decimal | min:1 |
| currency | enum | SYP, USD |
| pin | string | size:4 |

## سير العمل
1. Validate inputs
2. البحث عن المستخدم المستقبل بـ to_phone
3. منع التحويل إلى النفس
4. التحقق من PIN
5. البحث عن محفظة المصدر والوجهة (بنفس العملة)
6. التحقق من رصيد كافٍ
7. DB::beginTransaction()
8. decrement محفظة المرسل
9. increment محفظة المستقبل
10. إنشاء Transaction (type: 'transfer', fee: 0)
11. DB::commit()
12. event(new TransactionCompleted) → إشعارات
13. Response

## قواعد العمل
- رسوم التحويل بين المستخدمين: 0% (مجاني)
- الحد اليومي للتحويل: 2,000 USD أو 2,000,000 SYP
- لا يمكن التحويل إلى النفس
- PIN إجباري للتأكيد

## جداول قاعدة البيانات
- users
- wallets (balance, decrement, increment)
- transactions (type: 'transfer', fee: 0)

## API Endpoint
`POST /api/v1/transfer`

## واجهات المستخدم
- Flutter: TransferForm, TransferScreen
- React SPA: TransferPage, TransferForm

## اختبارات
- تحويل لمستخدم موجود ← 200
- تحويل برصيد غير كاف ← 400
- تحويل برقم PIN خاطئ ← 400
- تحويل إلى النفس ← 400
- تحويل لرقم غير موجود ← 422
- التحقق من تطابق الرصيد بعد التحويل
- التحقق من إرسال الإشعار
