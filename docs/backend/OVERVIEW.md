# Backend - Laravel 13 Modular Monolith

## نظرة عامة

Laravel 13 API بإطار Modular Monolith مع 31 وحدة نمطية داخل تطبيق واحد.

## Core Layer - طبقة النواة

المكونات المشتركة التي لا تعتمد على أي وحدة:

| المكون | الوصف |
|--------|-------|
| **ValueObjects** | Money, Currency, Rate, Percentage |
| **Enums** | TransactionStatus, WalletType, CurrencyCode |
| **Interfaces** | Contracts للخدمات الأساسية |
| **Traits** | Auditable, HasULID, SoftDeletes |
| **Exceptions** | استثناءات عامة للنواة |
| **Services** | Encryption, AuditLogger, CacheOrchestrator |

## الطبقات في كل طلب API

```
Request
  │
  ▼
Middleware (Auth, Idempotency, RateLimit, FraudCheck)
  │
  ▼
Controller (نحيف - فقط تحويل الطلب)
  │
  ▼
FormRequest (تحقق من صحة البيانات)
  │
  ▼
Action/Service (منطق الأعمال)
  │
  ▼
Event (إطلاق حدث للوحدات الأخرى)
  │
  ▼
Response (استجابة موحدة)
```

## التقنيات

| التقنية | الإصدار | الاستخدام |
|---------|---------|-----------|
| PHP | 8.3+ | لغة التطوير |
| Laravel | 13 | الإطار الرئيسي |
| MySQL | 8.4 | قاعدة البيانات |
| Redis | 7.x | جلسات، طوابير، تخزين مؤقت |
| RabbitMQ | 3.x | Event Bus |
| Pest | 3.x | إطار الاختبارات |

## المصادقة والصلاحيات

- **JWT** قصير العمر (15 دقيقة) + Refresh Token
- **Device Binding** إلزامي للمعاملات المالية
- **RBAC + ABAC** معاً لكل نقطة API
- **OTP** كخيار احتياطي عند فشل المصادقة البيومترية
