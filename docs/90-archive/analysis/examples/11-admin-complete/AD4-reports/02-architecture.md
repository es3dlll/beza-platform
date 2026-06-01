# 02 - مكان التقارير في الأرشيتيكشر

```
┌──────────────────────────────────────────────────────────────┐
│                    React Admin SPA                             │
│  [DailyReport] → [ReportsRepository] → [HTTP Requests]        │
└────────────────────────┬─────────────────────────────────────┘
                         │ GET /api/v1/admin/reports/*
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                   Laravel Router                              │
│  /admin/reports/daily                                         │
│  /admin/reports/monthly                                       │
│  /admin/reports/financial                                     │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    ReportController                            │
│  daily() → DailyReportService::generate()                     │
│  monthly() → MonthlyReportService::generate()                 │
│  financial() → FinancialReportService::generate()             │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    Report Services                             │
│  DailyReportService:                                          │
│    - إجمالي المعاملات = Transaction::today()->sum(amount)    │
│    - الإيرادات = Transaction::today()->fees()->sum(amount)   │
│    - مستخدمون جدد = User::today()->count()                   │
│    - توزيع المعاملات حسب النوع                               │
│                                                               │
│  MonthlyReportService:                                        │
│    - نفس اليومي + مقارنة بالشهر السابق                        │
│    - MAU = distinct users هذا الشهر                           │
│    - GMV = إجمالي قيمة المعاملات                              │
│    - Cohort analysis                                          │
│                                                               │
│  FinancialReportService:                                      │
│    - P&L: إيرادات - مصاريف                                    │
│    - هامش الربف = (إيرادات / حجم معاملات) × 100              │
│    - تحليل الرسوم حسب النوع                                   │
│    - تكاليف التشغيل (Stripe, Twilio, SMS)                     │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
                    ┌──────────┐
                    │  MySQL   │
                    └──────────┘
```

## ملفات المشروع

```
backend-laravel/
├── app/Http/Controllers/Api/Admin/ReportController.php
├── app/Services/Admin/Reports/DailyReportService.php
├── app/Services/Admin/Reports/MonthlyReportService.php
├── app/Services/Admin/Reports/FinancialReportService.php
├── app/Exports/Admin/ReportExport.php  (Excel/CSV)
├── app/Console/Commands/GenerateDailyReport.php
├── app/Http/Resources/Admin/DailyReportResource.php
└── app/Http/Resources/Admin/MonthlyReportResource.php
```
