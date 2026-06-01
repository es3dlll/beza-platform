# فهرس - المشاركة في صفقة

```
D2-invest-deal/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md              # فكرة العمل وسيناريو المستخدم
├── 02-architecture.md               # مكان العملية في النظام
├── 03-data-flow-sequence.md         # تدفق البيانات الكامل (Sequence Diagram)
├── 04-database-relationships.md     # علاقات الجداول + ER
├── 05-migrations.md                 # كود الميغريشن الكامل
├── 06-eloquent-models.md            # الموديلز مع العلاقات وال casts
├── 07-validation-rules.md           # كل قواعد التحقق + أسبابها
├── 08-controller-full-code.md       # المتحكم الكامل مع كل سطر
├── 09-service-layer-deal.md         # DealService كامل
├── 10-service-layer-invest.md       # InvestService كامل
├── 11-events-and-listeners.md       # InvestmentMade + مستمعيه
├── 12-notification-system.md        # FCM + إشعارات للمستثمر
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
| اسم العملية | المشاركة في صفقة (استثمار) |
| الأولوية | P1 (عالية) |
| API | `POST /api/v1/deals/{id}/invest` |
| Controller | `DealController@invest` |
| Service | `DealService` / `InvestService` |
| Event | `InvestmentMade` |
| Listener | `SendInvestmentNotification` |
| DB Tables | deals, deal_investments, wallets, transactions |
| الحد الأدنى | 10 USD |
| Flutter Screen | `DealInvestScreen` |
| React Page | `DealInvestPage` |
