# فهرس - الفوترة المتكررة (Merchant Recurring)

```
M5-merchant-recurring/
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
| اسم العملية | الفوترة المتكررة والاشتراكات |
| الأولوية | P2 (متوسطة) |
| API | `POST /api/v1/merchant/subscriptions`, `GET /api/v1/merchant/subscriptions` |
| Controller | `SubscriptionController` |
| Service | `SubscriptionService` / `RecurringBillingService` |
| Event | `SubscriptionCreated`, `SubscriptionChargeCompleted` |
| DB Tables | merchant_subscriptions, subscription_charges |
| Flutter | `SubscriptionScreen` |
| React | `SubscriptionPage` |
