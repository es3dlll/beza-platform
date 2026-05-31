# 🌱 Beza — المنصة المالية الرقمية المتكاملة

<div align="center" dir="rtl">

**بيزى — محفظتك الرقمية الذكية**

منظومة مالية رقمية متكاملة: محفظة مزدوجة (SYP/USD) | بطاقات افتراضية وفيزيائية |
صفقات استثمارية | بوابة دفع للتجار | شبكة وكلاء صرافة | متجر إلكتروني

---

## 📑 فهرس التوثيق الكامل

</div>

<div dir="rtl">

| # | المستند | الوصف |
|---|---------|-------|
| 01 | [الرؤية والملخص التنفيذي](./01-overview.md) | ملخص المنصة، الرؤية، الرسالة، OKRs |
| 02 | [نظام التصميم (Design System)](./02-design-system.md) | الألوان، الخطوط، الأزرار، البطاقات، النماذج، الجداول |
| 03 | [وثيقة متطلبات المنتج (PRD)](./03-prd.md) | شخصيات المستخدمين، الميزات، KPIs، القيود |
| 04 | [المعمارية الشاملة](./04-architecture.md) | البنية التحتية، الطبقات، تدفق البيانات، التكاملات |
| 05 | [Laravel API Core](./05-backend-laravel.md) | النماذج، المتحكمات، الخدمات، الأحداث، جداول الصفقات |
| 06 | [لوحة تحكم المشرف](./06-admin-dashboard.md) | React Admin، الإحصائيات، إدارة المستخدمين |
| 07 | [لوحة تحكم التاجر](./07-merchant-dashboard.md) | إدارة المنتجات، الطلبات، المدفوعات |
| 08 | [الواجهة الأمامية (React SPA)](./08-user-frontend.md) | موقع المستخدم، SEO، المكونات |
| 09 | [المتجر الإلكتروني](./09-storefront.md) | واجهة المتجر الجاهزة، صفحة المنتج |
| 10 | [تطبيق الجوال (Flutter)](./10-mobile-app.md) | شاشات التطبيق، الخريطة، الـ Agent Dashboard |
| 11 | [صفحة الهبوط التسويقية](./11-landing-page.md) | Next.js SSG، مكون Hero |
| 12 | [نظام الوكلاء](./12-agents-system.md) | السحب والإيداع النقدي، آلية العمل |
| 13 | [المدفوعات والمقاصة](./13-payments-settlement.md) | الرسوم، التسوية مع التجار |
| 14 | [الأمان والحماية](./14-security.md) | الإجراءات الأمنية، 2FA، منع الاحتيال |
| 15 | [النشر والتشغيل](./15-deployment.md) | Laragon localhost، GitHub Actions CI/CD |
| 16 | [الاختبارات](./16-testing.md) | Unit Tests، Widget Tests، K6 |
| 17 | [قوائم المراجعة](./17-checklists.md) | Pre-launch، Launch Day، Post-launch |

---

### 🚀 بدء سريع

```bash
# 1. تشغيل Laravel API
cd backend-laravel
cp .env.example .env   # عدّل إعدادات قاعدة البيانات
composer install
php artisan migrate --seed
php artisan serve --host=localhost --port=8000

# 2. تشغيل Admin Dashboard (نافذة منفصلة)
cd admin-dashboard
npm install && npm run dev

# 3. تشغيل User Frontend (نافذة منفصلة)
cd user-frontend
npm install && npm run dev

# 4. تشغيل Landing Page (نافذة منفصلة)
cd landing-page
npm install && npm run dev
```

### روابط التشغيل المحلي

| الخدمة | الرابط |
|--------|--------|
| API | http://localhost:8000/api |
| Admin Dashboard | http://localhost:5173 |
| User Frontend | http://localhost:5174 |
| Landing Page | http://localhost:3000 |

</div>
