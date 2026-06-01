# فهرس - عرض الرصيد (W2 Balance)

```
W2-balance/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md              # فكرة العمل وسيناريو المستخدم
├── 02-architecture.md               # مكان العملية في النظام
├── 03-data-flow-sequence.md         # تدفق البيانات الكامل (Sequence Diagram)
├── 04-database-relationships.md     # علاقات الجداول + ER
├── 05-migrations.md                 # كود الميغريشن الكامل
├── 06-eloquent-models.md            # الموديلز مع العلاقات وال casts
├── 07-validation-rules.md           # كل قواعد التحقق + أسبابها
├── 08-controller-full-code.md       # المتحكم الكامل مع كل سطر
├── 09-service-layer-wallet.md       # WalletService كامل
├── 10-service-layer-balance.md      # BalanceService كامل
├── 11-events-and-listeners.md       # الأحداث المرتبطة بالرصيد
├── 12-notification-system.md        # (لا يوجد — استعلام وليس إشعار)
├── 13-exception-handling.md         # كل الاستثناءات ومعالجتها
├── 14-database-transactions-acid.md # ACID في القراءات
├── 15-api-specification.md          # OpenAPI / Postman كامل
├── 16-flutter-implementation.md     # Flutter UI + BLoC + Repository
├── 17-react-implementation.md       # React UI + Hooks + API
├── 18-testing-complete.md           # كل الاختبارات
├── 19-edge-cases.md                 # حالات الحافة + سيناريوهات خطأ
└── 20-security-audit.md             # أمان العملية خطوة بخطوة
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | عرض الرصيد |
| الأولوية | P1 (عالية) |
| API | `GET /api/v1/wallet/balance` |
| Controller | `BalanceController@index` |
| Service | `BalanceService` / `WalletService` |
| Cache | 30 ثانية (Redis) |
| DB Tables | wallets |
| المخرجات | `{syp: {balance, frozen, wallet_number}, usd: {...}}` |
| Flutter Screen | `HomeScreen` |
| React Page | `DashboardPage` |
