# Module Structure — هيكل الوحدة النمطية الإلزامي

> **الهدف:** توثيق الهيكل الإلزامي لكل وحدة نمطية في Laravel 13 Modular Monolith  
> **العدد الإجمالي:** 31 وحدة نمطية  
> **المرجع:** [`docs/architecture/MODULES.md`](../architecture/MODULES.md)

---

## الهيكل الإلزامي (16 مجلداً)

كل وحدة نمطية جديدة يجب أن تتبع هذا الهيكل حرفياً:

```
ModuleName/
├── Actions/                        # عمليات أعمال قابلة لإعادة الاستخدام (final classes)
├── Controllers/                    # متحكمات رقيقة تستقبل الطلبات وتعيد الاستجابات
│   └── Api/
│       └── V1/                     # جميع المتحكمات ضمن إصدار API
├── DTOs/                           # كائنات نقل البيانات (Data Transfer Objects)
│   ├── Create{Entity}DTO.php
│   ├── Update{Entity}DTO.php
│   └── {Entity}ResponseDTO.php
├── Events/                         # أحداث Domain Events قابلة للإطلاق
│   ├── {Entity}Created.php
│   └── {Entity}Updated.php
├── Exceptions/                     # استثناءات خاصة بالوحدة
│   ├── {Entity}NotFoundException.php
│   └── {Entity}ValidationException.php
├── Listeners/                      # مستمعو الأحداث (ShouldQueue)
├── Models/                         # نماذج Eloquent (ULID + Timestamps)
├── Policies/                       # سياسات صلاحيات (RBAC + ABAC)
├── Repositories/                   # طبقة الوصول إلى البيانات (Repository Pattern)
│   ├── {Entity}RepositoryInterface.php
│   └── {Entity}Repository.php
├── Rules/                          # قواعد تحقق مخصصة (Custom Validation Rules)
├── Services/                       # خدمات الأعمال (final classes مع DI عبر البناء)
│   └── {Entity}Service.php
├── ValueObjects/                   # كائنات القيمة (immutable)
│   ├── {Entity}Status.php
│   └── {Entity}Type.php
├── Database/                       # قاعدة البيانات
│   ├── Factories/                  # مصانع النماذج للاختبارات
│   ├── Migrations/                 # ملفات الهجرة
│   └── Seeders/                    # بذور البيانات
├── Resources/lang/                 # ترجمات الوحدة
│   ├── ar/                         # العربية (الأساسية)
│   ├── en/                         # الإنجليزية
│   ├── ku/                         # الكردية
│   └── hy/                         # الأرمنية
├── Routes/
│   └── api.php                     # مسارات الوحدة بادئة /v1/{module}
├── Tests/
│   ├── Feature/                    # اختبارات الميزة
│   └── Unit/                       # اختبارات الوحدة
├── Providers/
│   └── {Module}ServiceProvider.php # مسجل الخدمة (يربط في bootstrap/providers.php)
└── README.md                       # توثيق الوحدة: الهدف، التدفقات، الاعتمادات، نقاط API
```

---

## Core Layer (مشترك، لا يعتمد على وحدات)

```
app/Core/
├── ValueObjects/                   # Money, Currency, Rate, Percentage
│   ├── Money.php                   # amount(int), currency, arithmetic, format, JsonSerializable
│   ├── Currency.php                # رمز العملة (SYP, USD, EUR)
│   ├── Rate.php                    # سعر الصرف
│   └── Percentage.php              # النسبة المئوية
├── Enums/                          # TransactionStatus, WalletType, CurrencyCode
├── Interfaces/                     # عقود الخدمات الأساسية
│   ├── FinancialServiceInterface.php
│   ├── AuditLoggerInterface.php
│   └── EncryptionInterface.php
├── Traits/                         # Auditable, HasULID
├── Exceptions/                     # CoreException, FinancialException
└── Services/                       # Encryption, AuditLogger, CacheOrchestrator
```

---

## قواعد التواصل بين الوحدات

```
ممنوع:                    // استدعاء service أو model من وحدة أخرى
  app(OtherModule::class);
  OtherModel::query();

مسموح فقط:
  event(new ModuleEvent(...));      // إطلاق حدث
  // الاستماع عبر ShouldQueue + handle()
```

**الاستثناء الوحيد:** Core/ يمكن استدعاؤه عبر واجهاته الرسمية الموثقة.

---

## أحداث مشتركة (Cross-Cutting Events)

| الحدث | المصدر | المستمعون المحتملون |
|-------|--------|---------------------|
| `UserRegistered` | Identity | Notification, Compliance, Analytics |
| `TransactionInitiated` | CFE | Fraud, Compliance, Analytics |
| `TransactionCompleted` | CFE | Notification, Settlement, Analytics |
| `KycLevelUpgraded` | Compliance | Wallet (رفع الحدود), Notification |
| `AccountFrozen` | Compliance | Notification, Agent, Admin |
| `FraudDetected` | Fraud | Compliance (تجميد), Notification |
| `SettlementCompleted` | Settlement | Notification, Treasury |

---

## الاستجابة الموحدة (ApiResponse)

كل نقطة API ترجع استجابة بهذا الهيكل:

```json
{
  "success": true,
  "message": "تمت المعاملة بنجاح",
  "data": {},
  "errors": null,
  "timestamp": "2026-05-31T10:00:00Z",
  "request_id": "01JXXXXX"
}
```

---

## العلاقة مع المصادر الأخرى

- [`docs/architecture/MODULES.md`](../architecture/MODULES.md): قائمة الوحدات الـ 31 والاعتمادات
- [`docs/architecture/PRINCIPLES.md`](../architecture/PRINCIPLES.md): المبادئ المعمارية الثابتة
- [`docs/compliance/README.md`](../compliance/README.md): متطلبات الامتثال المطبقة في كل وحدة
