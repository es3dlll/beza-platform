# 02 - مكان لوحة التحكم في الأرشيتيكشر (Architecture)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────┐
│                    React Admin SPA                             │
│  [AdminDashboard] → [statsRepository] → [HTTP GET]           │
└────────────────────────┬─────────────────────────────────────┘
                         │ GET /api/v1/admin/dashboard
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                    │
│  Route::get('/admin/dashboard', [DashboardController::class])│
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    Middleware Stack                            │
│  ┌──────────┐  ┌──────────┐  ┌─────────────────────────────┐│
│  │ auth:api │  │ admin    │  │ throttle:60,1              ││
│  └──────────┘  └──────────┘  └─────────────────────────────┘│
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    AdminDashboardController                    │
│  1. Check cache → return if fresh                             │
│  2. Call DashboardStatsService::getStats()                     │
│  3. Return response                                            │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    DashboardStatsService                       │
│  1. totalUsers = User::count()                                │
│  2. activeUsers = User::whereLastLoginToday()                 │
│  3. totalTxns = Transaction::count()                          │
│  4. volume = Transaction::sum('amount')                       │
│  5. walletsBalance = Wallet::sum('balance')                   │
│  6. merchants = User::whereMerchant()->count()                │
│  7. agents = User::whereAgent()->count()                      │
│  8. charts data (last 30 days)                                │
│  9. Cache::put('dashboard_stats', $data, 300)                 │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
              ┌──────────────────────────┐
              │        Redis Cache        │
              │  dashboard_stats: 5 min   │
              └──────────────────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/Admin/DashboardController.php
├── app/Http/Resources/Admin/DashboardResource.php
├── app/Services/Admin/DashboardStatsService.php
├── app/Services/Admin/CacheService.php
├── app/Models/User.php
├── app/Models/Wallet.php
├── app/Models/Transaction.php
└── routes/api.php

admin-frontend/
├── src/pages/AdminDashboard.jsx
├── src/components/AdminDashboard/
│   ├── StatCard.jsx
│   ├── RevenueChart.jsx
│   ├── TransactionVolumeChart.jsx
│   ├── UserGrowthChart.jsx
│   └── TopMerchantsTable.jsx
├── src/hooks/useDashboardStats.js
└── src/services/api.js
```
