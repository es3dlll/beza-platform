# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [HomeScreen] → [BalanceRepository] → [HTTP Request]             │
└────────────────────────────────┬─────────────────────────────────┘
                                  │ GET /api/v1/wallet/balance
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                       │
│  Route::get('/wallet/balance', [BalanceController::class, 'index'])│
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                               │
│  ┌──────────┐  ┌──────────┐                                      │
│  │ auth:api │  │ throttle │                                      │
│  └──────────┘  └──────────┘                                      │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    BalanceController                               │
│  1. Call BalanceService::getBalance($user)                       │
│  2. Return response                                              │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    BalanceService                                  │
│  1. Check Redis Cache (key: "balance:user:{id}")                 │
│  2. If cached → return cached data                               │
│  3. If not → query DB                                            │
│  4. Store in Redis for 30 seconds                                │
│  5. Return formatted balance                                     │
└────────────────────────────────┬─────────────────────────────────┘
                                  │
                     ┌────────────┼────────────┐
                     ▼            ▼            ▼
           ┌────────────┐ ┌────────────┐
           │ MySQL       │ │ Redis      │
           │ wallets     │ │ Cache      │
           └────────────┘ └────────────┘
```

## تدفق البيانات بين المكونات (Component Diagram)

```
Component Diagram: Balance Flow

User (Flutter/React)
  │
  ├── HomeScreen / DashboardPage
  │     └── useBalance / BalanceBloc
  │           └── GET /api/v1/wallet/balance
  │                 │
  ▼                 ▼
  ┌─────────────────────────────────────────────┐
  │  BalanceController                           │
  │  - $user = $request->user()                 │
  │  - $service->getBalance($user)              │
  └──────────────────┬──────────────────────────┘
                     │
  ┌──────────────────┴──────────────────────────┐
  │  BalanceService                              │
  │  ┌─────────────────────────────────┐        │
  │  │ cache_key = "balance:{$id}"    │        │
  │  │ if Redis::has(cache_key)       │        │
  │  │   return Redis::get(cache_key) │        │
  │  │                                 │        │
  │  │ $wallets = Wallet::where()      │        │
  │  │ Redis::setex(cache_key, 30, ..) │        │
  │  │ return $wallets                 │        │
  │  └─────────────────────────────────┘        │
  └──────────────────┬──────────────────────────┘
                     │
  ┌──────────────────┴──────────────────────────┐
  │  MySQL + Redis                               │
  └─────────────────────────────────────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/BalanceController.php
├── app/Http/Resources/BalanceResource.php
├── app/Services/BalanceService.php
├── app/Services/WalletService.php
├── app/Models/Wallet.php
├── app/Models/User.php
└── tests/Feature/BalanceTest.php

mobile-app/
└── lib/features/home/
    ├── presentation/
    │   ├── screens/home_screen.dart
    │   └── bloc/balance_bloc.dart
    └── data/
        └── repositories/balance_repository.dart

user-frontend/
└── src/
    ├── pages/DashboardPage.jsx
    └── hooks/useBalance.js
```
