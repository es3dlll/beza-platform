# Beza Platform - الهيكلية المعمارية

**Laravel 13 • React 19 • Flutter 3.29**

> وثيقة إعادة الهيكلة المعمارية - نسخة مُحسّنة وفق أفضل الممارسات للأنظمة المالية

---

## فهرس

1. [الرؤية والمبادئ](docs/architecture/PRINCIPLES.md)
2. [الهيكلية العامة للمشروع](#project-structure)
3. [دليل الوحدات النمطية](docs/architecture/MODULES.md)
4. [التواصل بين الوحدات](docs/architecture/COMMUNICATION.md)
5. [Backend - Laravel 13](docs/backend/OVERVIEW.md)
6. [Frontend Admin - React 19](docs/frontend/ADMIN.md)
7. [Mobile App - Flutter 3.29](docs/frontend/MOBILE.md)
8. [الأمان (Zero Trust)](docs/security/OVERVIEW.md)
9. [الامتثال (AML/KYC/CBS)](docs/compliance/OVERVIEW.md)
10. [الاختبارات والجودة](docs/architecture/QUALITY.md)
11. [النشر والبنية التحتية](docs/infrastructure/DEPLOYMENT.md)
12. [دليل التطوير السريع](docs/architecture/QUICKSTART.md)
13. [فهرس التوثيق الكامل](docs/README.md)

---

## Project Structure

```
beza-platform/
├── backend/                          # Laravel 13 API (Modular Monolith)
│   ├── app/
│   │   ├── Core/                     # النواة المشتركة (لا تعتمد على وحدات)
│   │   │   ├── ValueObjects/         # Money, Currency, Rate, Percentage
│   │   │   ├── Enums/                # TransactionStatus, WalletType, etc.
│   │   │   ├── Interfaces/          # Contracts للخدمات الأساسية
│   │   │   ├── Traits/              # Traits مشتركة (Auditable, HasULID)
│   │   │   ├── Exceptions/          # استثناءات النواة
│   │   │   └── Services/            # خدمات النواة (Encryption, AuditLogger)
│   │   ├── Modules/                  # الوحدات النمطية (31 وحدة)
│   │   │   ├── Identity/            # إدارة المستخدمين والمصادقة
│   │   │   ├── Wallet/              # المحافظ المالية متعددة العملات
│   │   │   ├── Ledger/              # دفتر الأستاذ المحاسبي
│   │   │   ├── CoreFinancialEngine/ # محرك المعاملات المالية
│   │   │   ├── Agent/               # شبكة الوكلاء
│   │   │   ├── FX/                  # صرف العملات
│   │   │   ├── Remittance/          # التحويلات الدولية
│   │   │   ├── Merchant/            # التجار و QR payments
│   │   │   ├── Fraud/               # كشف الاحتيال
│   │   │   ├── Settlement/          # التسويات المالية
│   │   │   ├── Bills/               # دفع الفواتير
│   │   │   ├── Payroll/             # الرواتب
│   │   │   ├── Savings/             # المدخرات
│   │   │   ├── Cards/               # البطاقات
│   │   │   ├── Financing/           # التمويل الإسلامي
│   │   │   ├── Marketplace/         # السوق الرقمي
│   │   │   ├── Escrow/              # الضمان المالي
│   │   │   ├── Takaful/             # التأمين التكافلي
│   │   │   ├── Investments/         # الاستثمارات
│   │   │   ├── OpenFinance/         # واجهات الطرف الثالث
│   │   │   ├── Notification/        # الإشعارات متعددة القنوات
│   │   │   ├── USSD/                # دعم الهواتف الأساسية
│   │   │   ├── Analytics/           # التحليلات والتقارير
│   │   │   ├── Compliance/          # الامتثال و AML
│   │   │   ├── Admin/               # واجهات الإدارة الخلفية
│   │   │   └── Shared/              # وحدات مساعدة مشتركة
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/ # متحكمات API (نحيفة)
│   │   │   ├── Middleware/          # Middleware مخصص
│   │   │   ├── Requests/            # Form Requests للتحقق
│   │   │   ├── Resources/           # API Resources
│   │   │   └── Responses/           # استجابات موحدة
│   │   ├── Events/                  # أحداث النظام (Domain Events)
│   │   ├── Listeners/              # مستمعو الأحداث
│   │   ├── Jobs/                   # مهام الخلفية (Queueable)
│   │   ├── Mail/                   # قوالب البريد الإلكتروني
│   │   ├── Notifications/          # إشعارات قاعدة البيانات
│   │   └── Policies/               # سياسات الصلاحيات (ABAC)
│   ├── bootstrap/
│   ├── config/
│   │   ├── modules.php             # تكوين الوحدات
│   │   ├── financial.php           # إعدادات مالية (رسوم، حدود)
│   │   ├── compliance.php          # قواعد الامتثال
│   │   └── security.php            # إعدادات الأمان
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── 0001_01_01_000000_core_tables.php
│   │   │   ├── 0001_01_01_000001_ledger_tables.php
│   │   │   └── modules/            # هجرات كل وحدة منفصلة
│   │   ├── seeders/
│   │   └── factories/
│   ├── routes/
│   │   ├── api.php                 # نقطة دخول API الرئيسية
│   │   ├── modules/                # مسارات الوحدات (مجزأة)
│   │   ├── admin.php               # مسارات لوحة الإدارة
│   │   └── console.php
│   ├── resources/
│   │   ├── lang/
│   │   │   ├── ar/                 # العربية (الأساسية)
│   │   │   ├── en/                 # الإنجليزية
│   │   │   ├── ku/                 # الكردية
│   │   │   └── hy/                 # الأرمنية
│   │   └── views/emails/           # قوالب البريد
│   ├── storage/
│   │   ├── logs/
│   │   ├── audit/                  # سجلات التدقيق (WORM)
│   │   └── compliance/             # تقارير الامتثال
│   ├── tests/
│   │   ├── Feature/
│   │   │   ├── Modules/            # اختبارات التكامل للوحدات
│   │   │   └── Core/               # اختبارات النواة
│   │   ├── Unit/
│   │   ├── Stubs/                  # بيانات اختبار وهمية
│   │   └── Support/                # أدوات مساعدة للاختبار
│   ├── docker/                     # تكوين Docker
│   ├── docs/
│   │   ├── api/                    # OpenAPI 3.1 Specification
│   │   ├── architecture/           # ADRs وقرارات معمارية
│   │   └── compliance/             # وثائق الامتثال
│   ├── artisan
│   ├── composer.json
│   ├── phpunit.xml
│   ├── ecs.php                     # PHP-CS-Fixer config
│   ├── pest.php                    # Pest configuration
│   └── .env.example
│
├── frontend/
│   ├── admin/                      # React 19 Admin Panel
│   │   ├── src/
│   │   │   ├── app/                # React Router v7 + Layouts
│   │   │   │   ├── routes/         # تعريف المسارات
│   │   │   │   ├── layouts/        # تخطيطات الصفحات
│   │   │   │   └── loaders/        # Data loaders
│   │   │   ├── features/           # ميزات مجمعة (Feature-Sliced)
│   │   │   │   ├── auth/           # المصادقة
│   │   │   │   ├── users/          # إدارة المستخدمين
│   │   │   │   ├── kyc/            # مراجعة KYC
│   │   │   │   ├── transactions/   # البحث في المعاملات
│   │   │   │   ├── fraud/          # حالات الاحتيال
│   │   │   │   ├── fx/             # إدارة أسعار الصرف
│   │   │   │   ├── agents/         # إدارة الوكلاء
│   │   │   │   ├── reports/        # التقارير والتحليلات
│   │   │   │   └── settings/       # إعدادات النظام
│   │   │   ├── entities/           # كيانات الأعمال
│   │   │   │   ├── user/
│   │   │   │   ├── transaction/
│   │   │   │   ├── wallet/
│   │   │   │   └── agent/
│   │   │   ├── shared/             # مكونات ومكتبات مشتركة
│   │   │   │   ├── ui/             # مكونات UI (Button, Modal, Table)
│   │   │   │   ├── api/            # API client + interceptors
│   │   │   │   ├── hooks/          # React Hooks مخصصة
│   │   │   │   ├── utils/          # دوال مساعدة
│   │   │   │   ├── constants/      # ثوابت النظام
│   │   │   │   ├── types/          # TypeScript types
│   │   │   │   └── i18n/           # الترجمات (ar, en, ku, hy)
│   │   │   ├── lib/                # تكوين المكتبات
│   │   │   │   ├── axios.ts        # Axios instance
│   │   │   │   ├── query.ts        # React Query config
│   │   │   │   ├── store.ts        # Zustand store
│   │   │   │   └── theme.ts        # MUI/Chakra theme
│   │   │   ├── assets/             # صور، أيقونات، خطوط
│   │   │   ├── main.tsx            # نقطة الدخول
│   │   │   └── vite-env.d.ts
│   │   ├── public/
│   │   ├── index.html
│   │   ├── vite.config.ts
│   │   ├── tsconfig.json
│   │   ├── tailwind.config.js
│   │   └── package.json
│   │
│   └── mobile/                     # Flutter 3.29 Super App
│       ├── lib/
│       │   ├── core/               # النواة المشتركة
│       │   │   ├── constants/      # ثوابت التطبيق
│       │   │   ├── errors/         # معالجة الأخطاء
│       │   │   ├── network/        # Dio client + interceptors
│       │   │   ├── storage/        # Secure storage + Hive
│       │   │   ├── utils/          # دوال مساعدة
│       │   │   ├── theme/          # ThemeData + RTL support
│       │   │   └── di/             # GetIt dependency injection
│       │   ├── features/           # ميزات التطبيق (مجزأة)
│       │   │   ├── auth/           # data/ domain/ presentation/
│       │   │   ├── wallet/
│       │   │   ├── transfer/
│       │   │   ├── bills/
│       │   │   ├── agent/
│       │   │   ├── merchant/
│       │   │   ├── remittance/
│       │   │   ├── financing/
│       │   │   ├── marketplace/
│       │   │   └── profile/
│       │   ├── shared/             # مكونات مشتركة
│       │   │   ├── widgets/        # Buttons, Cards, Inputs
│       │   │   ├── extensions/     # Extensions على Dart types
│       │   │   ├── validators/     # Form validators
│       │   │   └── l10n/           # ARB files for i18n
│       │   ├── routes/             # GoRouter configuration
│       │   ├── injections/         # تسجيل الاعتمادات
│       │   └── main.dart           # نقطة الدخول
│       ├── assets/                 # صور، خطوط، أيقونات
│       ├── test/                   # اختبارات الوحدة والتكامل
│       ├── integration_test/       # اختبارات التكامل الكاملة
│       ├── pubspec.yaml
│       ├── analysis_options.yaml   # قواعد linting الصارمة
│       └── flutter_launcher_icons.yaml
│
├── infrastructure/                 # البنية التحتية ككود
│   ├── docker/
│   │   ├── php/                   # PHP-FPM image customizations
│   │   ├── nginx/                 # Nginx config + SSL
│   │   ├── mysql/                 # MySQL config + init scripts
│   │   ├── redis/                 # Redis config
│   │   ├── rabbitmq/              # RabbitMQ config
│   │   └── docker-compose.yml
│   ├── k8s/                       # Kubernetes manifests
│   │   ├── base/
│   │   ├── overlays/
│   │   └── kustomization.yaml
│   ├── terraform/                 # IaC
│   │   ├── modules/
│   │   ├── environments/
│   │   └── main.tf
│   └── monitoring/
│       ├── prometheus.yml
│       ├── grafana/
│       └── alerts/
│
├── docs/                          # التوثيق المركزي (انظر docs/README.md)
│   ├── architecture/              # المبادئ، الوحدات، التواصل، الجودة
│   │   ├── PRINCIPLES.md
│   │   ├── MODULES.md
│   │   ├── COMMUNICATION.md
│   │   ├── QUALITY.md
│   │   └── QUICKSTART.md
│   ├── backend/                   # Laravel 13 API
│   ├── frontend/                  # React 19 + Flutter 3.29
│   ├── security/                  # Zero Trust، JWT، تشفير
│   ├── compliance/                # AML، KYC، متطلبات CBS
│   ├── infrastructure/            # Docker، نشر، نسخ احتياطي
│   ├── prd/                       # متطلبات المنتج
│   ├── planning/                  # خارطة طريق، تخطيط
│   ├── operations/                # عمليات، Runbooks، إصدارات
│   │   ├── releases/
│   │   ├── runbooks/
│   │   └── archive/
│   ├── journeys/                  # رحلات المستخدم
│   ├── marketing/                 # حملة الإطلاق
│   └── shared/                    # معايير مشتركة (7 مجالات)
│       ├── compliance/
│       ├── data-governance/
│       ├── design-system/
│       ├── notifications/
│       ├── observability/
│       ├── security/
│       └── testing/
│
├── .github/
│   ├── workflows/
│   │   ├── ci.yml                 # CI pipeline
│   │   ├── cd-staging.yml         # النشر للتجريب
│   │   ├── cd-production.yml      # النشر للإنتاج
│   │   └── security-scan.yml      # فحوصات الأمان
│   ├── CODEOWNERS
│   ├── PULL_REQUEST_TEMPLATE.md
│   └── ISSUE_TEMPLATE/
│
├── scripts/                       # سكربتات التشغيل والصيانة
│   ├── deploy.sh
│   ├── backup.sh
│   ├── health-check.sh
│   └── seed-demo.sh
│
├── .editorconfig
├── .gitignore
├── .gitattributes
├── Makefile                       # أوامر التطوير الموحدة
├── README.md
└── ARCHITECTURE.md                # هذه الوثيقة
```
