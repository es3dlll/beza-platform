# 03 - تدفق البيانات الكامل — المصادقة الثنائية (2FA)

## سلسلة الاستدعاءات الكاملة — تفعيل 2FA

```
  User              Flutter/React          Laravel API       TwoFactorService       MySQL
   │                     │                     │                   │                 │
   │  Click "تفعيل 2FA"  │                     │                   │                 │
   │────────────────────>│                     │                   │                 │
   │                     │                     │                   │                 │
   │                     │  POST /auth/2fa/    │                   │                 │
   │                     │  enable             │                   │                 │
   │                     │────────────────────>│                   │                 │
   │                     │                     │                   │                 │
   │                     │                     │  auth:api     │                 │
   │                     │                     │                   │                 │
   │                     │                     │  Google2FA::      │                 │
   │                     │                     │  generateSecretKey│                 │
   │                     │                     │  ()               │                 │
   │                     │                     │──────────────────>│                 │
   │                     │                     │                   │                 │
   │                     │                     │  Encrypt secret   │                 │
   │                     │                     │                   │                 │
   │                     │                     │  Save to user     │                 │
   │                     │                     │  two_factor_secret│                 │
   │                     │                     │──────────────────>│────────────────>│
   │                     │                     │                   │                 │
   │                     │                     │  Generate QR URL  │                 │
   │                     │                     │                   │                 │
   │                     │  Response 200       │                   │                 │
   │                     │  {qr_code, secret}  │                   │                 │
   │                     │<────────────────────│                   │                 │
   │                     │                     │                   │                 │
   │  Scan QR code       │                     │                   │                 │
   │  with Google Auth.  │                     │                   │                 │
   │<────────────────────│                     │                   │                 │
```

## سلسلة الاستدعاءات الكاملة — التحقق من 2FA

```
  User              Flutter/React          Laravel API       TwoFactorService       MySQL
   │                     │                     │                   │                 │
   │  Enter 6-digit      │                     │                   │                 │
   │  code from app      │                     │                   │                 │
   │────────────────────>│                     │                   │                 │
   │                     │                     │                   │                 │
   │                     │  POST /auth/2fa/    │                   │                 │
   │                     │  verify {code}      │                   │                 │
   │                     │────────────────────>│                   │                 │
   │                     │                     │                   │                 │
   │                     │                     │  throttle:5,1    │                 │
   │                     │                     │                   │                 │
   │                     │                     │  Get user's       │                 │
   │                     │                     │  two_factor_secret│                 │
   │                     │                     │──────────────────>│────────────────>│
   │                     │                     │                   │                 │
   │                     │                     │  Decrypt secret   │                 │
   │                     │                     │                   │                 │
   │                     │                     │  Google2FA::      │                 │
   │                     │                     │  verifyKey(code,  │                 │
   │                     │                     │  secret)          │                 │
   │                     │                     │                   │                 │
   │                     │                     │  SET two_factor_  │                 │
   │                     │                     │  confirmed = true │                 │
   │                     │                     │──────────────────>│────────────────>│
   │                     │                     │                   │                 │
   │                     │  Response 200       │                   │                 │
   │                     │  {2fa_confirmed:    │                   │                 │
   │                     │   true}             │                   │                 │
   │                     │<────────────────────│                   │                 │
```
