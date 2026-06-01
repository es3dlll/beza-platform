# 20 - تدقيق أمن 2FA (Security Audit)

## 1. قوة Secret Key

| الممارسة | الحالة |
|----------|--------|
| طول Secret: 32 بايت (160 بت) | ✅ |
| Secret عشوائي آمن (random_bytes) | ✅ |
| Secret مشفر في قاعدة البيانات | ✅ |
| Secret لا يظهر بعد التفعيل | ✅ |

## 2. حماية رموز الاسترداد

| الممارسة | الحالة |
|----------|--------|
| رموز استرداد مشفرة | ✅ |
| عرضها مرة واحدة فقط | ✅ |
| استهلاكها بعد الاستخدام | ✅ |
| إعادة التوليد متاحة | ✅ |

## 3. منع هجمات Replay

```php
// كل رمز يستخدم لمرة واحدة (Timestamp tracking)
session(['last_2fa_timestamp' => now()]);
```

## 4. قفل 2FA (Rate Limiting)

```php
// 5 محاولات خاطئة ← قفل 15 دقيقة
RateLimiter::hit('2fa:' . $user->id, 900);
if (RateLimiter::tooManyAttempts('2fa:' . $user->id, 5)) {
    throw new \App\Exceptions\TooManyAttemptsException();
}
```

## 5. تدقيق (Logging)

| الحدث | المسجل |
|-------|--------|
| تفعيل 2FA | audit_logs |
| تعطيل 2FA | audit_logs |
| محاولة 2FA ناجحة | audit_logs |
| محاولة 2FA فاشلة | audit_logs + إشعار |
| استخدام رمز استرداد | audit_logs |

## 6. قائمة التحقق الأمني

- [ ] Secret key مشفر في قاعدة البيانات
- [ ] رمز 2FA صالح لمدة 30 ثانية فقط
- [ ] منع إعادة استخدام الرمز (Replay protection)
- [ ] Rate limiting على محاولات 2FA
- [ ] 8 رموز استرداد عند التفعيل
- [ ] إشعار عند كل محاولة 2FA فاشلة
- [ ] 2FA إجباري للمشرفين
- [ ] 2FA إجباري للمعاملات > 1000 USD
- [ ] HTTPS مطلوب (منع MITM)
- [ ] CORS محدد (منع تسريب QR code)
