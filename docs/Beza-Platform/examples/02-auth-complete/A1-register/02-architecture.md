# 02 - مكان العملية في الأرشيتيكشر (Architecture) — تسجيل مستخدم جديد (Register)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [RegisterScreen] → [AuthRepository] → [HTTP Request]            │
└────────────────────────────────┬─────────────────────────────────┘
                                 │ POST /api/v1/auth/register
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/auth/register', [AuthController::class, 'register'])│
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                         No Middleware                             │
│  (المستخدم غير مصادق بعد — لا حاجة لـ auth:api)              │
│  فقط throttle:10,1 لمنع السبام                                    │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AuthController@register                         │
│  1. Validate (RegisterRequest)                                    │
│  2. Call AuthService::register()                                  │
│  3. Return response                                               │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AuthService::register()                         │
│  1. DB::beginTransaction()                                        │
│  2. User::create — uuid, name, phone, password, pin_code, status  │
│  3. Wallet::create × 2 — SYP(0) + USD(5)                         │
│  4. DB::commit()                                                  │
│  5. event(new UserRegistered)                                     │
│  6. Create JWT Token                                          │
│  7. NotificationService::sendWelcomeSms                           │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
          ┌────────────┐ ┌────────────┐ ┌────────────┐
          │ MySQL       │ │ Redis      │ │ Queue      │
          │ users +     │ │ (Cache)    │ │ (SMS/Notif)│
          │ wallets     │ │            │ │            │
          └────────────┘ └────────────┘ └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/AuthController.php
├── app/Http/Requests/RegisterRequest.php
├── app/Services/AuthService.php
├── app/Models/User.php
├── app/Models/Wallet.php
├── app/Events/UserRegistered.php
├── app/Listeners/CreateUserWallets.php
├── app/Listeners/SendWelcomeNotification.php
├── app/Exceptions/UserAlreadyExistsException.php
├── database/migrations/xxxx_xx_xx_create_users_table.php
├── database/migrations/xxxx_xx_xx_create_wallets_table.php
└── routes/api.php
```
