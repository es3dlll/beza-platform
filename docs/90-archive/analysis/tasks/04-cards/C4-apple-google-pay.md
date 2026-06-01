# C4 - إضافة إلى Apple/Google Pay

## الوصف
ربط البطاقة الافتراضية بمحافظ Apple Pay أو Google Pay للمعاملات اللاتلامسية.

## المدخلات
| الحقل | النوع |
|-------|-------|
| card_id | id |
| platform | enum: apple_pay, google_pay |

## سير العمل
1. استدعاء API مزود البطاقات لإنشاء token للمحفظة الرقمية
2. تمرير بيانات البطاقة المشفرة إلى Apple/Google Pay
3. Response رابط أو token للإضافة

## قواعد العمل
- يتطلب جهاز متوافق مع Apple Pay / Google Pay
- البطاقة يجب أن تكون نشطة وغير مجمدة

## API Endpoint
`POST /api/v1/cards/{id}/wallet-enroll`

## أولوية التنفيذ
P2
