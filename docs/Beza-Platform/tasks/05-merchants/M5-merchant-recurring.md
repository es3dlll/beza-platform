# M5 - الفوترة المتكررة (اشتراكات)

## الوصف
إنشاء اشتراكات متكررة تلقائية للعملاء.

## المدخلات
| الحقل | النوع |
|-------|-------|
| customer_email | email |
| amount | decimal |
| currency | enum: SYP, USD |
| interval | enum: weekly, monthly, yearly |
| description | string |

## سير العمل
1. إنشاء Subscription مرتبطة بالتاجر
2. كل فترة (Cron job): إنشاء رابط دفع وإرساله للعميل
3. إذا فشل الدفع: إعادة المحاولة 3 مرات
4. بعد 3 فشل: إلغاء الاشتراك وإشعار الطرفين

## قواعد العمل
- إلغاء الاشتراك من قبل العميل في أي وقت
- إرسال إشعار قبل 3 أيام من كل دفعة

## API Endpoint
`POST /api/v1/merchant/subscriptions`
`DELETE /api/v1/merchant/subscriptions/{id}`
`GET /api/v1/merchant/subscriptions`

## أولوية التنفيذ
P1
