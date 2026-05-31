# Infrastructure — البنية التحتية

> **الهدف:** توثيق البنية التحتية التقنية: Docker، النشر، المراقبة، النسخ الاحتياطي  
> **الجمهور المستهدف:** DevOps، مهندسو البنية التحتية، مسؤولو النظام  
> **العلاقة:** هذا القسم يصف كيفية تشغيل العمارة الموثقة في بقية الأقسام على خوادم الإنتاج

---

## الملفات

| الملف | الوصف |
|-------|-------|
| [`CURRENT_VERSIONS.md`](CURRENT_VERSIONS.md) | إصدارات التقنيات الحالية (PHP, Laravel, Flutter, Node.js) |
| [`DEPLOYMENT.md`](DEPLOYMENT.md) | Docker Compose، بيئات النشر، متطلبات الخادم، المراقبة |
| [`UPGRADE_LOG_20260531.md`](UPGRADE_LOG_20260531.md) | سجل ترقية الحزم والتبعيات |

---

## بيئات النشر

| البيئة | الخادم | الغرض |
|--------|--------|-------|
| **Development** | محلي (Laragon) | تطوير واختبار يومي |
| **Staging** | VPS (أوروبا) | تكامل واختبار ما قبل الإنتاج |
| **Production** | VPS (سوريا/لبنان + Cloudflare) | الإطلاق الرسمي |

---

## الخدمات في Docker

```
app:        Laravel + PHP-FPM + Nginx
mysql:      MySQL 8.4 (إعدادات AML-friendly)
redis:      جلست، طوابير، تخزين مؤقت، Rate Limiting
rabbitmq:   Event Bus للتواصل بين الوحدات
backup:     نسخ احتياطي مشفر AES-256
monitoring: Prometheus + Grafana
```

---

## العلاقة مع الأقسام الأخرى

- **العمارة** (`../architecture/`): الهيكل المعماري المطلوب تشغيله
- **الأمان** (`../security/`): أمان الخادم، TLS، UFW، Fail2Ban
- **المعايير المشتركة/المراقبة** (`../shared/observability/`): تنبيهات ومقاييس
- **العمليات** (`../operations/`): Runbooks للاستجابة لحوادث البنية التحتية
