# 20 - أمان عملية التسجيل (Security Audit)

## 1. تشفير كلمة المرور
```php
// Bcrypt — 12 rounds (config(app)
$user->password = Hash::make($request->password);
```

## 2. التحقق من البريد / الهاتف
```php
// الحساب pending حتى تأكيد OTP
$user->markPhoneAsVerified();
```

## 3. حماية من البوتات
```php
// Rate limiting على الـ endpoint
'throttle:3,1'  // 3 محاولات تسجيل في الدقيقة
```

## 4. قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | جميع المدخلات موثقة | ✅ |
| 2 | Parameterized SQL | ✅ |
| 3 | Rate Limiting | ✅ |
| 4 | تشفير كلمة المرور (Bcrypt) | ✅ |
| 5 | Mass assignment protection | ✅ |
| 6 | HTTPS (للإنتاج) | ⏳ |
| 7 | التحقق من OTP عند التسجيل | ✅ |
