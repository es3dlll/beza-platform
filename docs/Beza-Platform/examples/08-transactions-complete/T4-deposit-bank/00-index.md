# فهرس - إيداع بنكي (Bank Deposit)

```
T4-deposit-bank/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md              # فكرة العمل وسيناريو المستخدم
├── 02-architecture.md               # مكان العملية في النظام
├── 03-data-flow-sequence.md         # تدفق البيانات الكامل (Sequence Diagram)
├── 04-database-relationships.md     # علاقات الجداول + ER
├── 05-migrations.md                 # كود الميغريشن الكامل
├── 06-eloquent-models.md            # الموديلز مع العلاقات وال casts
├── 07-validation-rules.md           # كل قواعد التحقق + أسبابها
├── 08-controller-full-code.md       # المتحكم الكامل مع كل سطر
├── 09-service-layer-wallet.md       # WalletService
├── 10-service-layer-core.md         # Service Layer الكامل
├── 11-events-and-listeners.md       # Event + مستمعيه
├── 12-notification-system.md        # FCM + SMS + Email
├── 13-exception-handling.md         # كل الاستثناءات ومعالجتها
├── 14-database-transactions-acid.md # ACID + الأقفال + الـ Race Conditions
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
| اسم العملية | إيداع بنكي |
| الأولوية | P0 |
| API | POST /api/v1/deposit/bank, GET /api/v1/deposit/bank/status |
| Controller | BankDepositController |
| Service | BankDepositService |
| Event | BankDepositApproved / BankDepositRejected |
| Listener | SendBankDepositNotification |
| DB Tables | transactions, bank_deposits, wallets |
| رسوم | 0% |
| حد يومي | min 10 USD/10000 SYP, max 10000 USD/day |
| Flutter Screen | BankDepositScreen |
| React Page | BankDepositPage |
