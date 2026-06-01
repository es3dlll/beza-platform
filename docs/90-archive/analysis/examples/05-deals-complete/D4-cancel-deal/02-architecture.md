# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    React Admin SPA                                │
│  [AdminDealCancelScreen] → [CancelRepository] → [HTTP]           │
└────────────────────────────────┬─────────────────────────────────┘
                                  │ POST /api/v1/admin/deals/{id}/cancel
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/admin/deals/{deal}/cancel', [AdminDealController::class, 'cancel'])│
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
│                    AdminDealController (cancel)                    │
│  1. Validate input (DealCancelRequest)                            │
│  2. Call RefundService::refund()                                  │
│  3. Return response                                              │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    RefundService                                   │
│  1. Validate deal is not completed                                │
│  2. DB::transaction {                                             │
│       foreach (investors) {                                       │
│         WalletService::increment(investor, investment_amount)     │
│         Transaction::create(type: refund)                         │
│       }                                                           │
│       Deal::update status = cancelled                             │
│  }                                                                │
│  3. event(new DealCancelled)                                      │
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
├── app/Http/Requests/DealCancelRequest.php
├── app/Services/RefundService.php
├── app/Services/WalletService.php
├── app/Models/Deal.php
├── app/Models/DealInvestment.php
├── app/Models/Transaction.php
├── app/Events/DealCancelled.php
├── app/Listeners/SendRefundNotification.php
└── tests/Feature/CancelDealTest.php
```
