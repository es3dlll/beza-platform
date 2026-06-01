# 02 - مكان العملية في الأرشيتيكشر — المصادقة الثنائية (2FA)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [TwoFactorScreen] → [AuthRepository] → [HTTP Request]           │
└────────────────────────────────┬─────────────────────────────────┘
                     ┌───────────┴───────────┐
                     ▼                       ▼
        POST /api/v1/auth/2fa/enable    POST /api/v1/auth/2fa/verify
                     │                       │
                     ▼                       ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/auth/2fa/enable', [TwoFactorController::class, ..])│
│  Route::post('/auth/2fa/verify', [TwoFactorController::class, ..])│
└────────────────────────────────┬─────────────────────────────────┘
                                 │
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                                │
│  ┌─────────────────────┐                                          │
│  │ auth:api        │  ← يجب أن يكون المستخدم مصادقاً          │
│  │ throttle:5,1        │  ← 5 محاولات في الثانية للتحقق          │
│  └─────────────────────┘                                          │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    TwoFactorController                              │
│  enable:                                                          │
│  1. Create Google2FA instance                                     │
│  2. generateSecretKey()                                           │
│  3. Encrypt and store secret in DB                                │
│  4. Generate QR Code URL                                          │
│  5. Return qr_code (base64) + secret                              │
│                                                                   │
│  verify:                                                          │
│  1. Validate code (6 digits)                                      │
│  2. Decrypt two_factor_secret                                     │
│  3. verifyKey(code, secret)                                       │
│  4. Mark two_factor_confirmed = true                              │
│  5. Generate recovery codes                                       │
│  6. Return success                                                │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
                          ┌──────┴──────┐
                          ▼             ▼
                   ┌────────────┐ ┌────────────┐
                   │ MySQL      │ │ Redis      │
                   │ users      │ │ 2fa_attempt│
                   │ (2FA cols) │ │ (rate limit│
                   └────────────┘ └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/TwoFactorController.php
├── app/Http/Requests/TwoFactorRequest.php
├── app/Http/Requests/VerifyTwoFactorRequest.php
├── app/Services/TwoFactorService.php
├── app/Models/User.php
├── app/Exceptions/InvalidTwoFactorCodeException.php
├── app/Middleware/RequireTwoFactor.php
└── routes/api.php
```
