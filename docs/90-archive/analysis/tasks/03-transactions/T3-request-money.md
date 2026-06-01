# T3 - طلب تحويل (Request Money)

## الوصف
إرسال طلب مبلغ لمستخدم آخر (يحتاج موافقة المستقبل).

## المدخلات
| الحقل | النوع |
|-------|-------|
| from_phone | string (الذي سيطلب منه المال) |
| amount | decimal, min:1 |
| currency | enum: SYP, USD |
| note | string, nullable |

## سير العمل
1. البحث عن المستخدم المطلوب
2. إنشاء سجل `transfer_request` في قاعدة البيانات (أو Notification)
3. إرسال إشعار للمستخدم المطلوب
4. عند الموافقة: يتم تنفيذ التحويل كـ T1

## قواعد العمل
- الطلب صالح لمدة 24 ساعة
- يمكن رفض الطلب
- يمكن إلغاء الطلب من قبل المرسل

## API Endpoint
`POST /api/v1/transfer/request`
`POST /api/v1/transfer/request/{id}/approve`
`POST /api/v1/transfer/request/{id}/reject`

## أولوية التنفيذ
P2
