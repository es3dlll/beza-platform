# 02 - مكان العملية في الأرشيتيكشر (Architecture) — رمز التحقق (OTP)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [OtpScreen] → [OtpRepository] → [HTTP Request]                  │
└────────────────────────────────┬─────────────────────────────────┘
                     ┌───────────┴───────────┐
                     ▼                       ▼
        POST /api/v1/auth/request-otp   POST /api/v1/auth/verify-otp
                     │                       │
                     ▼                       ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/auth/request-otp', [AuthController::class, ...])   │
│  Route::post('/auth/verify-otp', [AuthController::class, ...])   │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware (request-otp)                        │
│  ┌─────────────────────┐                                          │
│  │ throttle:3,60       │  ← 3 طلبات OTP كحد أقصى كل 60 ثانية    │
│  │ (guest)             │                                          │
│  └─────────────────────┘                                          │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AuthController / OtpService                     │
│  request-otp:                                                     │
│  1. Validate phone                                                │
│  2. Generate 6-digit random code                                  │
│  3. Store in Redis: otp_{phone} → {code, expires_at}              │
│  4. Send SMS via SmsService                                       │
│  5. Return success (dev: return OTP)                              │
│                                                                   │
│  verify-otp:                                                      │
│  1. Validate phone + otp                                          │
│  2. Get from Redis: otp_{phone}                                  │
│  3. Compare codes                                                 │
│  4. Update phone_verified_at in users                             │
│  5. Delete from Redis                                             │
│  6. Return success                                                │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
          ┌────────────┐ ┌────────────┐ ┌────────────┐
          │ MySQL      │ │ Redis      │ │ SMS Gateway│
          │ users      │ │ otp_{phone}│ │ Provider   │
          └────────────┘ └────────────┘ └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/AuthController.php
├── app/Http/Requests/RequestOtpRequest.php
├── app/Http/Requests/VerifyOtpRequest.php
├── app/Services/OtpService.php
├── app/Services/SmsService.php
├── app/Exceptions/InvalidOtpException.php
├── app/Exceptions/OtpExpiredException.php
└── routes/api.php
```
