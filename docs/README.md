# Beza Platform — فهرس التوثيق المركزي

> **منصة بيزا:** نظام تشغيل مالي رقمي وطني للجمهورية العربية السورية  
> **الهيكل:** Modular Monolith (Laravel 13) + React 19 Admin + Flutter 3.29 Mobile  
> **الهدف:** تمكين 22 مليون مقيم و6 ملايين مغترب من خدمات مالية آمنة، شفافة، ومتوافقة

---

## فهرس الأقسام

| #  | القسم | المحتوى |
|----|-------|---------|
| 01 | [🔷 العمارة](#-architecture-العمارة) | مبادئ، وحدات، تواصل، جودة، ADRs |
| 02 | [🖥️ الخلفي](#-backend-الخلفي) | Laravel 13 API، هيكل الوحدات، Core Layer |
| 03 | [🎨 الأمامي](#-frontend-الأمامي) | React 19 Admin + Flutter 3.29 Mobile + Design System |
| 04 | [📋 الامتثال](#-compliance-الامتثال) | سياسات الأمان، AML/KYC، حماية البيانات، CBS |
| 05 | [🔗 API](#-api-wallet--cfe) | مواصفات OpenAPI، مصفوفة النقاط، Postman |
| 06 | [⚙️ البنية التحتية](#-infrastructure-البنية-التحتية) | Docker، نشر، إصدارات، نسخ احتياطي |
| 07 | [📦 المنتج والتخطيط](#-product--planning-المنتج-والتخطيط) | PRD، خارطة طريق، جلسات تخطيط |
| 08 | [🔄 العمليات](#-operations-العمليات) | Runbooks، إصدارات، مراقبة، إشعارات |
| 09 | [🗂️ الأرشيف](#-archive-الأرشيف) | وثائق تاريخية، خطط بناء سابقة |

---

## 🔷 Architecture — العمارة

| الملف | الوصف |
|-------|-------|
| [`PRINCIPLES.md`](architecture/PRINCIPLES.md) | المبادئ المعمارية الأساسية: 7 قواعد غير قابلة للتفاوض |
| [`MODULES.md`](architecture/MODULES.md) | دليل الـ 31 وحدة نمطية مع التبعيات والمسؤوليات |
| [`COMMUNICATION.md`](architecture/COMMUNICATION.md) | قواعد التواصل بين الوحدات: Event Bus |
| [`QUALITY.md`](architecture/QUALITY.md) | معايير الجودة: اختبارات، عتبات التغطية، أدوات التحليل |
| [`QUICKSTART.md`](architecture/QUICKSTART.md) | دليل البدء السريع: تشغيل البيئة محلياً |
| [`ADRs/`](architecture/ADRs/) | قرارات معمارية موثقة (Architecture Decision Records) |
| [`testing/`](architecture/testing/) | أنماط الاختبار وبيانات الاختبار (Shared) |
| [`prd/`](architecture/prd/) | متطلبات المنتج (PRD v1.1.0) |

---

## 🖥️ Backend — الخلفي

| الملف | الوصف |
|-------|-------|
| [`OVERVIEW.md`](backend/OVERVIEW.md) | Laravel 13 Modular Monolith: Core Layer، Middleware Stack |
| [`MODULE_STRUCTURE.md`](backend/MODULE_STRUCTURE.md) | الهيكل الإلزامي لكل وحدة (16 مجلداً) مع Core Layer و Events |
| [`README.md`](backend/README.md) | نظرة عامة على قسم الخلفي |

---

## 🎨 Frontend — الأمامي

| الملف | الوصف |
|-------|-------|
| [`ADMIN.md`](frontend/ADMIN.md) | لوحة تحكم الإدارة React 19: Feature-Sliced Design |
| [`MOBILE.md`](frontend/MOBILE.md) | تطبيق المحفظة Flutter 3.29: Clean Architecture، Offline-First |
| [`design-system/`](frontend/design-system/) | نظام التصميم الموحد: العلامة التجارية، المكونات، الحركة |
| [`README.md`](frontend/README.md) | نظرة عامة على قسم الأمامي |

---

## 📋 Compliance — الامتثال

| القسم | المحتوى |
|-------|---------|
| [`security-policies/`](compliance/security-policies/) | سياسات الأمان: المصادقة (JWT)، التفويض (RBAC+ABAC)، التشفير (AES-256) |
| [`aml-kyc/`](compliance/aml-kyc/) | مكافحة غسل الأموال (AML)، اعرف عميلك (KYC)، الشريعة الإسلامية |
| [`data-protection/`](compliance/data-protection/) | حماية البيانات: التصنيف، الاحتفاظ، الملكية |
| [`cbs-regulations/`](compliance/cbs-regulations/) | متطلبات المصرف المركزي السوري |
| [`kyc-tiers.md`](compliance/kyc-tiers.md) | مستويات التحقق T1-T3: الحدود، المتطلبات، السلوك التلقائي |

---

## 🔗 API — واجهات البرمجة

| الملف | الوصف |
|-------|-------|
| [`openapi-v1.yaml`](api/openapi-v1.yaml) | مواصفات OpenAPI 3.1 (جميع نقاط API) |
| [`endpoint-matrix.md`](api/endpoint-matrix.md) | مصفوفة نقاط API حسب الوحدة والطريقة والصلاحيات |
| [`postman-collection/`](api/postman-collection/) | مجموعة Postman للاختبارات اليدوية |

---

## ⚙️ Infrastructure — البنية التحتية

| الملف | الوصف |
|-------|-------|
| [`CURRENT_VERSIONS.md`](infrastructure/CURRENT_VERSIONS.md) | إصدارات التقنيات الحالية (PHP، Laravel، Flutter، Node) |
| [`DEPLOYMENT.md`](infrastructure/DEPLOYMENT.md) | Docker Compose، بيئات النشر، النسخ الاحتياطي |
| [`UPGRADE_LOG_20260531.md`](infrastructure/UPGRADE_LOG_20260531.md) | سجل تحديث الحزم والتبعيات |
| [`README.md`](infrastructure/README.md) | نظرة عامة على قسم البنية التحتية |

---

## 📦 Product & Planning — المنتج والتخطيط

| الملف | الوصف |
|-------|-------|
| [`PRD_v1.1.0.md`](architecture/prd/PRD_v1.1.0.md) | وثيقة متطلبات المنتج الكاملة v1.1.0 |

> ملفات خارطة الطريق وجلسات التخطيط موجودة في الأرشيف: [`docs/operations/archive/planning/`](operations/archive/planning/)

---

## 🔄 Operations — العمليات

| الملف | الوصف |
|-------|-------|
| [`INDEX.md`](operations/INDEX.md) | فهرس العمليات: الإصدارات، Runbooks |
| [`QUICK_REFERENCE_OPERATORS.md`](operations/QUICK_REFERENCE_OPERATORS.md) | دليل سريع للمشغلين وأوامر الصيانة |
| [`BETA_RETROSPECTIVE_v1.1.0.md`](operations/BETA_RETROSPECTIVE_v1.1.0.md) | ملخص النسخة التجريبية والدروس المستفادة |
| [`README.md`](operations/README.md) | نظرة عامة على قسم العمليات |
| [`notifications/`](operations/notifications/) | الإشعارات: Push، SMS، Email |
| [`observability/`](operations/observability/) | المراقبة: تسجيل، مقاييس، تنبيهات، KPIs |

### Releases — الإصدارات

| الملف | الوصف |
|-------|-------|
| [`CHANGELOG.md`](operations/releases/CHANGELOG.md) | سجل التغييرات الكامل |
| [`DEPLOYMENT_PRODUCTION_v1.0.0.md`](operations/releases/DEPLOYMENT_PRODUCTION_v1.0.0.md) | دليل النشر للإنتاج v1.0.0 |
| [`PUBLIC_RELEASE_PLAN_v1.1.0.md`](operations/releases/PUBLIC_RELEASE_PLAN_v1.1.0.md) | خطة الإطلاق العامة v1.1.0 |
| [`BETA_RELEASE_CHECKLIST_v1.1.0.md`](operations/releases/BETA_RELEASE_CHECKLIST_v1.1.0.md) | قائمة تدقيق الإصدار التجريبي v1.1.0 |

### Runbooks — أدلة الطوارئ

| الملف | الوصف |
|-------|-------|
| [`01-agent-cash.md`](operations/runbooks/01-agent-cash.md) | مشاكل السيولة النقدية للوكلاء |
| [`02-fx-feed.md`](operations/runbooks/02-fx-feed.md) | انقطاع تغذية أسعار الصرف |
| [`03-settlement-failure.md`](operations/runbooks/03-settlement-failure.md) | فشل التسوية اليومية |
| [`04-ledger-incident.md`](operations/runbooks/04-ledger-incident.md) | حوادث دفتر الأستاذ المالي |
| [`05-aml-backlog.md`](operations/runbooks/05-aml-backlog.md) | تراكم طابور فحص AML |

### User Journeys — رحلات المستخدم

| المسار | الوصف |
|--------|-------|
| [`first-time-user.md`](operations/runbooks/user-journeys/first-time-user.md) | رحلة المستخدم الجديد |
| [`kyc.md`](operations/runbooks/user-journeys/kyc.md) | رحلة التوثيق T1/T2/T3 |
| [`first-transfer.md`](operations/runbooks/user-journeys/first-transfer.md) | رحلة أول تحويل مالي |
| [`remittance-receive.md`](operations/runbooks/user-journeys/remittance-receive.md) | رحلة استلام حوالة دولية |
| [`agent-cashout.md`](operations/runbooks/user-journeys/agent-cashout.md) | رحلة السحب النقدي عبر وكيل |
| [`merchant-payment.md`](operations/runbooks/user-journeys/merchant-payment.md) | رحلة الدفع QR |
| [`payroll-employee.md`](operations/runbooks/user-journeys/payroll-employee.md) | رحلة استلام الراتب |
| [`fraud-review.md`](operations/runbooks/user-journeys/fraud-review.md) | رحلة مراجعة الاحتيال |
| [`dispute-resolution.md`](operations/runbooks/user-journeys/dispute-resolution.md) | رحلة حل النزاع |

---

## 🗂️ Archive — الأرشيف

| الملف | الوصف |
|-------|-------|
| [`README.md`](operations/archive/README.md) | فهرس الأرشيف (مرجع تاريخي فقط) |
| [`planning/`](operations/archive/planning/) | خارطة الطريق، جلسات التخطيط، استبيانات |
| [`marketing/`](operations/archive/marketing/) | حملة الإطلاق والمواد التسويقية |
| [`engineering/`](operations/archive/engineering/) | مصفوفات فنية، معايير بناء، قوائم تدقيق |

> الأرشيف يحتوي على وثائق من مراحل التطوير السابقة. يُحتفظ بها كمرجع تاريخي فقط.

---

## 🗺️ خريطة العلاقات بين الوثائق

```
ARCHITECTURE.md (المعمارية الكاملة — المرجع الوحيد)
│
├── docs/architecture/         ← المبادئ والوحدات والتواصل والجودة
│   ├── ADRs/                  ← القرارات المعمارية
│   ├── testing/               ← أنماط الاختبار
│   └── prd/                   ← متطلبات المنتج
│
├── docs/backend/              ← Laravel 13 + هيكل الوحدات
├── docs/frontend/             ← React 19 + Flutter 3.29 + Design System
│
├── docs/compliance/           ← الامتثال والأمان (موحد)
│   ├── security-policies/     ← المصادقة والتفويض والتشفير
│   ├── aml-kyc/               ← AML/KYC/Sharia
│   ├── data-protection/       ← حماية البيانات
│   └── cbs-regulations/       ← متطلبات المصرف المركزي
│
├── docs/api/                  ← مواصفات OpenAPI ومصفوفة النقاط
│
├── docs/infrastructure/       ← النشر والبنية التحتية
│
├── docs/operations/           ← العمليات والتشغيل اليومي
│   ├── releases/              ← الإصدارات والنشر
│   ├── runbooks/              ← الاستجابة للحوادث + رحلات المستخدم
│   ├── notifications/         ← الإشعارات
│   ├── observability/         ← المراقبة والمقاييس
│   └── archive/               ← وثائق تاريخية
```

---

## 📌 مبادئ التوثيق

1. **Arabic-First:** جميع التوثيق يبدأ بالعربية مع دعم الإنجليزي
2. **Single Source of Truth:** كل موضوع في مكان واحد، والباقي يشير إليه
3. **Layered Detail:** الفهرس → نظرة عامة → تفاصيل لكل قسم
4. **Cross-References:** روابط واضحة بين الوثائق المترابطة
5. **Versioned:** كل وثيقة رئيسية تحمل رقم إصدار وحالة (مسودة/معتمدة)
