# 14 - منع الهجمات — المصادقة الثنائية (2FA)

## Rate Limiting

```php
<?php
// في Route

// تفعيل 2FA — آمن، لا يحتاج rate limit قوي
Route::post('/auth/2fa/enable', [TwoFactorController::class, 'enable'])
    ->middleware('auth:api');

// التحقق من 2FA — حماية من تخمين الرمز
Route::post('/auth/2fa/verify', [TwoFactorController::class, 'verify'])
    ->middleware('auth:api')
    ->middleware('throttle:5,1');
    // 5 محاولات كحد أقصى في الثانية
```

## منع تخمين رمز TOTP

رمز TOTP يتغير كل 30 ثانية — مما يمنع التخمين. لكن نضيف حماية إضافية:

```php
<?php
// في TwoFactorService

private const MAX_2FA_ATTEMPTS = 5;

public function verify(User $user, string $code): void
{
    // التحقق من عدد المحاولات (لكل مستخدم)
    $attemptsKey = '2fa_attempts_' . $user->id;
    $attempts = (int) Cache::get($attemptsKey, 0);

    if ($attempts >= self::MAX_2FA_ATTEMPTS) {
        // إبطال التوكن المؤقت — يضطر لإعادة تسجيل الدخول
        JWTAuth::invalidate(true);
        Cache::forget($attemptsKey);
        throw new InvalidTwoFactorCodeException(
            'تجاوزت عدد المحاولات. يرجى إعادة تسجيل الدخول'
        );
    }

    $secret = $user->two_factor_secret;
    $valid = $this->google2fa->verifyKey($secret, $code, 1);

    if (!$valid) {
        Cache::put($attemptsKey, $attempts + 1, now()->addMinutes(30));
        throw new InvalidTwoFactorCodeException();
    }

    Cache::forget($attemptsKey);
}
```

## منع إعادة استخدام Recovery Codes

```php
<?php
// كل recovery code يستخدم مرة واحدة فقط
// بعد الاستخدام، يحذف من القائمة

public function useRecoveryCode(string $code): bool
{
    $codes = $this->getRecoveryCodes();
    $key = array_search($code, $codes);

    if ($key === false) return false; // غير موجود أو مستخدم مسبقاً

    unset($codes[$key]);
    $this->setRecoveryCodes(array_values($codes));

    return true;
}
```

## أمان Secret Key

```php
<?php
// Secret key مشفر في DB
public function setTwoFactorSecretAttribute(?string $value): void
{
    $this->attributes['two_factor_secret'] = $value
        ? Crypt::encryptString($value)
        : null;
}

// حتى لو تسربت DB، الـ secret غير مقروء
```

## ملخص الحماية

| الهجوم | الحماية |
|--------|---------|
| تخمين TOTP | يتغير كل 30 ثانية + rate limit |
| تكرار محاولات 2FA | 5 محاولات → إبطال التوكن |
| إعادة استخدام recovery code | استخدام لمرة واحدة |
| تسرب secret من DB | مشفر (Crypt::encryptString) |
| تخمين secret | 32 حرف عشوائي (Google2FA) |
