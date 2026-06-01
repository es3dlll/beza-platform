# 02 - مكان العملية في الأرشيتيكشر

```
┌──────────────────────────────────────────────────────────────┐
│                    React Admin SPA                             │
│  [MerchantApplications] → [ApprovalRepository]                │
└────────────────────────┬─────────────────────────────────────┘
                         │ GET/POST /api/v1/admin/merchants/*
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                   Laravel Router                              │
│  Route::get('/admin/merchants/applications', ...)             │
│  Route::post('/admin/merchants/{id}/approve', ...)            │
│  Route::post('/admin/merchants/{id}/reject', ...)             │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    ApprovalController                          │
│  index() → عرض الطلبات                                        │
│  show() → عرض تفاصيل الطلب + مستندات                           │
│  approve() → موافقة                                            │
│  reject() → رفض مع سبب                                         │
│  requestDocuments() → طلب مستندات إضافية                       │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    MerchantApprovalService                     │
│  1. التحقق من صحة المستندات                                    │
│  2. تحديث merchant.status                                      │
│  3. تحديث user.is_merchant = true                              │
│  4. dispatch(MerchantApproved) Event                           │
│  5. إرسال إشعار للمستخدم                                       │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
              ┌──────────────────────────┐
              │  MySQL                   │
              │  merchants               │
              │  merchant_documents      │
              │  users                   │
              └──────────────────────────┘
```

## ملفات المشروع

```
backend-laravel/
├── app/Http/Controllers/Api/Admin/MerchantApprovalController.php
├── app/Http/Controllers/Api/Admin/AgentApprovalController.php
├── app/Http/Requests/Admin/RejectRequest.php
├── app/Http/Resources/Admin/MerchantApplicationResource.php
├── app/Services/Admin/MerchantApprovalService.php
├── app/Services/Admin/AgentApprovalService.php
├── app/Events/Admin/MerchantApproved.php
├── app/Events/Admin/MerchantRejected.php
├── app/Events/Admin/AgentApproved.php
├── app/Events/Admin/AgentRejected.php
└── app/Listeners/Admin/SendApprovalNotification.php
```
