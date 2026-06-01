# A5 - المصادقة الثنائية (2FA - TOTP)

## الوصف
تفعيل والتحقق من المصادقة الثنائية باستخدام Google Authenticator.

## تفعيل 2FA

### المدخلات
- Bearer token

### سير العمل
1. إنشاء Google2FA instance
2. generateSecretKey()
3. حفظ secret مشفراً في `two_factor_secret`
4. إنشاء QR Code URL للتطبيق
5. Response: qr_code (base64), secret (plain text)

### API Endpoint
`POST /api/v1/auth/2fa/enable`

## التحقق من رمز 2FA (تفعيل)

### المدخلات
| الحقل | النوع |
|-------|-------|
| code | string, size:6 |

### سير العمل
1. فك تشفير two_factor_secret
2. verifyKey باستخدام Google2FA
3. إذا صحيح: `two_factor_confirmed = true`
4. Response

### API Endpoint
`POST /api/v1/auth/2fa/verify`

## قواعد العمل
- 2FA إجباري للمعاملات > 1,000 USD
- يمكن للمستخدم تفعيل 2FA اختيارياً للمبالغ الأقل
- التعطيل يتطلب إعادة المصادقة

## جداول قاعدة البيانات
- users (two_factor_secret, two_factor_recovery_codes, two_factor_confirmed)

## اختبارات
- تفعيل 2FA ← QR Code
- التحقق برمز صحيح ← 200
- التحقق برمز خاطئ ← 400
- محاولة معاملة > 1000 USD بدون 2FA ← 403
