# SY1 - نظام التنصيب (First-Run Installer)

## الوصف
مثبت ويب يعمل مرة واحدة عند النشر الأولي. يتحقق من المتطلبات، يهيئ .env، يشغّل الترحيلات، وينشئ أول مشرف.

## نقاط API
- GET /install — عرض صفحة الترحيب
- POST /install/requirements — التحقق من متطلبات PHP
- POST /install/database — اختبار اتصال MySQL
- POST /install/env — حفظ إعدادات .env
- POST /install/migrate — تشغيل الترحيلات والبذور
- POST /install/admin — إنشاء أول مشرف
- POST /install/complete — إكمال التنصيب

## الأولوية: P0 (حرجة - مطلوبة قبل أي عملية)

## الملفات
- tasks/examples/19-system-complete/SY1-install/ (21 ملف)
