# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    React Admin SPA                                │
│  [AdminDealCreateScreen] → [AdminDealRepository] → [HTTP]        │
└────────────────────────────────┬─────────────────────────────────┘
                                  │ POST /api/v1/admin/deals
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/admin/deals', [AdminDealController::class, 'store'])│
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐ │
│  │ auth:api │  │ is_admin │  │ throttle │  │ verified         │ │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘ │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AdminDealController                            │
│  1. Validate input (DealStoreRequest)                             │
│  2. Call AdminDealService::create()                               │
│  3. Return response                                              │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    AdminDealService                                │
│  1. Set status = pending                                          │
│  2. DB::transaction { Deal::create() }                            │
│  3. event(new DealCreated)                                        │
│  4. Return deal resource                                          │
└────────────────────────────────┬─────────────────────────────────┘
                                  │
                     ┌────────────┼────────────┐
                     ▼            ▼            ▼
           ┌────────────┐ ┌────────────┐ ┌────────────┐
           │ MySQL       │ │ Redis      │ │ Queue      │
           │ deals +     │ │ Cache      │ │ (Events)   │
           │ transactions │ │            │ │            │
           └────────────┘ └────────────┘ └──────┬─────┘
                                                │
                                       ┌────────┴────────┐
                                       ▼                 ▼
                                ┌────────────┐   ┌────────────┐
                                │ Listener    │   │ Webhook    │
                                │ SendDealNot.│   │ (optional) │
                                └────────────┘   └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/Admin/AdminDealController.php
├── app/Http/Requests/DealStoreRequest.php
├── app/Services/AdminDealService.php
├── app/Services/DealService.php
├── app/Models/Deal.php
├── app/Models/DealInvestment.php
├── app/Events/DealCreated.php
├── app/Listeners/SendDealCreatedNotification.php
├── database/migrations/2024_01_01_000010_create_deals_table.php
├── database/migrations/2024_01_01_000011_create_deal_investments_table.php
├── routes/api.php
└── tests/Feature/AdminDealTest.php
```
