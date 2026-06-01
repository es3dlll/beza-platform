# Mobile App — Flutter 3.29

## نظرة عامة

تطبيق المحفظة الرقمية الشامل (Super App) بتقنية Flutter 3.29 مع Clean Architecture (data/domain/presentation) لكل feature.

## هيكل المشروع

```
mobile/
├── lib/
│   ├── core/                         # النواة المشتركة (مستقلة عن الميزات)
│   │   ├── constants/                # ثوابت التطبيق (API URLs, Timeouts)
│   │   ├── errors/                   # معالجة الأخطاء (Failure, Exceptions)
│   │   ├── network/                  # Dio client + interceptors (Auth, Logging)
│   │   │   ├── dio_client.dart       # Dio instance مع interceptors
│   │   │   ├── auth_interceptor.dart # إرفاق JWT + Device Fingerprint
│   │   │   ├── retry_interceptor.dart # إعادة محاولة ذكية (exponential backoff)
│   │   │   └── network_info.dart     # فحص الاتصال (Connectivity)
│   │   ├── storage/                  # التخزين المحلي
│   │   │   ├── secure_storage.dart   # مفاتيح، توكنات، PIN
│   │   │   └── local_database.dart   # Hive/SQLite للتخزين المؤقت
│   │   ├── utils/                    # دوال مساعدة
│   │   │   ├── money.dart            # كائن قيمة Money (immutable, bigint)
│   │   │   ├── validators.dart       # تحقق المدخلات
│   │   │   └── formatters.dart       # تنسيق الأرقام والعملات
│   │   ├── theme/                    # ThemeData + RTL support
│   │   └── di/                       # GetIt Dependency Injection
│   ├── features/                     # ميزات التطبيق (مجزأة بـ Clean Architecture)
│   │   ├── auth/                     # data/domain/presentation/
│   │   │   ├── data/
│   │   │   │   ├── datasources/      # Remote + Local
│   │   │   │   ├── models/           # DTOs مع fromJson/toJson
│   │   │   │   └── repositories/     # تنفيذ AuthRepository
│   │   │   ├── domain/
│   │   │   │   ├── entities/         # User, Session
│   │   │   │   ├── repositories/     # AuthRepository interface
│   │   │   │   └── usecases/         # Login, Register, VerifyOTP
│   │   │   └── presentation/
│   │   │       ├── providers/        # Riverpod/Bloc State Management
│   │   │       ├── pages/            # شاشات UI
│   │   │       └── widgets/          # مكونات خاصة بالميزة
│   │   ├── wallet/                   # المحفظة والرصيد
│   │   ├── transfer/                 # التحويلات المالية
│   │   ├── bills/                    # دفع الفواتير
│   │   ├── agent/                    # الوكلاء (إيداع/سحب)
│   │   ├── merchant/                 # التجار (QR)
│   │   ├── remittance/               # الحوالات الدولية
│   │   ├── financing/                # التمويل
│   │   ├── marketplace/              # السوق الرقمي
│   │   └── profile/                  # الملف الشخصي
│   ├── shared/                       # مكونات مشتركة بين الميزات
│   │   ├── widgets/                  # Buttons, Cards, Inputs, Dialogs
│   │   ├── extensions/               # Dart type extensions
│   │   ├── validators/               # Validators مشتركة
│   │   └── l10n/                     # ARB files للترجمة (ar, en, ku, hy)
│   ├── routes/                       # GoRouter configuration
│   │   ├── app_router.dart           # تعريف المسارات + Guards
│   │   └── route_names.dart          # ثوابت أسماء المسارات
│   ├── injections/                   # تسجيل الاعتمادات (GetIt)
│   │   ├── core_injections.dart      # خدمات النواة
│   │   └── feature_injections.dart   # خدمات الميزات
│   └── main.dart                     # نقطة الدخول
├── assets/                           # صور، خطوط، أيقونات
│   ├── images/
│   ├── fonts/
│   └── icons/
├── test/                             # اختبارات الوحدة والتكامل
│   ├── unit/
│   ├── widget/
│   └── mocks/
├── integration_test/                 # اختبارات التكامل الكاملة
├── pubspec.yaml
├── analysis_options.yaml             # قواعد linting الصارمة
└── flutter_launcher_icons.yaml
```

---

## Money Value Object

```dart
// core/utils/money.dart
final class Money {
  final BigInt amount;     // بوحدات صغرى (فلس)
  final Currency currency;

  const Money(this.amount, this.currency);

  Money operator +(Money other) {
    // تحقق من تطابق العملة
    return Money(amount + other.amount, currency);
  }

  Money operator -(Money other) {
    return Money(amount - other.amount, currency);
  }

  String format({bool showSymbol = true}) { /* تنسيق */ }
}
```

---

## إدارة الحالة (State Management)

- **اختيار:** Riverpod (موصى به) أو Bloc
- **المبدأ:** كل شاشة لها Provider/Bloc مستقل
- **دورة الحياة:** autoDispose عند مغادرة الشاشة
- **الاستثناء:** بيانات المستخدم والمصادقة — بقاء عالمي (global)

```
Screen
  ├── Widget (UI) ← Consumer/BlocBuilder
  │     └── Provider/Bloc (State Management)
  │           ├── UseCase (منطق الأعمال)
  │           └── Repository (بيانات)
```

---

## وضع Offline-First

بسبب طبيعة البيئة السورية (انقطاع الكهرباء والإنترنت):

1. **التخزين المحلي:** Hive للبيانات غير الحساسة، Secure Storage للتوكنات والمفاتيح
2. **العمليات غير الحرجة:** تعمل في وضع عدم الاتصال (عرض الرصيد، سجل الحركات)
3. **المعاملات الحرجة:** تتطلب اتصالاً نشطاً (تحويل، دفع) مع queueing للانقطاعات
4. **استئناف المعاملات:** إعادة محاولة ذكية (exponential backoff) مع إشعار المستخدم
5. **التزامن:** عند عودة الاتصال، مزامنة تلقائية مع الخادم

## المصادقة والجهاز

- JWT (15 دقيقة) مخزن في Secure Storage
- Refresh Token (7 أيام) مع دوران تلقائي عبر auth_interceptor
- Device Binding: fingerprint + device ID في كل طلب مالي
- Biometric: Face ID / Fingerprint كخيار دخول سريع بعد المصادقة الأولى

## RTL والدعم اللغوي

- العربية هي اللغة الأساسية مع RTL الكامل
- اللغات المدعومة: العربية، الإنجليزية، الكردية، الأرمنية
- ARB files للترجمة مع locale switching فوري
- اختبار RTL في كل مكون UI

## العلاقة مع الأقسام الأخرى

- **التصميم:** [`design-system/`](design-system/) — المكونات البصرية والألوان
- **الأمان:** [`../compliance/security-policies/`](../compliance/security-policies/) — المصادقة والتشفير
- **الخلفي:** [`../backend/OVERVIEW.md`](../backend/OVERVIEW.md) — نقاط API
- **الإشعارات:** [`../operations/notifications/`](../operations/notifications/) — Push/SMS
