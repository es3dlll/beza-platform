# فهرس - AG4: خريطة الوكلاء (Agent Map)

```
AG4-agent-map/
├── 00-index.md                     ← أنت هنا
├── 01-business-idea.md             # فكرة العمل وسيناريو المستخدم
├── 02-architecture.md              # مكان العملية في النظام
├── 03-data-flow-sequence.md        # تدفق البيانات الكامل (Sequence Diagram)
├── 04-database-relationships.md    # علاقات الجداول + ER
├── 05-migrations.md                # كود الميغريشن الكامل
├── 06-eloquent-models.md           # الموديلز مع العلاقات وال casts
├── 07-validation-rules.md          # كل قواعد التحقق + أسبابها
├── 08-controller-full-code.md      # المتحكم الكامل مع كل سطر
├── 09-service-layer-core.md        # AgentMapService كامل
├── 10-service-layer-wallet.md      # WalletService للتكامل
├── 11-events-and-listeners.md      # الأحداث والمستمعين
├── 12-notification-system.md       # FCM + SMS + Email
├── 13-exception-handling.md        # كل الاستثناءات ومعالجتها
├── 14-database-transactions-acid.md # ACID + الأقفال
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
| اسم العملية | خريطة الوكلاء |
| الأولوية | P2 (عادية) |
| API | `GET /api/v1/agent/nearby` |
| Controller | `AgentMapController@nearby` |
| Service | `AgentMapService` |
| Event | — (لا يوجد) |
| DB Tables | agents |
| Flutter Screen | `AgentMapScreen` (Google Maps) |
| React Page | `AgentMapPage` |
