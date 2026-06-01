# فهرس - SY1: تنصيب النظام لأول مرة (First-Run Installer)

```
SY1-install/
├── 00-index.md                     ← أنت هنا
├── 01-business-idea.md             # فكرة العمل وسيناريو المستخدم
├── 02-architecture.md              # بنية النظام بالكامل
├── 03-installation-flow.md         # تدفق التنصيب — 6 خطوات (Sequence Diagram)
├── 04-database-relationships.md    # لا جداول خاصة بالمثبت
├── 05-migrations.md                # لا ميجريشن خاص بالمثبت
├── 06-eloquent-models.md           # لا موديل — Installer ليس جدولاً
├── 07-validation-rules.md          # التحقق من مدخلات المستخدم
├── 08-controller-full-code.md      # InstallerController كامل
├── 09-service-layer-core.md        # RequirementChecker (فحص المتطلبات)
├── 10-service-layer-aux.md         # EnvironmentConfigurator (.env)
├── 11-events-and-listeners.md      # InstallationCompleted event
├── 12-notification-system.md       # لا إشعارات — رسائل حالة مباشرة
├── 13-exception-handling.md        # معالجة الأخطاء مع التراجع الآمن
├── 14-database-transactions-acid.md # لا معاملات — كل خطوة مستقلة
├── 15-api-specification.md         # OpenAPI لجميع نقاط API
├── 16-flutter-implementation.md    # لا حاجة — التنصيب Web فقط
├── 17-react-implementation.md      # React InstallerWizard كامل
├── 18-testing-complete.md          # اختبارات لجميع الخطوات
├── 19-edge-cases.md                # حالات الحافة
└── 20-security-audit.md            # أمان المثبت
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | تنصيب النظام لأول مرة |
| الأولوية | P0 (حرجة — لا يمكن تشغيل المنصة بدونها) |
| API | `GET /install`, `POST /install/*` |
| Controller | `InstallerController` |
| Services | `RequirementChecker`, `EnvironmentConfigurator` |
| Event | `InstallationCompleted` |
| DB Tables | لا يوجد (يستخدم المثبت جداول التطبيق بعد إنشائها) |
| عدد الخطوات | 6 |
| واجهة المستخدم | React SPA (مرة واحدة فقط) |
| حالة ما بعد التنصيب | مُعطَّل تلقائياً — لا يمكن إعادة التشغيل |
| شرط العمل | ملف `.env` غير موجود أو `INSTALLER_LOCKED=false` |
