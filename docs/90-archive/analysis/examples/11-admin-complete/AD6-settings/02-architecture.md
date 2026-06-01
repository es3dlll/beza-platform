# 02 - مكان الإعدادات في الأرشيتيكشر

```
┌──────────────────────────────────────────────────────────────┐
│                    React Admin SPA                             │
│  [SettingsPage] → [SettingsRepository] → [HTTP Requests]      │
└────────────────────────┬─────────────────────────────────────┘
                         │ GET/PUT /api/v1/admin/settings
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                   Laravel Router                              │
│  /admin/settings          → SettingsController@index           │
│  /admin/settings          → SettingsController@update          │
│  /admin/settings/fees     → SettingsController@fees           │
│  /admin/settings/limits   → SettingsController@limits         │
│  /admin/settings/exchange → SettingsController@exchangeRate   │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    SettingsController                          │
│  index() → جميع الإعدادات من DB                               │
│  update() → تحديث إعداد عام                                   │
│  fees() → تحديث رسوم المعاملات                                │
│  limits() → تحديث الحدود                                      │
│  exchangeRate() → تحديث سعر الصرف                             │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    SettingsService                             │
│  getAll() → قراءة من DB + دمج مع config/beza.php              │
│  set() → تحديث في DB + مسح Cache                             │
│  getFee() → قراءة رسوم معاملة معينة                           │
│  getLimit() → قراءة حد معين                                   │
│  getExchangeRate() → سعر الصرف الحالي                         │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
              ┌──────────────────────────┐
              │  MySQL (settings table)   │
              │  Redis (setting cache)    │
              │  config/beza.php (ثابت)   │
              └──────────────────────────┘
```

## ملفات المشروع

```
backend-laravel/
├── app/Http/Controllers/Api/Admin/SettingsController.php
├── app/Http/Requests/Admin/SettingsRequest.php
├── app/Http/Requests/Admin/FeeSettingsRequest.php
├── app/Http/Requests/Admin/LimitSettingsRequest.php
├── app/Http/Requests/Admin/ExchangeRateRequest.php
├── app/Http/Resources/Admin/SettingsResource.php
├── app/Services/Admin/SettingsService.php
├── app/Models/Admin/Setting.php
├── app/Events/Admin/SettingsUpdated.php
├── config/beza.php
└── app/Http/Middleware/CheckMaintenanceMode.php
```
