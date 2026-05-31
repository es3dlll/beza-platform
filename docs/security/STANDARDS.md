# Security Standards - معايير الأمان

## المصادقة (Authentication)

- **JWT** قصير العمر (15 دقيقة)
- **Refresh Token** (7 أيام، دوران تلقائي)
- **Device Binding** للمعاملات المالية
- **OTP** احتياطي للمصادقة متعددة العوامل
- **Biometric** (Face ID / Fingerprint) للجوال

## التفويض (Authorization)

```php
// RBAC: أدوار المستخدمين
'admin', 'compliance_officer', 'operator', 'agent', 'user'

// ABAC: صلاحيات قائمة على السمات
$user->can('transfer', [$wallet, $amount])
```

## التشفير (Encryption)

| البيانات | طريقة التشفير |
|----------|--------------|
| SSL/TLS | TLS 1.3 |
| البيانات المخزنة (حساسة) | AES-256-GCM |
| كلمات المرور | Bcrypt |
| مفاتيح API | Hash HMAC-SHA256 |
| قواعد البيانات | Encrypted at rest |

## قواعد الأمان الصارمة

- لا توجد مفاتيح أو أسرار في الكود المصدري
- التشفير في النقل (TLS 1.3) إلزامي لكل الاتصالات
- تخزين المفاتيح في بيئة معزولة (Vault / .env)
- كل نقطة API محمية بمعدل طلب (Rate Limit)
- تسجيل كل محاولة وصول فاشلة
- جلسات المستخدم منتهية الصلاحية بعد 15 دقيقة من عدم النشاط
