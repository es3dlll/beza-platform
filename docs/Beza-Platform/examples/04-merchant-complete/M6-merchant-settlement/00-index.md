# فهرس - تسوية مدفوعات التاجر (Merchant Settlement)

```
M6-merchant-settlement/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md
├── 02-architecture.md
├── 03-data-flow-sequence.md
├── 04-database-relationships.md
├── 05-migrations.md
├── 06-eloquent-models.md
├── 07-validation-rules.md
├── 08-controller-full-code.md
├── 09-service-layer-core.md
├── 10-service-layer-aux.md
├── 11-events-and-listeners.md
├── 12-notification-system.md
├── 13-exception-handling.md
├── 14-database-transactions-acid.md
├── 15-api-specification.md
├── 16-flutter-implementation.md
├── 17-react-implementation.md
├── 18-testing-complete.md
├── 19-edge-cases.md
└── 20-security-audit.md
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | تسوية رصيد التاجر وتحويل بنكي |
| الأولوية | P1 (عالية) |
| API | `POST /api/v1/merchant/settlement`, `GET /api/v1/merchant/settlement/history` |
| Controller | `SettlementController` |
| Service | `SettlementService` |
| Event | `SettlementRequested`, `SettlementCompleted` |
| DB Tables | merchant_settlements, merchant_wallets |
| Flutter | `SettlementScreen` |
| React | `SettlementPage` |
