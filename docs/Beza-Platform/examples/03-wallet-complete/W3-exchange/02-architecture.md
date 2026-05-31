# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [ExchangeScreen] → [ExchangeRepository] → [HTTP Request]        │
└────────────────────────────────┬─────────────────────────────────┘
                                  │ POST /api/v1/wallet/exchange
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                       │
│  Route::post('/wallet/exchange', [ExchangeController::class, 'exchange'])│
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
│                    ExchangeController                              │
│  1. Validate input                                                │
│  2. Call ExchangeService::exchange()                               │
│  3. Return response                                               │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    ExchangeService                                 │
│  1. Validate currencies (SYP↔USD, not same)                      │
│  2. Check minimum amount                                         │
│  3. Get exchange rate + calculate fee                              │
│  4. Check fromWallet balance                                       │
│  5. DB::transaction {                                             │
│       WalletService::decrement(fromWallet, amount + fee)          │
│       WalletService::increment(toWallet, convertedAmount)         │
│       Transaction::create(type: exchange)                         │
│  }                                                                │
│  6. Cache::forget balance                                         │
│  7. event(new ExchangeCompleted)                                  │
└────────────────────────────────┬─────────────────────────────────┘
                                  │
                     ┌────────────┼────────────┐
                     ▼            ▼            ▼
           ┌────────────┐ ┌────────────┐ ┌────────────┐
           │ MySQL       │ │ Redis      │ │ Queue      │
           │ wallets +   │ │ Cache      │ │ (Events)   │
           │ transactions│ │ + Rates    │ │            │
           └────────────┘ └────────────┘ └──────┬─────┘
                                                │
                                       ┌────────┴────────┐
                                       ▼                 ▼
                                ┌────────────┐   ┌────────────┐
                                │ Listener    │   │ Webhook    │
                                │ SendNotif.  │   │ (optional) │
                                └────────────┘   └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/ExchangeController.php
├── app/Http/Requests/ExchangeRequest.php
├── app/Http/Resources/ExchangeResource.php
├── app/Services/ExchangeService.php
├── app/Services/WalletService.php
├── app/Services/RateService.php
├── app/Models/Wallet.php
├── app/Models/Transaction.php
├── app/Events/ExchangeCompleted.php
├── app/Listeners/SendExchangeNotification.php
├── config/beza.php (exchange rates, fees)
├── routes/api.php
└── tests/Feature/ExchangeTest.php

mobile-app/
├── lib/features/exchange/
│   ├── presentation/
│   │   ├── screens/exchange_screen.dart
│   │   ├── widgets/exchange_form.dart
│   │   └── bloc/exchange_bloc.dart
│   ├── domain/
│   │   ├── entities/exchange_entity.dart
│   │   └── repositories/i_exchange_repository.dart
│   └── data/
│       └── repositories/exchange_repository.dart

user-frontend/
├── src/pages/ExchangePage.jsx
├── src/components/Exchange/ExchangeForm.jsx
└── src/hooks/useExchange.js
```
