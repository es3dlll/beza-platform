# فهرس - الموافقة على التجار والوكلاء (Approval)

```
AD3-merchant-agent-approval/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md              # فكرة الموافقة
├── 02-architecture.md               # مكانها في النظام
├── 03-data-flow-sequence.md         # تدفق البيانات
├── 04-database-relationships.md     # علاقات الجداول
├── 05-migrations.md                 # كود الميغريشن
├── 06-eloquent-models.md            # الموديلز
├── 07-validation-rules.md           # قواعد التحقق
├── 08-controller-full-code.md       # المتحكم الكامل
├── 09-service-layer-approval.md     # ApprovalService
├── 10-service-layer-notification.md # إشعارات الموافقة
├── 11-events-and-listeners.md       # الأحداث
├── 12-notification-system.md        # إشعارات
├── 13-exception-handling.md         # الاستثناءات
├── 14-database-transactions-acid.md # ACID
├── 15-api-specification.md          # OpenAPI
├── 16-flutter-implementation.md     # Flutter UI
├── 17-react-implementation.md       # React Admin
├── 18-testing-complete.md           # الاختبارات
├── 19-edge-cases.md                 # حالات الحافة
└── 20-security-audit.md             # الأمان
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | الموافقة على التجار والوكلاء |
| الأولوية | P0 (حرجة) |
| API | GET + POST /api/v1/admin/merchants, /agents |
| Controller | `MerchantApprovalController` / `AgentApprovalController` |
| Service | `MerchantApprovalService` / `AgentApprovalService` |
| DB Tables | users, merchants, agents, merchant_documents |
| سير العمل | عرض → مراجعة → موافقة/رفض → إشعار |
