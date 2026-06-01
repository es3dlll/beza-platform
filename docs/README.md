# Beza Platform — فهرس التوثيق المركزي

> **منصة بيزا:** نظام تشغيل مالي رقمي وطني للجمهورية العربية السورية
> **الهيكل:** Modular Monolith (Laravel ^13.8) + React 19.2.4 Admin + Flutter 3.41+ Mobile
> **الهدف:** تمكين 22 مليون مقيم و6 ملايين مغترب من خدمات مالية آمنة، شفافة، ومتوافقة

---

## فهرس الأقسام

| #  | القسم | المحتوى |
|----|-------|---------|
| 01 | [دليل عام](01-guide/) | نظرة عامة على المنصة، مسرد مصطلحات |
| 02 | [العمارة](02-architecture/) | مبادئ، 31 وحدة نمطية، تواصل، جودة، ADRs |
| 03 | [الخلفي](03-backend/) | Laravel API، هيكل الوحدات، Core Layer |
| 04 | [الأمامي](04-frontend/) | React 19 Admin + Flutter 3.41 Mobile + Design System |
| 05 | [API](05-api/) | مواصفات OpenAPI 3.1، مصفوفة النقاط، Postman |
| 06 | [الامتثال](06-compliance/) | سياسات أمان، AML/KYC، حماية بيانات، CBS |
| 07 | [البنية التحتية](07-infrastructure/) | Docker، نشر، إصدارات، نسخ احتياطي |
| 08 | [العمليات](08-operations/) | Releases، Runbooks، مراقبة، إشعارات |
| 09 | [الأمن](09-security/) | تقارير اختراق، تدقيق أمني |
| 90 | [الأرشيف](90-archive/) | وثائق تاريخية، تحليلات سابقة، خطط بناء |

---

## 01-guide — دليل عام

| الملف | الوصف |
|-------|-------|
| [`overview.md`](01-guide/overview.md) | نظرة عامة على المنصة — الرؤية، المكدس التقني، الجمهور |
| [`glossary.md`](01-guide/glossary.md) | مسرد المصطلحات المالية والتقنية |

## 02-architecture — العمارة

| الملف | الوصف |
|-------|-------|
| [`PRINCIPLES.md`](02-architecture/PRINCIPLES.md) | المبادئ المعمارية الأساسية: 7 قواعد غير قابلة للتفاوض |
| [`MODULES.md`](02-architecture/MODULES.md) | دليل الـ 31 وحدة نمطية مع التبعيات والمسؤوليات |
| [`COMMUNICATION.md`](02-architecture/COMMUNICATION.md) | قواعد التواصل بين الوحدات: Event Bus |
| [`QUALITY.md`](02-architecture/QUALITY.md) | معايير الجودة: اختبارات، عتبات التغطية، أدوات التحليل |
| [`QUICKSTART.md`](02-architecture/QUICKSTART.md) | دليل البدء السريع: تشغيل البيئة محلياً |
| [`ADRs/`](02-architecture/ADRs/) | قرارات معمارية موثقة (Architecture Decision Records) |
| [`testing/`](02-architecture/testing/) | أنماط الاختبار وبيانات الاختبار (Shared) |
| [`prd/`](02-architecture/prd/) | متطلبات المنتج (PRD v1.1.0) |

## 03-backend — الخلفي

| الملف | الوصف |
|-------|-------|
| [`OVERVIEW.md`](03-backend/OVERVIEW.md) | Laravel 13 Modular Monolith: Core Layer، Middleware Stack |
| [`MODULE_STRUCTURE.md`](03-backend/MODULE_STRUCTURE.md) | الهيكل الإلزامي لكل وحدة (16 مجلداً) مع Core Layer و Events |

## 04-frontend — الأمامي

| الملف | الوصف |
|-------|-------|
| [`ADMIN.md`](04-frontend/ADMIN.md) | لوحة تحكم الإدارة React 19: Feature-Sliced Design |
| [`MOBILE.md`](04-frontend/MOBILE.md) | تطبيق المحفظة Flutter 3.41: Clean Architecture، Offline-First |
| [`design-system/`](04-frontend/design-system/) | نظام التصميم الموحد: العلامة التجارية، المكونات، الحركة |

## 05-api — واجهات البرمجة

| الملف | الوصف |
|-------|-------|
| [`openapi-v1.yaml`](05-api/openapi-v1.yaml) | مواصفات OpenAPI 3.1 (جميع نقاط API) |
| [`endpoint-matrix.md`](05-api/endpoint-matrix.md) | مصفوفة نقاط API حسب الوحدة والطريقة والصلاحيات |
| [`postman-collection/`](05-api/postman-collection/) | مجموعة Postman للاختبارات اليدوية |

## 06-compliance — الامتثال

| القسم | المحتوى |
|-------|---------|
| [`security-policies/`](06-compliance/security-policies/) | سياسات الأمان: المصادقة (JWT)، التفويض (RBAC+ABAC)، التشفير (AES-256) |
| [`aml-kyc/`](06-compliance/aml-kyc/) | مكافحة غسل الأموال (AML)، اعرف عميلك (KYC)، الشريعة الإسلامية |
| [`data-protection/`](06-compliance/data-protection/) | حماية البيانات: التصنيف، الاحتفاظ، الملكية |
| [`cbs-regulations/`](06-compliance/cbs-regulations/) | متطلبات المصرف المركزي السوري |
| [`kyc-tiers.md`](06-compliance/kyc-tiers.md) | مستويات التحقق T1-T3: الحدود، المتطلبات، السلوك التلقائي |

## 07-infrastructure — البنية التحتية

| الملف | الوصف |
|-------|-------|
| [`CURRENT_VERSIONS.md`](07-infrastructure/CURRENT_VERSIONS.md) | إصدارات التقنيات الحالية (PHP، Laravel، Flutter، Node) |
| [`DEPLOYMENT.md`](07-infrastructure/DEPLOYMENT.md) | Docker Compose، بيئات النشر، النسخ الاحتياطي |
| [`UPGRADE_LOG_20260531.md`](07-infrastructure/UPGRADE_LOG_20260531.md) | سجل تحديث الحزم والتبعيات |

## 08-operations — العمليات

| الملف | الوصف |
|-------|-------|
| [`INDEX.md`](08-operations/INDEX.md) | فهرس العمليات: الإصدارات، Runbooks |
| [`QUICK_REFERENCE_OPERATORS.md`](08-operations/QUICK_REFERENCE_OPERATORS.md) | دليل سريع للمشغلين وأوامر الصيانة |
| [`BETA_RETROSPECTIVE_v1.1.0.md`](08-operations/BETA_RETROSPECTIVE_v1.1.0.md) | ملخص النسخة التجريبية والدروس المستفادة |
| [`RUNBOOK.md`](08-operations/RUNBOOK.md) | دليل استخدام Runbooks |
| [`FEEDBACK_FORM.md`](08-operations/FEEDBACK_FORM.md) | نموذج التغذية الراجعة |
| [`releases/`](08-operations/releases/) | الإصدارات: Changelog، خطط النشر، قوائم التدقيق |
| [`runbooks/`](08-operations/runbooks/) | أدلة الطوارئ (5 سيناريوهات) |
| [`runbooks/user-journeys/`](08-operations/runbooks/user-journeys/) | رحلات المستخدم (9 مسارات) |
| [`notifications/`](08-operations/notifications/) | الإشعارات: Push، SMS، Email |
| [`observability/`](08-operations/observability/) | المراقبة: تسجيل، مقاييس، تنبيهات، KPIs |

## 09-security — الأمن

| الملف | الوصف |
|-------|-------|
| [`audits/SEC-001/`](09-security/audits/SEC-001/) | تقارير اختراق SEC-001: 4 ثغرات حرجة |

## 90-archive — الأرشيف

| القسم | المحتوى |
|-------|---------|
| [`engineering/`](90-archive/engineering/) | مصفوفات فنية، معايير بناء، قوائم تدقيق |
| [`planning/`](90-archive/planning/) | خارطة الطريق، جلسات التخطيط، استبيانات |
| [`product/`](90-archive/product/) | نطاقات الإصدارات السابقة (v1–v4) |
| [`plans/`](90-archive/plans/) | خطط بناء Beza (v0–v5) |
| [`marketing/`](90-archive/marketing/) | حملة الإطلاق والمواد التسويقية |
| [`tasks/`](90-archive/tasks/) | مهام سابقة: backend، security، qa، devops، ai |
| [`workflows/`](90-archive/workflows/) | سير عمل الوكلاء (4 مراحل) |
| [`analysis/`](90-archive/analysis/) | تحليل شامل للمنصة + مواصفات + أمثلة + مهام تفصيلية |
| [`README.md`](90-archive/README.md) | فهرس الأرشيف |

> الأرشيف يحتوي على وثائق من مراحل التطوير السابقة. يُحتفظ بها كمرجع تاريخي فقط.

---

## خريطة العلاقات بين الوثائق

```
docs/README.md (الفهرس المركزي — SSOT)
│
├── 01-guide/                ← أدلة عامة
├── 02-architecture/         ← مبادئ، وحدات، تواصل، جودة
│   ├── ADRs/                ← قرارات معمارية
│   ├── testing/             ← أنماط الاختبار
│   └── prd/                 ← متطلبات المنتج
│
├── 03-backend/              ← Laravel 13 + هيكل الوحدات
├── 04-frontend/             ← React 19 + Flutter 3.41 + Design System
├── 05-api/                  ← OpenAPI + مصفوفة النقاط
│
├── 06-compliance/           ← الامتثال والأمان
│   ├── security-policies/   ← مصادقة، تفويض، تشفير
│   ├── aml-kyc/             ← AML/KYC/Sharia
│   ├── data-protection/     ← حماية البيانات
│   └── cbs-regulations/     ← متطلبات المصرف المركزي
│
├── 07-infrastructure/       ← النشر والبنية التحتية
│
├── 08-operations/           ← العمليات والتشغيل
│   ├── releases/            ← الإصدارات والنشر
│   ├── runbooks/            ← الاستجابة للحوادث
│   ├── notifications/       ← الإشعارات
│   └── observability/       ← المراقبة
│
├── 09-security/             ← تقارير اختراق
│   └── audits/              ← تدقيق أمني
│
├── 90-archive/              ← أرشيف تاريخي
│
├── SOP-workflow.md          ← إجراءات التشغيل الموحدة
└── reports/                 ← تقارير مرحلية
```

---

## مبادئ التوثيق

1. **Arabic-First:** جميع التوثيق يبدأ بالعربية مع دعم الإنجليزي
2. **Single Source of Truth:** كل موضوع في مكان واحد، والباقي يشير إليه
3. **Layered Detail:** الفهرس → نظرة عامة → تفاصيل لكل قسم
4. **Cross-References:** روابط واضحة بين الوثائق المترابطة
5. **Versioned:** كل وثيقة رئيسية تحمل رقم إصدار وحالة (مسودة/معتمدة)

---

**آخر تحديث:** 2026-05-31 | **الفرع:** `feature/phase5-deploy-wap-admin`
