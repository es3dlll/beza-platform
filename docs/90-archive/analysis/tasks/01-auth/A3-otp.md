# A3 - طلب والتحقق من OTP

## الوصف
إرسال رمز تحقق OTP عبر SMS للمستخدم، والتحقق منه لتأكيد رقم الهاتف.

## طلب OTP

### المدخلات
| الحقل | النوع | المتطلبات |
|-------|-------|-----------|
| phone | string | required |

### سير العمل
1. Validate phone
2. توليد OTP عشوائي (6 أرقام)
3. تخزين في Cache: `otp_{phone}` → value, expiry 300s
4. إرسال SMS عبر Twilio (في الإنتاج)
5. Response

### API Endpoint
`POST /api/v1/auth/request-otp`

## التحقق من OTP

### المدخلات
| الحقل | النوع | المتطلبات |
|-------|-------|-----------|
| phone | string | required |
| otp | string | size:6 |

### سير العمل
1. البحث في Cache عن `otp_{phone}`
2. مقارنة الرمز
3. إذا صحيح: تحديث `phone_verified_at` للمستخدم
4. حذف الرمز من Cache
5. Response

### قواعد العمل
- OTP صالح لمدة 5 دقائق
- كل طلب OTP يلغي الرمز السابق
- في بيئة التطوير: يتم إرجاع OTP في response للتسهيل

### API Endpoint
`POST /api/v1/auth/verify-otp`

## جداول قاعدة البيانات
- users (phone_verified_at)
- Cache (Redis)

## اختبارات
- طلب OTP لرقم موجود ← 200
- إدخال OTP صحيح ← 200
- إدخال OTP خاطئ ← 400
- إدخال OTP منتهي الصلاحية ← 400
