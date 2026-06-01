# فهرس - تشغيل البيئة المحلية (Localhost Setup)

```
I1-localhost-setup/
├── 00-index.md                      ← أنت هنا
├── 01-business-idea.md              # أهمية البيئة المحلية
├── 02-architecture.md               # بنية المنصة الكاملة
├── 03-setup-workflow-sequence.md    # تدفق خطوات الإعداد
├── 04-environment-requirements.md   # المتطلبات والأدوات
├── 05-configuration-files.md        # ملفات الإعداد (.env, config)
├── 06-shell-scripts.md              # سكريبتات التشغيل الآلي
├── 07-verification-scripts.md       # سكريبتات التحقق من الصحة
├── 08-commands-reference.md         # دليل أوامر artisan, npm, flutter
├── 09-troubleshooting-guide.md      # حل المشكلات الشائعة
├── 10-laragon-setup.md               # إعداد Laragon
├── 11-redis-and-queue.md            # Redis + Queue Worker
├── 12-nginx-configuration.md        # إعداد Nginx للوكيل العكسي
├── 13-error-solutions.md            # حلول أخطاء الإعداد
├── 14-performance-tuning.md         # تحسين الأداء المحلي
├── 15-api-endpoints.md              # قائمة نقاط API
├── 16-flutter-build-config.md       # إعداد بناء Flutter
├── 17-frontend-build-config.md      # إعداد بناء الواجهات
├── 18-testing-setup.md              # إعداد بيئة الاختبارات
├── 19-edge-cases-troubleshoot.md    # حالات الحافة
└── 20-security-checklist.md         # قائمة التحقق الأمني
```

## ملخص العملية
| العنصر | القيمة |
|--------|--------|
| اسم العملية | تشغيل البيئة المحلية |
| الأولوية | P0 (حرجة) |
| المتطلبات | PHP 8.2+, Composer, MySQL 8.0, Redis, Node 18+, Flutter SDK |
| المشاريع | Laravel API, Admin Dashboard, User Frontend, Landing Page, Flutter App |
| المنافذ | 8000 (API), 5173 (Admin), 5174 (User), 3000 (Landing) |
