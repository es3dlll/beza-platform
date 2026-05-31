# Shared Standards — المعايير المشتركة

> **الهدف:** توثيق المعايير الموحدة التي تطبق عبر كل أجزاء المنصة  
> **الجمهور المستهدف:** جميع الفرق التقنية — مطورو Backend/Frontend/Mobile  
> **العلاقة:** هذه المعايير هي المرجع لكل الأقسام الأخرى — أي كود جديد يجب أن يتبعها

---

## الأقسام

| القسم | المحتوى | الملفات |
|-------|---------|---------|
| [🔐 الأمان](security/README.md) | المصادقة، التفويض، التشفير | `01-authentication.md`, `02-authorization.md`, `03-encryption.md` |
| [📋 الامتثال](compliance/README.md) | AML، KYC، الشريعة الإسلامية | `01-aml.md`, `02-kyc.md`, `03-sharia.md` |
| [📊 حوكمة البيانات](data-governance/README.md) | تصنيف البيانات، الاحتفاظ، الملكية | `01-data-classification.md`, `02-data-retention.md`, `03-data-ownership.md` |
| [🎨 نظام التصميم](design-system/README.md) | العلامة التجارية، المكونات، الحركة | `01-brand.md`, `02-components.md`, `03-motion.md` |
| [🔔 الإشعارات](notifications/README.md) | Push، SMS، Email | `01-push.md`, `02-sms.md`, `03-email.md` |
| [📊 المراقبة](observability/README.md) | تسجيل، مقاييس، تنبيهات | `01-logging.md`, `02-metrics.md`, `03-alerting.md`, `observability.md`, `kpi-catalog.md`, `command-center.md` |
| [🧪 الاختبارات](testing/README.md) | أنماط الاختبار، بيانات الاختبار | `01-testing-patterns.md`, `02-test-data-factories.md` |

---

## مبدأ Single Source of Truth

كل موضوع في مكان واحد فقط. أي قسم آخر يشير إلى هذه المعايير بدلاً من تكرارها.
مثال: `docs/compliance/OVERVIEW.md` يقدم نظرة عامة، لكن التفاصيل موجودة هنا في `shared/compliance/01-aml.md`.

```
طلب ميزة جديدة
    │
    ├── تحقق من المعايير المشتركة للأمان ← shared/security/
    ├── تحقق من المعايير المشتركة للامتثال ← shared/compliance/
    ├── تحقق من نظام التصميم ← shared/design-system/
    ├── أضف اختبارات ← shared/testing/
    └── أضف مراقبة ← shared/observability/
```

---

## العلاقة مع الأقسام الأخرى

- **العمارة** (`../architecture/`): المبادئ المعمارية تستند لهذه المعايير
- **الأمان** (`../security/`): نظرة عامة ← تفاصيل في shared/security/
- **الامتثال** (`../compliance/`): نظرة عامة ← تفاصيل في shared/compliance/
- **الخلفي** (`../backend/`): يطبق معايير shared/security/ و shared/testing/
- **الأمامي** (`../frontend/`): يطبق معايير shared/design-system/ و shared/notifications/
