# A4 - تسجيل الخروج (Logout)

## الوصف
إبطال التوكن الحالي للمستخدم.

## المدخلات
- Bearer token في Header

## سير العمل
1. استلام التوكن من request
2. حذف التوكن الحالي فقط (currentAccessToken()->delete())
3. Response

## API Endpoint
`POST /api/v1/auth/logout`

## اختبارات
- تسجيل خروج بتوكن صحيح ← 200
- تسجيل خروج بدون توكن ← 401
- استخدام التوكن بعد الحذف ← 401
