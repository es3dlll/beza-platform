# 02 - بنية نظام 2FA (Architecture)

## موقع 2FA في النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                     Flutter / React UI                             │
│  [Login/Transfer] → [2FA Screen] → [Verify Code] → [Proceed]     │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
┌────────────────────────────────┴─────────────────────────────────┐
│                    Middleware Stack                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐                │
│  │ auth:api │  │ throttle │  │ TwoFactorMiddleware │                │
│  └──────────┘  └──────────┘  └──────────────────┘                │
│                                          │                        │
│                     ┌────────────────────┘                        │
│                     ▼                                             │
│         ┌─────────────────────────────┐                           │
│         │  TwoFactorController         │                           │
│         │  - enable(Request)           │                           │
│         │  - verify(Request)           │                           │
│         │  - disable(Request)          │                           │
│         │  - usingRecoveryCode(Request)│                           │
│         └──────────────┬──────────────┘                           │
│                        │                                           │
│         ┌──────────────┴──────────────┐                           │
│         │  TwoFactorService            │                           │
│         │  - generateSecret()          │                           │
│         │  - verifyCode(secret, code)  │                           │
│         │  - generateRecoveryCodes()   │                           │
│         │  - isRequired(user, action)  │                           │
│         └──────────────┬──────────────┘                           │
│                        │                                           │
│         ┌──────────────┴──────────────┐                           │
│         │  Google2FA (Library)         │                           │
│         │  - PragmaRX\Google2FA        │                           │
│         └─────────────────────────────┘                           │
└──────────────────────────────────────────────────────────────────┘
```

## المكونات

| المكون | التقنية | الوصف |
|--------|---------|-------|
| QR Code Generation | PragmaRX/Google2FA | توليد Secret + QR |
| TOTP Verification | PragmaRX/Google2FA | التحقق من الرمز |
| Recovery Codes | Illuminate\Support\Str | 8 رموز عشوائية |
| Middleware | Laravel Middleware | التحقق قبل العمليات الحساسة |
| Storage | MySQL (encrypted) | تخزين الـ Secret مشفر |
