# 02 - مكان العملية في الأرشيتيكشر (Architecture) — تسجيل الخروج (Logout)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [SettingsScreen] → [AuthRepository] → [HTTP Request]            │
└────────────────────────────────┬─────────────────────────────────┘
                                 │ POST /api/v1/auth/logout
                                 │ Authorization: Bearer token
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/auth/logout', [AuthController::class, 'logout'])   │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                                │
│  ┌─────────────────────┐                                          │
│  │ auth:api            │  ← يجب أن يكون المستخدم مصادقاً          │
│  └─────────────────────┘                                          │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AuthController@logout                           │
│  1. Get current token from request                                │
│  2. Call AuthService::logout()                                    │
│  3. Return response                                               │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AuthService::logout()                           │
│  1. JWTAuth::invalidate(true)                                     │
│  2. Return success message                                        │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
                          ┌──────┴──────┐
                          ▼             ▼
                    ┌────────────┐
                    │ Redis      │
                    │ (blacklist)│
                    │            │
                    └────────────┘
```

## خيار إضافي — logout-all

```
Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);

// في AuthService:
// JWTAuth::invalidate(true);  — إبطال التوكن الحالي
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/AuthController.php
├── app/Services/AuthService.php
├── app/Models/User.php
└── routes/api.php
```
