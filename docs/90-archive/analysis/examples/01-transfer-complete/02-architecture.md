# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [TransferScreen] → [TransferRepository] → [HTTP Request]        │
└────────────────────────────────┬─────────────────────────────────┘
                                 │ POST /api/v1/transfer
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                       │
│  Route::post('/transfer', [TransferController::class, 'transfer'])│
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐ │
│  │ auth:api │  │ throttle │  │ verified │  │ Optional: 2FA   │ │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘ │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    TransferController                             │
│  1. Validate input                                               │
│  2. Call TransferService::transfer()                              │
│  3. Return response                                              │
└────────────────────────────────┬─────────────────────────────────┘
                                 ▼
┌──────────────────────────────────────────────────────────────────┐
│                    TransferService                                │
│  1. Verify PIN                                                    │
│  2. Check balance                                                 │
│  3. DB::transaction {                                             │
│       WalletService::decrement(sender)                            │
│       WalletService::increment(receiver)                          │
│       Transaction::create()                                       │
│  }                                                                │
│  4. event(new TransactionCompleted)                               │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
          ┌────────────┐ ┌────────────┐ ┌────────────┐
          │ MySQL       │ │ Redis      │ │ Queue      │
          │ wallets +   │ │ Cache      │ │ (Events)   │
          │ transactions│ │            │ │            │
          └────────────┘ └────────────┘ └──────┬─────┘
                                               │
                                      ┌────────┴────────┐
                                      ▼                 ▼
                               ┌────────────┐   ┌────────────┐
                               │ Listener    │   │ Webhook    │
                               │ SendNotif.  │   │ (optional) │
                               └────────────┘   └────────────┘
```

## تدفق البيانات بين المكونات (Component Diagram)

```
Component Diagram: Transfer Flow

User (Flutter)
  │
  ├── TransferForm (UI Widget)
  │     └── TransferBloc (State Management)
  │           └── TransferRepository (Data Layer)
  │                 └── ApiClient (HTTP)
  │                       └── POST /api/v1/transfer
  │                             │
User (React)                    │
  │                             │
  ├── TransferPage              │
  │     └── TransferForm        │
  │           └── useTransfer   │
  │                 └── api.post│
  │                             │
  ▼                             ▼
  ┌─────────────────────────────────────────────┐
  │  Nginx / Artisan Serve (localhost:8000)      │
  └──────────────────┬──────────────────────────┘
                     │
  ┌──────────────────┴──────────────────────────┐
  │  Laravel Kernel                             │
  │  - Handle (request)                         │
  │  - Through middleware stack                  │
  │  - Route to controller                      │
  └──────────────────┬──────────────────────────┘
                     │
  ┌──────────────────┴──────────────────────────┐
  │  TransferController                          │
  │  - $request->validate()                      │
  │  - app(TransferService)->transfer(...)       │
  │  - return response                           │
  └──────────────────┬──────────────────────────┘
                     │
  ┌──────────────────┴──────────────────────────┐
  │  TransferService                             │
  │  ┌─────────────────────────────────┐        │
  │  │ DB::transaction(function) {     │        │
  │  │   Wallet::decrement()           │        │
  │  │   Wallet::increment()           │        │
  │  │   Transaction::create()         │        │
  │  │ }                               │        │
  │  └─────────────────────────────────┘        │
  │  Event::dispatch(TransactionCompleted)       │
  └──────────────────┬──────────────────────────┘
                     │
  ┌──────────────────┴──────────────────────────┐
  │  MySQL (wallet_balance_lock + transaction)  │
  └─────────────────────────────────────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/TransferController.php
├── app/Http/Requests/TransferRequest.php        (Form Request)
├── app/Services/TransferService.php
├── app/Services/WalletService.php
├── app/Models/Wallet.php
├── app/Models/Transaction.php
├── app/Models/User.php
├── app/Events/TransactionCompleted.php
├── app/Listeners/SendTransactionNotification.php
├── app/Exceptions/InsufficientBalanceException.php
├── app/Exceptions/InvalidPinException.php
├── database/migrations/2024_01_01_000002_create_wallets_table.php
├── database/migrations/2024_01_01_000003_create_transactions_table.php
├── routes/api.php
└── tests/Feature/TransferTest.php

mobile-app/
├── lib/features/transfer/
│   ├── presentation/
│   │   ├── screens/transfer_screen.dart
│   │   ├── widgets/transfer_form.dart
│   │   └── bloc/transfer_bloc.dart
│   ├── domain/
│   │   ├── entities/transfer_entity.dart
│   │   └── repositories/i_transfer_repository.dart
│   └── data/
│       ├── repositories/transfer_repository.dart
│       └── models/transfer_request_model.dart

user-frontend/
├── src/pages/TransferPage.jsx
├── src/components/Transfer/TransferForm.jsx
├── src/hooks/useTransfer.js
└── src/services/api.js
```
