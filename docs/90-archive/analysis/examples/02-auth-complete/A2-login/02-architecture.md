# 02 - مكان العملية في الأرشيتيكشر (Architecture) — تسجيل الدخول (Login)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [LoginScreen] → [AuthRepository] → [HTTP Request]               │
└────────────────────────────────┬─────────────────────────────────┘
                                 │ POST /api/v1/auth/login
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/auth/login', [AuthController::class, 'login'])     │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                                │
│  ┌─────────────────────┐                                          │
│  │ throttle:10,1       │  ← 10 محاولات كحد أقصى في الدقيقة       │
│  │ (guest)             │  ← فقط للمستخدمين غير المصادقين          │
│  └─────────────────────┘                                          │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AuthController@login                            │
│  1. Validate (LoginRequest)                                       │
│  2. Call AuthService::login()                                     │
│  3. Return response                                               │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AuthService::login()                            │
│  1. Find user by phone                                            │
│  2. Hash::check(password)                                         │
│  3. Check user status (not suspended)                             │
│  4. Check login attempts (rate limit)                             │
│  5. Update last_login_at, last_login_ip, device_id                │
│  6. Delete old tokens                                             │
│  7. Create new JWT Token                                      │
│  8. Return user + token                                           │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
                          ┌──────┴──────┐
                          ▼             ▼
                   ┌────────────┐ ┌────────────┐
                   │ MySQL      │ │ Redis      │
                   │ users +    │ │ failed_    │
                   │ tokens     │ │ attempts   │
                   └────────────┘ └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/AuthController.php
├── app/Http/Requests/LoginRequest.php
├── app/Services/AuthService.php
├── app/Models/User.php
├── app/Exceptions/AccountSuspendedException.php
├── app/Exceptions/AccountLockedException.php
├── app/Exceptions/InvalidCredentialsException.php
└── routes/api.php
```
