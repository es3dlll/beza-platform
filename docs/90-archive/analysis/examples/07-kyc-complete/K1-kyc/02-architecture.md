# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [KycScreen] → [KycRepository] → [HTTP Request]                  │
└────────────────────────────────┬─────────────────────────────────┘
                                  │ POST /api/v1/kyc/submit
                                  │ GET  /api/v1/kyc/status
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/kyc/submit', [KycController::class, 'submit'])    │
│  Route::get('/kyc/status', [KycController::class, 'status'])     │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                               │
│  ┌──────────┐  ┌──────────┐  ┌───────────────────────────────┐  │
│  │ auth:api │  │ throttle │  │ verified (phone)            │  │
│  └──────────┘  └──────────┘  └───────────────────────────────┘  │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    KycController                                   │
│  submit:                                                          │
│  1. Validate uploaded files                                       │
│  2. Call KycService::submit()                                     │
│  3. Return status                                                 │
│                                                                   │
│  status:                                                          │
│  1. Return current kyc_status for user                            │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    KycService / VerificationService                │
│  Submit:                                                          │
│  1. Validate file types (ID/Passport/Driver License)              │
│  2. Upload to storage (S3/Local)                                  │
│  3. Auto-verify image resolution                                  │
│  4. Create kyc_documents records                                  │
│  5. Update user kyc_status = pending                              │
│  6. Notify admin for manual review                                │
│                                                                   │
│  Review (Admin):                                                  │
│  1. Admin views documents                                         │
│  2. Admin approves or rejects                                     │
│  3. Update user kyc_status                                        │
│  4. Notify user of result                                         │
└────────────────────────────────┬─────────────────────────────────┘
                                  │
                     ┌────────────┼────────────┐
                     ▼            ▼            ▼
           ┌────────────┐ ┌────────────┐ ┌────────────┐
           │ MySQL       │ │ Storage    │ │ Queue      │
           │ users       │ │ S3/Local   │ │ (Events)   │
           │ kyc_docs    │ │            │ │            │
           │ kyc_reviews │ │            │ │            │
           └────────────┘ └────────────┘ └──────┬─────┘
                                                │
                                       ┌────────┴────────┐
                                       ▼                 ▼
                                ┌────────────┐   ┌────────────┐
                                │ Listener    │   │ Notification│
                                │ SendKycNotif│   │ FCM/Email  │
                                └────────────┘   └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/KycController.php
├── app/Http/Requests/KycSubmitRequest.php
├── app/Http/Requests/KycReviewRequest.php (Admin)
├── app/Services/KycService.php
├── app/Services/VerificationService.php
├── app/Models/KycDocument.php
├── app/Models/KycReview.php
├── app/Events/KycUpdated.php
├── app/Listeners/SendKycNotification.php
├── database/migrations/2024_01_01_000030_create_kyc_documents_table.php
├── database/migrations/2024_01_01_000031_create_kyc_reviews_table.php
└── tests/Feature/KycTest.php
```
