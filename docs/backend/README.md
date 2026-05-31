# Backend — الخلفي (Laravel 13)

> **الهدف:** توثيق هيكل وتقنيات الخادم الخلفي لمنصة بيزا  
> **الجمهور المستهدف:** مطورو Laravel، مهندسو DevOps، معماريو النظام  
> **العلاقة:** هذا القسم يطبق [المبادئ المعمارية](../architecture/PRINCIPLES.md) في كود Laravel

---

## الملفات

| الملف | الوصف |
|-------|-------|
| [`OVERVIEW.md`](OVERVIEW.md) | نظرة عامة: Core Layer، طبقات الطلب، التقنيات، المصادقة |

---

## التقنيات

| التقنية | الإصدار | الاستخدام |
|---------|---------|-----------|
| PHP | 8.3+ | لغة التطوير |
| Laravel | 13 | الإطار الرئيسي (Modular Monolith) |
| MySQL | 8.4 | قاعدة البيانات الأساسية |
| Redis | 7.x | جلسات، طوابير، تخزين مؤقت |
| RabbitMQ | 3.x | Event Bus للتواصل بين الوحدات |

---

## الهيكل الرئيسي

```
backend/
├── app/Core/                    # نواة مشتركة (مستقلة عن الوحدات)
│   ├── ValueObjects/            # Money, Currency, Rate
│   ├── Enums/                   # TransactionStatus, WalletType
│   ├── Interfaces/              # عقود الخدمات الأساسية
│   ├── Traits/                  # Auditable, HasULID
│   ├── Exceptions/              # استثناءات عامة
│   └── Services/                # Encryption, AuditLogger
├── app/Modules/                 # 31 وحدة نمطية (انظر MODULES.md)
│   ├── Identity/                # المستخدمين والمصادقة
│   ├── Wallet/                  # المحافظ المالية
│   ├── Ledger/                  # دفتر الأستاذ
│   └── ...                      # باقي الوحدات
├── app/Http/Middleware/         # Auth, Idempotency, RateLimit
└── routes/
    ├── api.php                  # نقطة دخول API
    └── modules/                 # مسارات مجزأة لكل وحدة
```

---

## العلاقة مع الأقسام الأخرى

- **العمارة** (`../architecture/`): الأسس المعمارية التي يبني عليها الـ Backend
- **الأمان والامتثال** (`../compliance/security-policies/`): تنفيذ المصادقة والتفويض
- **الاختبارات** (`../architecture/testing/`): أنماط اختبار الـ Backend
- **هيكل الوحدات** (`MODULE_STRUCTURE.md`): المجلدات الـ 16 الإلزامية لكل وحدة
