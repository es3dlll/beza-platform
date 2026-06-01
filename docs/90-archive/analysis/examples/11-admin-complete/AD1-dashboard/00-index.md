# فهرس - لوحة تحكم المشرف (Admin Dashboard)

```
AD1-dashboard/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md              # فكرة لوحة التحكم
├── 02-architecture.md               # مكانها في النظام
├── 03-data-flow-sequence.md         # تدفق البيانات
├── 04-database-relationships.md     # علاقات الجداول + ER
├── 05-migrations.md                 # كود الميغريشن
├── 06-eloquent-models.md            # الموديلز
├── 07-validation-rules.md           # قواعد التحقق
├── 08-controller-full-code.md       # المتحكم الكامل
├── 09-service-layer-stats.md        # StatsService
├── 10-service-layer-cache.md        # CacheService
├── 11-events-and-listeners.md       # الأحداث
├── 12-notification-system.md        # إشعارات المشرف
├── 13-exception-handling.md         # الاستثناءات
├── 14-database-transactions-acid.md # ACID
├── 15-api-specification.md          # OpenAPI
├── 16-flutter-implementation.md     # Flutter UI
├── 17-react-implementation.md       # React Admin
├── 18-testing-complete.md           # الاختبارات
└── 20-security-audit.md             # الأمان
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | لوحة تحكم المشرف - الإحصائيات |
| الأولوية | P0 (حرجة) |
| API | `GET /api/v1/admin/dashboard` |
| Controller | `AdminDashboardController@stats` |
| Service | `DashboardStatsService` / `CacheService` |
| DB Tables | users, wallets, transactions, merchants, agents |
| تحديث تلقائي | كل 30 ثانية (refetchInterval) |
| Cache | بيانات محسّمة لمدة 5 دقائق |
| React Page | `AdminDashboard` |
