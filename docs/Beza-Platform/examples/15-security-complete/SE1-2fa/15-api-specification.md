# 15 - API 2FA (API Specification)

## المسارات

```php
// routes/api.php
Route::middleware('auth:jwt')->prefix('2fa')->group(function () {
    Route::post('/enable', [TwoFactorController::class, 'enable']);
    Route::post('/verify', [TwoFactorController::class, 'verify']);
    Route::post('/disable', [TwoFactorController::class, 'disable']);
    Route::get('/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes']);
    Route::post('/recovery-codes/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes']);
});
```

## POST /api/v1/2fa/enable

```json
// الطلب
{
    "password": "current_password"
}

// الرد (200)
{
    "success": true,
    "data": {
        "secret": "4S2J5SJ4N5HUI5KJN4QGM3DNXUZAS57D",
        "qr_code_url": "otpauth://totp/Beza:user@beza.example?secret=...",
        "qr_code_svg": "<svg>...</svg>"
    }
}
```

## POST /api/v1/2fa/verify

```json
// الطلب
{
    "code": "123456"
}

// الرد (200)
{
    "success": true,
    "message": "تم تفعيل المصادقة الثنائية بنجاح",
    "data": {
        "recovery_codes": ["A3F2-B7D1-9E4C", ...]
    }
}
```

## POST /api/v1/2fa/disable

```json
// الطلب
{
    "password": "current_password"
}

// الرد (200)
{
    "success": true,
    "message": "تم إلغاء المصادقة الثنائية"
}
```

## استخدام 2FA في الطلبات

```json
// إرسال رمز 2FA في الهيدر
X-2FA-Code: 123456

// أو في جسم الطلب
{
    "to_phone": "963900000001",
    "amount": 1500,
    "currency": "USD",
    "pin": "1234",
    "two_factor_code": "123456"
}
```

## استجابة طلب يحتاج 2FA

```json
// 402 Payment Required
{
    "success": false,
    "message": "مطلوب رمز المصادقة الثنائية",
    "requires_2fa": true
}
```
