# 02 - مكان النزاعات في الأرشيتيكشر

```
┌──────────────────────────────────────────────────────────────┐
│  User (Flutter)            Admin (React)                      │
│  [SubmitDispute]           [DisputeList]                      │
└──────────┬──────────────────────┬────────────────────────────┘
           │ POST /support/disputes│ GET /admin/disputes
           ▼                      ▼
┌──────────────────────────────────────────────────────────────┐
│                   Laravel Router                              │
│  Route::post('/support/disputes', [DisputeController])        │
│  Route::get('/admin/disputes', [AdminDisputeController])      │
│  Route::post('/admin/disputes/{id}/resolve', ...)             │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    DisputeService                              │
│  create() → إنشاء نزاع + رفع أدلة                             │
│  getPending() → قائمة النزاعات المفتوحة                       │
│  getDetail() → تفاصيل النزاع + سجل المحادثة                   │
│  resolve() → قرار المشرف + تحديث الرصيد                      │
│  autoClose() → إغلاق النزاعات المنتهية                        │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
              ┌──────────────────────────┐
              │  MySQL                   │
              │  disputes                │
              │  dispute_evidence        │
              │  transactions            │
              └──────────────────────────┘
```

## ملفات المشروع

```
backend-laravel/
├── app/Http/Controllers/Api/DisputeController.php
├── app/Http/Controllers/Api/Admin/AdminDisputeController.php
├── app/Http/Requests/DisputeRequest.php
├── app/Http/Requests/Admin/ResolveDisputeRequest.php
├── app/Http/Resources/DisputeResource.php
├── app/Services/DisputeService.php
├── app/Services/DisputeResolutionService.php
├── app/Models/Dispute.php
├── app/Models/DisputeEvidence.php
├── app/Events/DisputeResolved.php
├── app/Events/DisputeOpened.php
└── app/Listeners/SendDisputeNotification.php
```
