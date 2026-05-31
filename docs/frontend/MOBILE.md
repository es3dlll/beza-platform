# Mobile App - Flutter 3.29

## نظرة عامة

تطبيق المحفظة الرقمية الشامل (Super App) بتقنية Flutter 3.29 مع Clean Architecture (data/domain/presentation).

## هيكل المشروع

```
mobile/
├── lib/
│   ├── core/                       # النواة المشتركة
│   │   ├── constants/              # ثوابت التطبيق
│   │   ├── errors/                 # معالجة الأخطاء
│   │   ├── network/                # Dio client + interceptors
│   │   ├── storage/                # Secure storage + Hive
│   │   ├── utils/                  # دوال مساعدة
│   │   ├── theme/                  # ThemeData + RTL
│   │   └── di/                     # GetIt DI
│   ├── features/                   # Feature modules
│   │   ├── auth/                   # data/domain/presentation
│   │   ├── wallet/                 # المحفظة والرصيد
│   │   ├── transfer/               # التحويلات المالية
│   │   ├── bills/                  # دفع الفواتير
│   │   ├── agent/                  # الوكلاء
│   │   ├── merchant/               # التجار (QR)
│   │   ├── remittance/             # الحوالات الدولية
│   │   ├── financing/              # التمويل
│   │   ├── marketplace/            # السوق الرقمي
│   │   └── profile/                # الملف الشخصي
│   ├── shared/                     # مكونات مشتركة
│   │   ├── widgets/                # Buttons, Cards, Inputs
│   │   ├── extensions/             # Dart type extensions
│   │   ├── validators/             # Form validators
│   │   └── l10n/                   # ARB i18n files
│   ├── routes/                     # GoRouter
│   ├── injections/                 # DI registration
│   └── main.dart                   # نقطة الدخول
├── assets/
├── test/
├── integration_test/
├── pubspec.yaml
├── analysis_options.yaml
└── flutter_launcher_icons.yaml
```

## وضع Offline-First

بسبب طبيعة البيئة السورية (انقطاع الكهرباء والإنترنت)، التطبيق يجب أن:
- يخزن البيانات الأساسية محلياً (Hive/SQLite)
- يعمل في وضع غير متصل للعمليات غير الحرجة
- يستأنف المعاملات المقطوعة تلقائياً عند عودة الاتصال
- يستخدم آلية إعادة محاولة ذكية (exponential backoff)

## الميزات الأساسية (MVP)

1. المصادقة (تسجيل، دخول، نسيت كلمة المرور)
2. المحفظة والرصيد (عرض الرصيد، الحركات)
3. التحويلات (P2P، محلي، دولي)
4. دفع الفواتير (كهرباء، ماء، اتصالات)
5. شحن الرصيد (عبر وكيل)
6. سحب نقدي (عبر وكيل)
7. دفع QR للتجار
8. الإشعارات
9. الملف الشخصي والإعدادات
