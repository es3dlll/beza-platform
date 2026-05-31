# 4. المعمارية الشاملة (Architecture)

## 4.1 الرسم البياني للبنية التحتية (Localhost)

```
                                    ┌──────────────────────┐      ┌──────────────────────┐
                                    │  React SPA (User)    │      │  React Admin         │
                                    │  localhost:5174      │      │  localhost:5173       │
                                    └──────────┬───────────┘      └──────────┬───────────┘
                                               │                            │
                                               └──────────────┬─────────────┘
                                                              │
                                    ┌─────────────────────────┼─────────────────────────┐
                                    │                         │                         │
                                    ▼                         ▼                         ▼
                        ┌───────────────────────┐   ┌───────────────────┐   ┌───────────────────────┐
                        │   Laravel API Server   │   │  Landing Page     │   │   Flutter App         │
                        │   localhost:8000       │   │  localhost:3000   │   │   USB/Emulator        │
                        └───────────┬───────────┘   └───────────────────┘   └───────────────────────┘
                                    │
                                    ├─────────────────────────────────────┐
                                    │                                     │
                                    ▼                                     ▼
                        ┌───────────────────────┐             ┌───────────────────────┐
                        │      MySQL 8.0         │             │       Redis           │
                        │      localhost:3306    │             │   localhost:6379      │
                        └───────────────────────┘             └───────────────────────┘
```

## 4.2 فصل الطبقات (Layers)

### 4.2.1 طبقة العرض (Presentation Layer)
- React SPA (للمستخدمين العاديين)
- React Admin (للمشرفين)
- React Merchant Dashboard (للتجار)
- Flutter App (للمستخدمين والوكلاء)
- Landing Page (Next.js SSG)

### 4.2.2 طبقة التوجيه (Gateway Layer)
- Laravel PHP Artisan Serve (تطوير محلي)
- Rate Limiting عبر Laravel内置 Middleware
- Caching عبر Redis محلي

### 4.2.3 طبقة التطبيق (Application Layer - Laravel)

**وحدات التحكم (Controllers):**

- AuthController
- WalletController
- TransferController
- MerchantController
- AgentController
- DealController (صفقات استثمارية في تمويل الشحنات التجارية)

- AdminController
- PaymentGatewayController
- WebhookController

**الخدمات (Services):**

- WalletService
- ExchangeRateService
- PaymentService (Stripe, PayTabs, local)
- NotificationService (FCM, Email, SMS)
- KYCService (Shufti Pro)
- SettlementService (للتسوية مع الوكلاء والتجار)

**الـ Middlewares:**

- Authenticate
- CheckRole (admin, merchant, agent, user)
- TwoFactor
- DynamicRateLimit
- DeviceCheck

### 4.2.4 طبقة البيانات (Data Layer)
- MySQL (بيانات أساسية: مستخدمين، محافظ، معاملات، استثمارات)
- Redis (جلسات، كاش، قوائم انتظار)
- Elasticsearch (سجلات، بحث متقدم)
- S3 (ملفات: صور المستخدمين، إثباتات، شعارات التجار)

## 4.3 تدفق البيانات (Data Flow) - مثال معاملة تحويل

```
[1] يطلب المستخدم تحويل 100 USD إلى رقم هاتف صديق
[2] تطبيق Flutter → POST /api/v1/transfer (مع Bearer token)
[3] Laravel API Server → يستقبل الطلب على localhost:8000
[4] Laravel Middleware: authenticate, rate limit, 2FA (إذا لزم الأمر)
[5] WalletService::transfer():
    - يتحقق من رصيد المحفظة
    - يبدأ DB transaction
    - يخصم من محفظة المرسل
    - يضيف إلى محفظة المستقبل
    - يسجل المعاملة في جدول transactions
    - يطلق حدث TransactionCompleted
[6] TransactionCompleted event → يستمع له:
    - NotificationService (إرسال إشعار FCM للمستقبل)
    - AuditLogService (تسجيل العملية)
    - WebhookService (إذا كان المستقبل تاجراً)
[7] DB transaction commit
[8] يستجيب API بنجاح مع تفاصيل المعاملة
[9] يتلقى التطبيق الاستجابة ويعرضها للمستخدم
```

## 4.4 أنظمة التكامل (Integrations)

| النظام | الغرض | طريقة التكامل |
|--------|-------|---------------|
| Stripe | مدفوعات البطاقات الدولية | API + Webhooks |
| PayTabs | مدفوعات المنطقة العربية | API |
| Shufti Pro | التحقق من الهوية (KYC) | API |
| Twilio | رسائل SMS للتوثيق | API |
| Firebase Cloud Messaging | إشعارات فورية | API |
| Google Maps | عرض مواقع الوكلاء | JavaScript API |
| OpenExchangeRates | أسعار الصرف | API (كل ساعة) |

| البنوك المحلية | التحويلات البنكية | ملفات CSV/API (حسب البنك) |
