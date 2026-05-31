# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [DealInvestScreen] → [InvestRepository] → [HTTP Request]        │
└────────────────────────────────┬─────────────────────────────────┘
                                  │ POST /api/v1/deals/{id}/invest
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/deals/{deal}/invest', [DealController::class, 'invest'])│
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐ │
│  │ auth:api │  │ throttle │  │ verified │  │ kyc_required    │ │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘ │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    DealController (invest)                         │
│  1. Validate input (InvestRequest)                                │
│  2. Call InvestService::invest()                                  │
│  3. Return response                                              │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    InvestService                                   │
│  1. Check deal is active & not full                               │
│  2. Check minimum investment (10 USD)                             │
│  3. DB::transaction {                                             │
│       WalletService::decrement(investor)                          │
│       Deal::increment current_amount                              │
│       DealInvestment::create()                                    │
│  }                                                                │
│  4. event(new InvestmentMade)                                     │
└────────────────────────────────┬─────────────────────────────────┘
                                  │
                     ┌────────────┼────────────┐
                     ▼            ▼            ▼
           ┌────────────┐ ┌────────────┐ ┌────────────┐
           │ MySQL       │ │ Redis      │ │ Queue      │
           │ deals +     │ │ Cache      │ │ (Events)   │
           │ wallets     │ │            │ │            │
           └────────────┘ └────────────┘ └──────┬─────┘
                                                │
                                       ┌────────┴────────┐
                                       ▼                 ▼
                                ┌────────────┐   ┌────────────┐
                                │ Listener    │   │ Notification│
                                │ SendInvestN │   │ FCM/SMS    │
                                └────────────┘   └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/DealController.php
├── app/Http/Requests/InvestRequest.php
├── app/Services/InvestService.php
├── app/Services/WalletService.php
├── app/Models/Deal.php
├── app/Models/DealInvestment.php
├── app/Events/InvestmentMade.php
├── app/Listeners/SendInvestmentNotification.php
├── app/Exceptions/InsufficientBalanceException.php
├── app/Exceptions/DealNotActiveException.php
└── tests/Feature/InvestDealTest.php
```
