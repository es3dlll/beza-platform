# فهرس - إنشاء المحفظة المزدوجة (W1 Create Wallet)

```
W1-create-wallet/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md              # فكرة العمل وسيناريو المستخدم
├── 02-architecture.md               # مكان العملية في النظام
├── 03-data-flow-sequence.md         # تدفق البيانات الكامل (Sequence Diagram)
├── 04-database-relationships.md     # علاقات الجداول + ER
├── 05-migrations.md                 # كود الميغريشن الكامل
├── 06-eloquent-models.md            # الموديلز مع العلاقات وال casts
├── 07-validation-rules.md           # كل قواعد التحقق + أسبابها
├── 08-controller-full-code.md       # الـ Listener والمستمع
├── 09-service-layer-wallet.md       # WalletService كامل
├── 10-service-layer-create-wallet.md # CreateWalletService كامل
├── 11-events-and-listeners.md       # UserCreated + CreateUserWallets
├── 12-notification-system.md        # ترحيب بالمستخدم الجديد
├── 13-exception-handling.md         # كل الاستثناءات ومعالجتها
├── 14-database-transactions-acid.md # ACID + الأقفال + الـ Race Conditions
├── 15-api-specification.md          # (تلقائي - لا يحتاج API منفصل)
├── 16-flutter-implementation.md     # عرض المحافظ بعد التسجيل
├── 17-react-implementation.md       # عرض المحافظ بعد التسجيل
├── 18-testing-complete.md           # كل الاختبارات
├── 19-edge-cases.md                 # حالات الحافة + سيناريوهات خطأ
└── 20-security-audit.md             # أمان العملية خطوة بخطوة
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | إنشاء المحفظة المزدوجة (SYP + USD) |
| الأولوية | P0 (حرجة) |
| API | تلقائي — لا يحتاج API منفصل |
| Trigger | `User::created` event |
| Listener | `CreateUserWallets` |
| Service | `CreateWalletService` / `WalletService` |
| DB Tables | users, wallets |
| محفظة SYP | رصيد 0, بادئة 62 |
| محفظة USD | رصيد 5 (هدية), بادئة 63 |
| Flutter Screen | شاشة التسجيل ← توجيه للرئيسية |
| React Page | صفحة التسجيل ← توجيه للرئيسية |
