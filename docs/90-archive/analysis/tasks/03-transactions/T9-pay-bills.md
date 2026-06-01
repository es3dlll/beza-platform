# T9 - دفع فواتير

## الوصف
دفع فواتير الخدمات (كهرباء، ماء، هاتف، إنترنت).

## المدخلات
| الحقل | النوع |
|-------|-------|
| bill_type | enum: electricity, water, phone, internet |
| bill_number | string (رقم الاشتراك) |
| amount | decimal |
| currency | enum: SYP, USD |
| pin | string, size:4 |

## سير العمل
1. التحقق من PIN
2. التحقق من رصيد كافٍ
3. الاتصال بـ API مزود الخدمة (أو محاكاة)
4. DB::beginTransaction()
5. خصم المبلغ من المحفظة
6. تسجيل Transaction (type: bill_payment)
7. DB::commit()
8. إشعار المستخدم

## قواعد العمل
- رسوم الخدمة: حسب اتفاقية كل مزود
- فترة التفعيل: P2 (إطلاق متأخر)

## API Endpoint
`POST /api/v1/pay/bill`
`GET /api/v1/pay/bill-types`

## أولوية التنفيذ
P2

## اختبارات
- دفع فاتورة موجودة ← 200
- دفع برصيد غير كاف ← 400
- دفع برقم اشتراك خاطئ ← 400
