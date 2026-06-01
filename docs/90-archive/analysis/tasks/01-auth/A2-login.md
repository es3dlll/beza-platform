# A2 - تسجيل الدخول (Login)

## الوصف
مصادقة المستخدم وإصدار توكن وصول.

## المدخلات
| الحقل | النوع | المتطلبات |
|-------|-------|-----------|
| phone | string | required |
| password | string | required |

## المخرجات
- user: id, name, phone, status, kyc_status
- token: Bearer token

## سير العمل
1. البحث عن user بـ phone
2. التحقق من password بـ Hash::check
3. التحقق من status (ممنوع إذا suspended)
4. تحديث last_login_at, last_login_ip, device_id
5. حذف التوكنات القديمة
6. إنشاء توكن جديد بـ abilities: ['user']
7. Response

## قواعد العمل
- بعد 5 محاولات فاشلة → قفل الحساب 15 دقيقة
- الحساب المعلق (suspended) لا يمكنه الدخول
- يتم حذف التوكنات القديمة لمنع التراكم

## جداول قاعدة البيانات
- users
- personal_access_tokens (Sanctum)

## API Endpoint
`POST /api/v1/auth/login`

## اختبارات
- دخول بمعلومات صحيحة ← 200
- دخول برقم غير موجود ← 401
- دخول بكلمة سر خاطئة ← 401
- دخول لحساب معلق ← 403
