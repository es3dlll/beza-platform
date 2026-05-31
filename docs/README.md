# Beza Platform — فهرس التوثيق المركزي

> **منصة بيزا:** نظام تشغيل مالي رقمي وطني للجمهورية العربية السورية  
> **الهيكل:** Modular Monolith (Laravel 13) + React 19 Admin + Flutter 3.29 Mobile  
> **الهدف:** تمكين 22 مليون مقيم و6 ملايين مغترب من خدمات مالية آمنة، شفافة، ومتوافقة

---

## فهرس الأقسام

| #  | القسم | المحتوى |
|----|-------|---------|
| 01 | [🔷 العمارة](#-architecture-العمارة) | مبادئ، وحدات، تواصل، جودة، بدء سريع |
| 02 | [🖥️ الخلفي](#-backend-الخلفي) | Laravel 13 API، Core Layer، Middleware |
| 03 | [🎨 الأمامي](#-frontend-الأمامي) | React 19 Admin + Flutter 3.29 Mobile |
| 04 | [🔒 الأمان](#-security-الأمان) | Zero Trust، JWT، تشفير، تدقيق |
| 05 | [📋 الامتثال](#-compliance-الامتثال) | AML، KYC، متطلبات CBS، شريعة |
| 06 | [⚙️ البنية التحتية](#-infrastructure-البنية-التحتية) | Docker، نشر، إصدارات، نسخ احتياطي |
| 07 | [📦 المنتج](#-product-المنتج) | PRD، خارطة طريق، خطط الإطلاق |
| 08 | [🔄 العمليات](#-operations-العمليات) | Runbooks، إصدارات، دليل المشغلين |
| 09 | [👤 رحلات المستخدم](#-user-journeys-رحلات-المستخدم) | رحلات كاملة: KYC، تحويل، دفع، إلخ |
| 10 | [📐 المعايير المشتركة](#-shared-standards-المعايير-المشتركة) | تصنيف بيانات، تصميم، اختبارات، مراقبة |
| 11 | [📢 التسويق](#-marketing-التسويق) | حملة الإطلاق، الأسئلة الشائعة، العلاقات العامة |
| 12 | [🗂️ الأرشيف](#-archive-الأرشيف) | وثائق تاريخية، خطط بناء سابقة |

---

## 🔷 Architecture — العمارة

| الملف | الوصف |
|-------|-------|
| [`PRINCIPLES.md`](architecture/PRINCIPLES.md) | المبادئ المعمارية الأساسية: 7 قواعد غير قابلة للتفاوض |
| [`MODULES.md`](architecture/MODULES.md) | دليل الـ 31 وحدة نمطية: Core، Financial، Merchant، Cross-Cutting |
| [`COMMUNICATION.md`](architecture/COMMUNICATION.md) | قواعد التواصل بين الوحدات: Event Bus، الممنوعات، الاستثناءات |
| [`QUALITY.md`](architecture/QUALITY.md) | معايير الجودة: اختبارات PestPHP، عتبات التغطية، أدوات التحليل |
| [`QUICKSTART.md`](architecture/QUICKSTART.md) | دليل البدء السريع: تشغيل Backend/Frontend/Mobile محلياً |
| [`README.md`](architecture/README.md) | نظرة عامة على قسم العمارة |

---

## 🖥️ Backend — الخلفي

| الملف | الوصف |
|-------|-------|
| [`OVERVIEW.md`](backend/OVERVIEW.md) | Laravel 13 Modular Monolith: Core Layer، Middleware Stack |
| [`README.md`](backend/README.md) | نظرة عامة + تفاصيل قسم الخلفي |

---

## 🎨 Frontend — الأمامي

| الملف | الوصف |
|-------|-------|
| [`ADMIN.md`](frontend/ADMIN.md) | لوحة تحكم الإدارة React 19: Feature-Sliced Design، الميزات |
| [`MOBILE.md`](frontend/MOBILE.md) | تطبيق المحفظة Flutter 3.29: Clean Architecture، Offline-First |
| [`README.md`](frontend/README.md) | نظرة عامة على قسم الأمامي |

---

## 🔒 Security — الأمان

| الملف | الوصف |
|-------|-------|
| [`OVERVIEW.md`](security/OVERVIEW.md) | نموذج Zero Trust: JWT، Audit Log، Device Binding |
| [`STANDARDS.md`](security/STANDARDS.md) | معايير الأمان التفصيلية: تشفير، مصادقة، تفويض |
| [`README.md`](security/README.md) | نظرة عامة على قسم الأمان |

---

## 📋 Compliance — الامتثال

| الملف | الوصف |
|-------|-------|
| [`OVERVIEW.md`](compliance/OVERVIEW.md) | AML/KYC Framework، متطلبات CBS، مسؤوليات الامتثال |
| [`README.md`](compliance/README.md) | نظرة عامة على قسم الامتثال |

> **للمعايير التفصيلية:** راجع [المعايير المشتركة/الامتثال](shared/compliance/README.md)

---

## ⚙️ Infrastructure — البنية التحتية

| الملف | الوصف |
|-------|-------|
| [`CURRENT_VERSIONS.md`](infrastructure/CURRENT_VERSIONS.md) | إصدارات التقنيات الحالية (PHP، Laravel، Flutter، Node) |
| [`DEPLOYMENT.md`](infrastructure/DEPLOYMENT.md) | Docker Compose، بيئات النشر، النسخ الاحتياطي، المراقبة |
| [`UPGRADE_LOG_20260531.md`](infrastructure/UPGRADE_LOG_20260531.md) | سجل تحديث الحزم والتبعيات |
| [`README.md`](infrastructure/README.md) | نظرة عامة على قسم البنية التحتية |

---

## 📦 Product — المنتج

| الملف | الوصف |
|-------|-------|
| [`PRD_v1.1.0.md`](prd/PRD_v1.1.0.md) | وثيقة متطلبات المنتج الكاملة v1.1.0 |
| [`README.md`](prd/README.md) | نظرة عامة على قسم المنتج |

### التخطيط

| الملف | الوصف |
|-------|-------|
| [`ROADMAP_v1.1.0.md`](planning/ROADMAP_v1.1.0.md) | خارطة طريق الإصدار v1.1.0 |
| [`PLANNING_SESSION_v1.1.0.md`](planning/PLANNING_SESSION_v1.1.0.md) | جلسة التخطيط: الميزات والجدول الزمني |
| [`SURVEY_INPUTS_v1.1.0.md`](planning/SURVEY_INPUTS_v1.1.0.md) | مدخلات الاستبيان وتحليل السوق |
| [`EXECUTIVE_PRESENTATION_v1.1.0.md`](planning/EXECUTIVE_PRESENTATION_v1.1.0.md) | عرض تقديمي تنفيذي v1.1.0 |
| [`README.md`](planning/README.md) | نظرة عامة على قسم التخطيط |

---

## 🔄 Operations — العمليات

| الملف | الوصف |
|-------|-------|
| [`INDEX.md`](operations/INDEX.md) | فهرس العمليات: الإصدارات، Runbooks |
| [`QUICK_REFERENCE_OPERATORS.md`](operations/QUICK_REFERENCE_OPERATORS.md) | دليل سريع للمشغلين وأوامر الصيانة |
| [`BETA_RETROSPECTIVE_v1.1.0.md`](operations/BETA_RETROSPECTIVE_v1.1.0.md) | ملخص النسخة التجريبية والدروس المستفادة |
| [`README.md`](operations/README.md) | نظرة عامة على قسم العمليات |

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

---

## 👤 User Journeys — رحلات المستخدم

| المسار | الوصف |
|--------|-------|
| [`first-time-user.md`](journeys/first-time-user.md) | رحلة المستخدم الجديد: تحميل → تسجيل → أول معاملة |
| [`kyc.md`](journeys/kyc.md) | رحلة التوثيق: المستويات T1/T2/T3 |
| [`first-transfer.md`](journeys/first-transfer.md) | رحلة أول تحويل مالي |
| [`remittance-receive.md`](journeys/remittance-receive.md) | رحلة استلام حوالة دولية |
| [`agent-cashout.md`](journeys/agent-cashout.md) | رحلة السحب النقدي عبر وكيل |
| [`merchant-payment.md`](journeys/merchant-payment.md) | رحلة الدفع لدى التاجر (QR) |
| [`payroll-employee.md`](journeys/payroll-employee.md) | رحلة استلام الراتب عبر المنصة |
| [`fraud-review.md`](journeys/fraud-review.md) | رحلة مراجعة حالة احتيال |
| [`dispute-resolution.md`](journeys/dispute-resolution.md) | رحلة حل النزاع المالي |

---

## 📐 Shared Standards — المعايير المشتركة

| القسم | المحتوى | الملفات |
|-------|---------|---------|
| [🔐 الأمان](shared/security/README.md) | مصادقة، تفويض، تشفير | `01-authentication.md`, `02-authorization.md`, `03-encryption.md` |
| [📋 الامتثال](shared/compliance/README.md) | AML، KYC، شريعة | `01-aml.md`, `02-kyc.md`, `03-sharia.md` |
| [📊 حوكمة البيانات](shared/data-governance/README.md) | تصنيف، احتفاظ، ملكية | `01-data-classification.md`, `02-data-retention.md`, `03-data-ownership.md` |
| [🎨 نظام التصميم](shared/design-system/README.md) | العلامة التجارية، المكونات، الحركة | `01-brand.md`, `02-components.md`, `03-motion.md` |
| [🔔 الإشعارات](shared/notifications/README.md) | Push، SMS، Email | `01-push.md`, `02-sms.md`, `03-email.md` |
| [📊 المراقبة](shared/observability/README.md) | تسجيل، مقاييس، تنبيهات | `01-logging.md`, `02-metrics.md`, `03-alerting.md` |
| [🧪 الاختبارات](shared/testing/README.md) | أنماط الاختبار، بيانات الاختبار | `01-testing-patterns.md`, `02-test-data-factories.md` |

---

## 📢 Marketing — التسويق

| الملف | الوصف |
|-------|-------|
| [`press_release_ar.md`](marketing/LAUNCH_CAMPAIGN_v1.1.0/press_release_ar.md) | البيان الصحفي الرسمي للإطلاق |
| [`faq_public.md`](marketing/LAUNCH_CAMPAIGN_v1.1.0/faq_public.md) | الأسئلة الشائعة للجمهور |
| [`social_media_posts.md`](marketing/LAUNCH_CAMPAIGN_v1.1.0/social_media_posts.md) | منشورات وسائل التواصل الاجتماعي |
| [`onboarding_guide.md`](marketing/LAUNCH_CAMPAIGN_v1.1.0/onboarding_guide.md) | دليل التعريف والتسجيل للمستخدمين |
| [`security_assurance.md`](marketing/LAUNCH_CAMPAIGN_v1.1.0/security_assurance.md) | ضمانات الأمان للجمهور |

---

## 🗂️ Archive — الأرشيف

| الملف | الوصف |
|-------|-------|
| [`README.md`](operations/archive/README.md) | فهرس الأرشيف: وثائق تاريخية، خطط بناء، سير عمل |

> الأرشيف يحتوي على وثائق من مراحل التطوير السابقة. يُحتفظ بها كمرجع تاريخي فقط.

---

## 🗺️ خريطة العلاقات بين الوثائق

```
ARCHITECTURE.md (المعمارية الكاملة)
│
├── docs/architecture/         ← المبادئ والوحدات والتواصل
│   └── docs/QUALITY.md       ← معايير الجودة
│
├── docs/backend/              ← تفاصيل Laravel 13
├── docs/frontend/             ← React 19 + Flutter 3.29
│
├── docs/security/             ← الأمان (يرتبط بـ shared/security/)
├── docs/compliance/           ← الامتثال (يرتبط بـ shared/compliance/)
│
├── docs/infrastructure/       ← النشر والبنية التحتية
│
├── docs/prd/                  ← PRD (القاعدة لكل القرارات)
├── docs/planning/             ← خطط الطريق والتسويق
│
├── docs/operations/           ← العمليات والتشغيل اليومي
│   ├── releases/              ← الإصدارات والنشر
│   ├── runbooks/              ← الاستجابة للحوادث
│   └── archive/               ← وثائق تاريخية
│
├── docs/journeys/             ← رحلات المستخدم
├── docs/marketing/            ← التسويق والإطلاق
│
└── docs/shared/               ← معايير مشتركة لكل الأقسام
    ├── compliance/
    ├── data-governance/
    ├── design-system/
    ├── notifications/
    ├── observability/
    ├── security/
    └── testing/
```

---

## 📌 مبادئ التوثيق

1. **Arabic-First:** جميع التوثيق يبدأ بالعربية مع دعم الإنجليزي
2. **Single Source of Truth:** كل موضوع في مكان واحد، والباقي يشير إليه
3. **Layered Detail:** الفهرس → نظرة عامة → تفاصيل لكل قسم
4. **Cross-References:** روابط واضحة بين الوثائق المترابطة
5. **Versioned:** كل وثيقة رئيسية تحمل رقم إصدار وحالة (مسودة/معتمدة)
