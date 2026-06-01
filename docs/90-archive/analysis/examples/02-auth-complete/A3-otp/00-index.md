# 00 - فهرس - رمز التحقق (OTP)

```
A3-otp/
├── 00-index.md                     ← أنت هنا
├── 01-business-idea.md             # فكرة العمل وسيناريو المستخدم
├── 02-architecture.md              # مكان العملية في النظام
├── 03-data-flow-sequence.md        # تدفق البيانات الكامل (Sequence Diagram)
├── 04-database-relationships.md    # علاقات الجداول + ER
├── 05-migrations.md                # كود الميغريشن الكامل
├── 06-eloquent-models.md           # الموديلز مع العلاقات وال casts
├── 07-validation-rules.md          # كل قواعد التحقق + أسبابها
├── 08-controller-full-code.md      # المتحكم الكامل مع كل سطر
├── 09-service-layer.md             # سيرفس لير العملية
├── 10-auth-guards-middleware.md    # المصادقة والصلاحيات
├── 11-events-and-listeners.md      # الأحداث والمستمعين
├── 12-notification-system.md       # FCM + SMS + Email
├── 13-exception-handling.md        # كل الاستثناءات ومعالجتها
├── 14-rate-limiting-brute-force.md # منع الهجمات
├── 15-api-specification.md         # OpenAPI / Postman كامل
├── 16-flutter-implementation.md    # Flutter UI + BLoC + Repository
├── 17-react-implementation.md      # React UI + Hooks + API
├── 18-testing-complete.md          # كل الاختبارات
├── 19-edge-cases.md                # حالات الحافة
└── 20-security-audit.md            # أمان العملية خطوة بخطوة
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | طلب والتحقق من OTP |
| الأولوية | P1 (مهم) |
| APIs | `POST /api/v1/auth/request-otp`, `POST /api/v1/auth/verify-otp` |
| Controller | `AuthController@requestOtp` / `AuthController@verifyOtp` |
| Service | `OtpService` |
| تخزين OTP | Redis Cache (otp_{phone}) |
| صلاحية OTP | 300 ثانية (5 دقائق) |
| طول OTP | 6 أرقام |
| DB Tables | users (phone_verified_at) |
| Flutter Screen | `OtpVerificationScreen` |
| React Page | `OtpVerificationPage` |
