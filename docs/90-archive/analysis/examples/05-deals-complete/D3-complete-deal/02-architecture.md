# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    React Admin SPA                                │
│  [AdminDealCompleteScreen] → [CompleteRepository] → [HTTP]       │
└────────────────────────────────┬─────────────────────────────────┘
                                  │ POST /api/v1/admin/deals/{id}/complete
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/admin/deals/{deal}/complete', [AdminDealController::class, 'complete'])│
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                               │
│  ┌──────────┐  ┌──────────┐  ┌───────────────────────────────┐  │
│  │ auth:api │  │ is_admin │  │ throttle:15,1               │  │
│  └──────────┘  └──────────┘  └───────────────────────────────┘  │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AdminDealController (complete)                  │
│  1. Validate input (DealCompleteRequest)                          │
│  2. Call ProfitDistributionService::distribute()                  │
│  3. Return response                                              │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    ProfitDistributionService                       │
│  1. Validate deal status = active or filled                       │
│  2. Calculate profit for each investor                            │
│  3. DB::transaction {                                             │
│       foreach (investors) {                                       │
│         WalletService::increment(investor, profit_share)          │
│         Transaction::create(type: investment_profit)              │
│       }                                                           │
│       Deal::update status = completed                             │
│  }                                                                │
│  4. event(new DealCompleted)                                      │
└────────────────────────────────┬─────────────────────────────────┘
                                  │
                           ┌──────┴──────┐
                           ▼             ▼
                    ┌────────────┐ ┌────────────┐
                    │ MySQL      │ │ Queue      │
                    │ transactions│ │ (Events)   │
                    │ wallets    │ │            │
                    └────────────┘ └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/Admin/AdminDealController.php
├── app/Http/Requests/DealCompleteRequest.php
├── app/Services/ProfitDistributionService.php
├── app/Services/WalletService.php
├── app/Models/Deal.php
├── app/Models/DealInvestment.php
├── app/Models/Transaction.php
├── app/Events/DealCompleted.php
├── app/Listeners/SendProfitNotification.php
└── tests/Feature/CompleteDealTest.php
```
