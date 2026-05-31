# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [RegisterScreen] → [AuthRepository] → [HTTP Request]            │
└────────────────────────────────┬─────────────────────────────────┘
                                  │ POST /api/v1/register
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                       │
│  Route::post('/register', [AuthController::class, 'register'])   │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AuthController                                 │
│  1. Validate input                                               │
│  2. Create user (User::create)                                    │
│  ┌─ 3. Event: User::created (Eloquent) ──────────────────────┐   │
│  │    ↓                                                       │   │
│  │  Listener: CreateUserWallets                               │   │
│  │    ├── Wallet::create(SYP, balance=0)                      │   │
│  │    ├── Wallet::create(USD, balance=0)                      │   │
│  │    └── Transaction::create(USD, +5, type=deposit)          │   │
│  │    ↓                                                       │   │
│  │  Event: WalletCreated (Custom)                             │   │
│  │    └── Listener: SendWelcomeNotification                   │   │
│  └────────────────────────────────────────────────────────────┘   │
│  4. Return token + user data                                     │
└────────────────────────────────┬─────────────────────────────────┘
                                  │
                     ┌────────────┼────────────┐
                     ▼            ▼            ▼
           ┌────────────┐ ┌────────────┐ ┌────────────┐
           │ MySQL       │ │ Redis      │ │ Queue      │
           │ users +     │ │ Cache      │ │ (Events)   │
           │ wallets     │ │            │ │            │
           └────────────┘ └────────────┘ └──────┬─────┘
                                                │
                                       ┌────────┴────────┐
                                       ▼                 ▼
                                ┌────────────┐   ┌────────────┐
                                │ Listener    │   │ Webhook    │
                                │ WelcomeNotif│   │ (optional) │
                                └────────────┘   └────────────┘
```

## تدفق البيانات بين المكونات (Component Diagram)

```
Component Diagram: Create Wallet Flow

User (Flutter/React)
  │
  ├── RegisterScreen
  │     └── POST /api/v1/register
  │           │
  ▼           ▼
  ┌─────────────────────────────────────────────┐
  │  Laravel AuthController                      │
  │  - validate($request)                        │
  │  - $user = User::create($data)               │
  │  - حدث User::created ينطلق تلقائياً          │
  └──────────────────┬──────────────────────────┘
                     │
  ┌──────────────────┴──────────────────────────┐
  │  CreateUserWallets (Listener)               │
  │  - CreateWalletService::createWallets($user) │
  │  ┌─────────────────────────────────┐        │
  │  │ Wallet::create(SYP, 0)          │        │
  │  │ Wallet::create(USD, 0)          │        │
  │  │ DB::transaction {               │        │
  │  │   Wallet::increment(USD, 5)     │        │
  │  │   Transaction::create(deposit)  │        │
  │  │ }                               │        │
  │  └─────────────────────────────────┘        │
  └──────────────────┬──────────────────────────┘
                     │
  ┌──────────────────┴──────────────────────────┐
  │  MySQL (wallets + transactions)             │
  │  - UNIQUE(user_id, currency)                │
  │  - UNIQUE(wallet_number)                    │
  └─────────────────────────────────────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Listeners/CreateUserWallets.php
├── app/Listeners/SendWelcomeNotification.php
├── app/Events/WalletCreated.php
├── app/Services/CreateWalletService.php
├── app/Services/WalletService.php
├── app/Models/Wallet.php
├── app/Models/User.php
├── app/Models/Transaction.php
├── database/migrations/2024_01_01_000002_create_wallets_table.php
├── database/migrations/2024_01_01_000003_create_transactions_table.php
├── app/Providers/EventServiceProvider.php
└── tests/Feature/CreateWalletTest.php

mobile-app/
└── lib/features/auth/
    └── presentation/screens/register_screen.dart

user-frontend/
└── src/pages/RegisterPage.jsx
```
