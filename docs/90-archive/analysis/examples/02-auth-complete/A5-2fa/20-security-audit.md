# 20 - أمان المصادقة الثنائية (Security Audit)

## 1. إنشاء سر TOTP
```php
// سر فريد لكل مستخدم — مشفر في قاعدة البيانات
$secret = $google2fa->generateSecretKey();
$user->two_factor_secret = encrypt($secret);
```

## 2. رمز الاسترجاع (Recovery Codes)
```php
// 8 أكواد — كل منها HASH — كل كود يستخدم مرة واحدة
$codes = collect(range(1, 8))->map(fn() => Hash::make(bin2hex(random_bytes(5))));
$user->two_factor_recovery_codes = $codes->toArray();
```

## 3. التحقق من TOTP
```php
// نافذة ±1 (30 ثانية) للسماح بفارق التوقيت
$valid = $google2fa->verifyKey($user->two_factor_secret, $request->code, 1);
```

## 4. قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | تشفير secret في DB | ✅ |
| 2 | Recovery codes (مشوشة) | ✅ |
| 3 | كل كود استرجاع يُستخدم مرة واحدة | ✅ |
| 4 | Rate limiting على 2FA (5/دقيقة) | ✅ |
| 5 | إجباري للمعاملات > 1,000 USD | ✅ |
| 6 | HTTPS (للإنتاج) | ⏳ |
| 7 | WebAuthn / Passkeys | 📋 مستقبلاً |
