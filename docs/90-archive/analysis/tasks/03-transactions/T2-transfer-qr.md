# T2 - تحويل عبر QR Code

## الوصف
تحويل أموال بمسح رمز QR لمستقبل.

## المدخلات
| الحقل | النوع |
|-------|-------|
| qr_data | string (مشفر: phone أو merchant_id) |
| amount | decimal, min:1 |
| pin | string, size:4 |

## سير العمل
1. فك تشفير qr_data ← `toPhone`
2. نفس سير عمل التحويل العادي (T1)

## قواعد العمل
- QR Code يحتوي على رقم الهاتف مشفراً
- QR Code صالح لمدة 30 دقيقة (ميزة أمان)
- يمكن مسح QR من تطبيق Flutter ومن React SPA

## API Endpoint
`POST /api/v1/transfer/qr`

## واجهات المستخدم
- Flutter: QrScanner widget
- React SPA: QrScanner component

## اختبارات
- مسح QR صحيح وتحويل ← 200
- QR منتهي الصلاحية ← 400
- QR برقم غير موجود ← 404
