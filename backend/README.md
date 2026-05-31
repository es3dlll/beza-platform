# منصة بيزا - النواة المالية السورية

**Beza Platform — Syrian Financial Kernel**

مستودع النواة المالية الخلفية لمنصة بيزا، البنية التحتية المالية الرقمية الوطنية للجمهورية العربية السورية.

## الهدف الاستراتيجي

تمكين 22 مليون مقيم و6 ملايين مغترب من الوصول إلى خدمات مالية رقمية آمنة، شفافة، ومتوافقة تنظيميًا — عبر محرك مالي مركزي (CFE) ودفتر أستاذ (Ledger) بنظام القيد المزدوج غير القابل للتعديل.

## المعمارية

- **النمط:** Modular Monolith على Laravel 11
- **التواصل:** Event Bus داخلي (يمنع الاستدعاء المباشر بين الوحدات)
- **الوحدات:** Agent, EventBus, FinancialCore, Fraud, Fx, Identity, Ledger, Wallet
- **قاعدة البيانات:** MySQL/PostgreSQL، ULID للمعرّفات، bigint للأرصدة (وحدات صغرى)
- **الأمان:** Zero Trust، AES-256، TLS 1.3، RBAC/ABAC

## شروط التشغيل المحلي

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
```

## أمر بناء الواجهة

```bash
php artisan serve
```

## مواصفات OpenAPI

`docs/specs/openapi.yaml`

## التغطية الاختبارية

```bash
php artisan test --use-baseline=deprecation-baseline.xml
```
142 اختبار، 416 تأكيد، 0 إخفاق.

---

> **تحذير:** يمنع التعديل المباشر على دفتر الأستاذ (Ledger) أو المحرك المالي المركزي (CFE) دون موافقة صريحة من لجنة الهندسة واختبارات شاملة تغطي التوازن والامتثال والأمان. أي تغيير يخالف هذا الشرط يُعتبر خرقاً أمنياً وتنظيمياً.
