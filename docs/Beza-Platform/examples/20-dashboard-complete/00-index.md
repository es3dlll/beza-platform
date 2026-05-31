# فهرس - لوحة المعلومات الرئيسية (Dashboard / Home)

```
20-dashboard-complete/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md              # فكرة العمل وسيناريو المستخدم
├── 02-architecture.md               # مكان الصفحة في تطبيق React
├── 03-data-flow-sequence.md         # تدفق البيانات (API → State → UI)
├── 04-component-relationships.md    # علاقات المكونات وشجرة الـ DOM
├── 05-styles-theme.md               # نظام الألوان والثيم (Gold Theme)
├── 06-responsive-layout.md          # التجاوب (Mobile → Desktop)
├── 07-validation-rules.md           # قواعد التحقق من المدخلات
├── 08-API-integration.md            # نقاط الـ API المستخدمة
├── 09-state-management.md           # إدارة الحالة (State Hooks)
├── 10-auth-guards-middleware.md     # حماية الصفحة والمصادقة
├── 11-events-and-listeners.md       # الأحداث والمؤثرات
├── 12-notification-system.md        # نظام الإشعارات والقائمة المنسدلة
├── 13-exception-handling.md         # معالجة الأخطاء
├── 14-performance-optimization.md   # تحسين الأداء
├── 15-exchange-widget.md            # واجهة الصرف الفوري
├── 16-transaction-modal.md          # شاشة المعاملات والفاتورة
├── 17-bottom-sheets.md              # اللوحات السفلية (Send/Receive)
├── 18-testing-complete.md           # الاختبارات
├── 19-edge-cases.md                 # حالات الحافة
└── 20-security-audit.md             # أمان الصفحة
```

## ملخص العملية

| العنصر | القيمة |
|--------|--------|
| اسم الصفحة | Dashboard (لوحة المعلومات الرئيسية) |
| الأولوية | P0 (حرجة) — الصفحة الرئيسية |
| المسار | `/` |
| نوع الصفحة | SPA (Single Page Application) |
| Framework | React 19 + Vite |
| التصميم | Bento Grid (1→4 أعمدة متجاوبة) |
| الألوان | كحلي #080c1a / #0f1730، ذهبي #F5A623 |
| اللغة | RTL العربية |
| API Calls | `/wallet/balance`, `/auth/me`, `/wallet/rates`, `/transfer` |
| Modals | Bottom Sheet (Send + Receive) |
