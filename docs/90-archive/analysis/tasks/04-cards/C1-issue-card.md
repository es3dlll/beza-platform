# C1 - إصدار بطاقة

## الوصف
إصدار بطاقة افتراضية (فورية) أو فيزيائية (تُرسل بالبريد).

## المدخلات
| الحقل | النوع |
|-------|-------|
| type | enum: virtual, physical |
| currency | enum: SYP, USD |
| card_network | enum: visa, mastercard |

## سير العمل (Virtual Card)
1. استدعاء API مزود البطاقات (Stripe Issuing / Marqeta)
2. إنشاء سجل VirtualCard
3. إظهار بيانات البطاقة (رقم، تاريخ انتهاء، CVV)
4. إشعار المستخدم

## سير العمل (Physical Card)
1. Collect shipping address
2. استدعاء API مزود البطاقات (طلب تصنيع + شحن)
3. إنشاء VirtualCard مع status = 'processing'
4. تحديث tracking_number عند الشحن
5. تحديث status = 'active' عند التفعيل

## قواعد العمل
- البطاقة الافتراضية فورية
- البطاقة الفيزيائية: 7-10 أيام للتوصيل
- يمكن للمستخدم امتلاك حتى 3 بطاقات
- البطاقة مرتبطة بمحفظة المستخدم

## جداول قاعدة البيانات
- virtual_cards: user_id, type, network, last_four, status, expiry, cvv (مشفر)
- card_transactions

## API Endpoint
`POST /api/v1/cards/issue`
`GET /api/v1/cards`

## أولوية التنفيذ
P1 (افتراضية), P2 (فيزيائية)

## اختبارات
- إصدار بطاقة افتراضية ← 200
- إصدار بطاقة فيزيائية ← 200 (processing)
- محاولة إصدار أكثر من 3 بطاقات ← 400
- عرض قائمة البطاقات ← 200
