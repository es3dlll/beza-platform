# فهرس عمليات منصة بيزا

## الإصدارات والنشر

| الملف | الوصف |
|-------|-------|
| `releases/CHANGELOG.md` | سجل التغييرات الكامل للمنصة |
| `releases/DEPLOYMENT_PRODUCTION_v1.0.0.md` | دليل النشر للإنتاج v1.0.0 |
| `releases/PUBLIC_RELEASE_PLAN_v1.1.0.md` | خطة الإطلاق العامة v1.1.0 |
| `releases/BETA_RELEASE_CHECKLIST_v1.1.0.md` | قائمة تدقيق الإصدار التجريبي |

## كتيبات الطوارئ (Runbooks)

| الملف | الوصف |
|-------|-------|
| `runbooks/01-agent-cash.md` | التعامل مع مشاكل السيولة النقدية للوكلاء |
| `runbooks/02-fx-feed.md` | انقطاع تغذية أسعار الصرف |
| `runbooks/03-settlement-failure.md` | فشل التسوية اليومية |
| `runbooks/04-ledger-incident.md` | حوادث دفتر الأستاذ المالي |
| `runbooks/05-aml-backlog.md` | تراكم طابور فحص AML |

## رحلات المستخدم (User Journeys)

| الملف | الوصف |
|-------|-------|
| `runbooks/user-journeys/first-time-user.md` | رحلة المستخدم الجديد |
| `runbooks/user-journeys/kyc.md` | رحلة التوثيق |
| `runbooks/user-journeys/first-transfer.md` | رحلة أول تحويل |
| `runbooks/user-journeys/remittance-receive.md` | رحلة استلام حوالة |
| `runbooks/user-journeys/agent-cashout.md` | رحلة السحب النقدي |
| `runbooks/user-journeys/merchant-payment.md` | رحلة الدفع QR |
| `runbooks/user-journeys/payroll-employee.md` | رحلة استلام الراتب |
| `runbooks/user-journeys/fraud-review.md` | رحلة مراجعة الاحتيال |
| `runbooks/user-journeys/dispute-resolution.md` | رحلة حل النزاع |

## الإشعارات والمراقبة

| المجلد | المحتوى |
|--------|---------|
| `notifications/` | Push Notification، SMS، Email |
| `observability/` | تسجيل (Logging)، مقاييس (Metrics)، تنبيهات (Alerting) |

## الأرشيف

| المجلد | المحتوى |
|--------|---------|
| `archive/planning/` | خارطة الطريق، جلسات التخطيط، استبيانات |
| `archive/marketing/` | حملة الإطلاق والمواد التسويقية |
| `archive/engineering/` | مصفوفات فنية ومعايير بناء من مراحل سابقة |
| `archive/product/` | رؤية المنتج وتحديد نطاق الإصدارات السابقة |
| `archive/workflows/` | سير عمل وكلاء الذكاء الاصطناعي (مرجع تاريخي) |
| `archive/tasks/` | تتبع المهام حسب المجال (مرجع تاريخي) |
| `archive/plans/` | ملفات خطط جلسات سابقة (مرجع تاريخي) |

## هيكلية المجلدات

```
docs/operations/
├── INDEX.md                        ← أنت هنا
├── README.md                       ← نظرة عامة
├── QUICK_REFERENCE_OPERATORS.md    ← دليل المشغلين السريع
├── BETA_RETROSPECTIVE_v1.1.0.md    ← ملخص النسخة التجريبية
├── releases/                       ← سجل التغييرات وأدلة النشر
│   ├── CHANGELOG.md
│   ├── DEPLOYMENT_PRODUCTION_v1.0.0.md
│   ├── PUBLIC_RELEASE_PLAN_v1.1.0.md
│   └── BETA_RELEASE_CHECKLIST_v1.1.0.md
├── runbooks/                       ← كتيبات الاستجابة للحوادث
│   ├── 01-agent-cash.md
│   ├── 02-fx-feed.md
│   ├── 03-settlement-failure.md
│   ├── 04-ledger-incident.md
│   ├── 05-aml-backlog.md
│   └── user-journeys/             ← رحلات المستخدم
├── notifications/                  ← Push، SMS، Email
├── observability/                  ← تسجيل، مقاييس، تنبيهات، KPIs
└── archive/                        ← وثائق تاريخية
    ├── planning/
    ├── marketing/
    ├── engineering/
    ├── product/
    ├── workflows/
    ├── tasks/
    └── plans/
```
