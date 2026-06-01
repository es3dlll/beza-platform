# فهرس - التحويل عبر QR (QR Transfer)

```
T2-transfer-qr/
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
| اسم العملية | التحويل عبر QR |
| الأولوية | P1 |
| API | POST /api/v1/transfer/qr-generate, POST /api/v1/transfer/qr-pay |
| Controller | QRController@generate, QRController@pay |
| Service | QRService / TransferService |
| Event | QRPaymentCompleted |
| Listener | SendQRNotification |
| DB Tables | transactions, qr_codes, wallets |
| رسوم | 0% (مجاني) |
| حد يومي | 500 USD/day (QR Pay) |
| Flutter Screen | QRScreen + QRScannerScreen |
| React Page | QRPage + QRScannerPage |
