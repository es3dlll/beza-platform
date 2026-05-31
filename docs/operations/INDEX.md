# فهرس عمليات منصة بيزا

## الإصدارات والنشر

| الملف | الوصف |
|-------|-------|
| `releases/CHANGELOG.md` | سجل التغييرات الكامل للمنصة |
| `releases/DEPLOYMENT_PRODUCTION_v1.0.0.md` | دليل النشر للإنتاج v1.0.0 |

## كتيبات الطوارئ (Runbooks)

| الملف | الوصف |
|-------|-------|
| `runbooks/01-agent-cash.md` | التعامل مع مشاكل السيولة النقدية للوكلاء |
| `runbooks/02-fx-feed.md` | انقطاع تغذية أسعار الصرف |
| `runbooks/03-settlement-failure.md` | فشل التسوية اليومية |
| `runbooks/04-ledger-incident.md` | حوادث دفتر الأستاذ المالي |
| `runbooks/05-aml-backlog.md` | تراكم طابور فحص AML |

## هيكلية المجلدات

```
docs/operations/
├── INDEX.md              ← أنت هنا
├── releases/             ← سجل التغييرات وأدلة النشر
│   ├── CHANGELOG.md
│   └── DEPLOYMENT_PRODUCTION_v1.0.0.md
└── runbooks/             ← كتيبات الاستجابة للحوادث
    ├── 01-agent-cash.md
    ├── 02-fx-feed.md
    ├── 03-settlement-failure.md
    ├── 04-ledger-incident.md
    └── 05-aml-backlog.md
```
