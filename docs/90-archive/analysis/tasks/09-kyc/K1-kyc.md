# K1 - التحقق من الهوية (KYC)

## الوصف
تقديم وثائق الهوية للتحقق من المستخدم ورفع حالة الحساب.

## تقديم وثائق KYC

### المدخلات
| الحقل | النوع |
|-------|-------|
| id_type | enum: national_id, passport, driver_license |
| id_number | string |
| front_image | file (صورة وجه البطاقة) |
| back_image | file (صورة ظهر البطاقة) |
| selfie | file (صورة شخصية) |

### سير العمل
1. رفع الصور إلى S3/OSS
2. إنشاء KycDocument (status: pending)
3. تحديث user.kyc_status = 'pending'
4. استدعاء Shufti Pro API للتحقق الآلي
5. إذا نجح التحقق الآلي → kyc_status = 'verified', user.status = 'active'
6. إذا فشل → مراجعة يدوية من المشرف

## مراجعة KYC (Admin)

### الإجراءات
- الموافقة: user.kyc_status = 'verified', user.status = 'active'
- الرفض: user.kyc_status = 'rejected' + سبب الرفض

### API Endpoint
`POST /api/v1/kyc/submit`
`GET /api/v1/kyc/status`
`POST /api/v1/admin/kyc/{id}/approve`
`POST /api/v1/admin/kyc/{id}/reject`

## قواعد العمل
- بدون KYC موثق: حد معاملات منخفض
- KYC إجباري للمعاملات > 1,000 USD
- الوثائق تُحفظ 7 سنوات للامتثال

## جداول قاعدة البيانات
- kyc_documents
- users (kyc_status, status)

## واجهات المستخدم
- Flutter: KycScreen (تصوير البطاقة + selfie)
- React Admin: KycReview

## اختبارات
- تقديم وثائق كاملة ← 200
- تقديم بدون صورة ← 422
- الموافقة على KYC ← user.status = active
- رفض KYC ← user.kyc_status = rejected
