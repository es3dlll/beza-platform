# فهرس - A5: المصادقة الثنائية (2FA - TOTP)

```
A5-2fa/
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
| اسم العملية | المصادقة الثنائية (TOTP) |
| الأولوية | P1 (مهم) |
| APIs | `POST /api/v1/auth/2fa/enable`, `POST /api/v1/auth/2fa/verify` |
| Controller | `TwoFactorController@enable` / `TwoFactorController@verify` |
| Service | `TwoFactorService` |
| مكتبة TOTP | `pragmarx/google2fa-laravel` |
| DB Tables | users (two_factor_secret, two_factor_recovery_codes, two_factor_confirmed) |
| إجباري | للمعاملات > 1,000 USD |
| Flutter Screen | `TwoFactorSetupScreen` |
| React Page | `TwoFactorSetupPage` |
