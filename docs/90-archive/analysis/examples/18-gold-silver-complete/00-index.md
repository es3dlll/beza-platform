# فهرس - الذهب والفضة مدخرات (Gold & Silver Savings)

```
18-gold-silver-complete/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md              # فكرة العمل وسيناريو المستخدم
├── 02-architecture.md               # مكان العملية في النظام
├── 03-data-flow-sequence.md         # تدفق البيانات الكامل (Sequence Diagram)
├── 04-database-relationships.md     # علاقات الجداول + ER
├── 05-migrations.md                 # كود الميغريشن الكامل
├── 06-eloquent-models.md            # الموديلز مع العلاقات والأكسسورز
├── 07-validation-rules.md           # كل قواعد التحقق + أسبابها
├── 08-controller-full-code.md       # المتحكم الكامل مع كل سطر
├── 09-service-layer-wallet.md       # WalletService للتكامل
├── 10-service-layer-core.md         # CommodityService كامل
├── 11-events-and-listeners.md       # أحداث: GoldPurchased, GoldSold, PriceAlert
├── 12-notification-system.md        # FCM + SMS + Email للإشعارات
├── 13-exception-handling.md         # كل الاستثناءات المخصصة
├── 14-database-transactions-acid.md # ACID + الأقفال + Deadlock Prevention
├── 15-api-specification.md          # OpenAPI 3.0 كامل
├── 16-flutter-implementation.md     # Flutter UI + BLoC + Repository
├── 17-react-implementation.md       # React UI + Hooks + API
├── 18-testing-complete.md           # كل الاختبارات (PHPUnit)
├── 19-edge-cases.md                 # 15+ حالة حافة وسيناريوهات خطأ
└── 20-security-audit.md             # أمان العملية خطوة بخطوة
```

## ملخص العملية

| العنصر | القيمة |
|--------|--------|
| اسم العملية | شراء وبيع الذهب والفضة (Gold & Silver Savings) |
| كود العملية | G1 |
| الأولوية | P1 (عالية) |
| API | `POST /api/v1/commodity/buy` — `POST /api/v1/commodity/sell` |
| Controller | `CommodityController` |
| Core Service | `CommodityService` |
| Wallet Service | `WalletService` |
| Price Feed | `PriceFeedProvider` (XAU/USD, XAG/USD) |
| Events | `GoldPurchased`, `GoldSold`, `PriceAlertTriggered` |
| Tables | commodity_prices, commodity_holdings, commodity_transactions, commodity_orders |
| Flutter Screen | `GoldScreen` + `GoldBloc` |
| React Page | `GoldPage` + `useGold Hook` |
| هامش الربح | 1-2% Premium (رسوم الوساطة) |
| الحد الأدنى | 0.1 جرام |
| فترة الاحتفاظ الدنيا | 24 ساعة |
