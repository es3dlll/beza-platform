# SY4 - إعدادات النظام (System Settings)

## الوصف
نظام مركزي لإدارة جميع إعدادات المنصة مخزّنة في قاعدة البيانات مع كاش Redis.

## نقاط API
- GET /admin/system/settings — جميع الإعدادات
- PUT /admin/system/settings/general — الإعدادات العامة
- PUT /admin/system/settings/features — تفعيل/تعطيل الميزات
- PUT /admin/system/settings/fees — رسوم الخدمات
- PUT /admin/system/settings/limits — الحدود القصوى
- PUT /admin/system/settings/exchange — سعر الصرف
- PUT /admin/system/settings/security — الأمان
- PUT /admin/system/settings/notifications — الإشعارات
- PUT /admin/system/settings/mail — البريد الإلكتروني
- PUT /admin/system/settings/maintenance — الصيانة

## الأولوية: P0 (حرجة)

## الملفات
- tasks/examples/19-system-complete/SY4-settings/ (21 ملف)
