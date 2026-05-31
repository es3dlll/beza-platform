# SY3 - إدارة النظام (System Management)

## الوصف
أدوات إدارة النظام عبر API: إدارة الكاش، السجلات، النسخ الاحتياطي، وضع الصيانة.

## نقاط API
- POST /admin/system/cache/clear — مسح الكاش
- POST /admin/system/cache/optimize — تحسين الكاش
- POST /admin/system/log/view — عرض السجلات
- POST /admin/system/log/clear — مسح السجلات
- GET /admin/system/queue/status — حالة العمال
- POST /admin/system/queue/restart — إعادة تشغيل العمال
- GET /admin/system/schedule — المهام المجدولة
- POST /admin/system/maintenance — وضع الصيانة
- POST /admin/system/backup — نسخ احتياطي
- GET /admin/system/backup/list — قائمة النسخ
- POST /admin/system/backup/{id}/restore — استعادة
- DELETE /admin/system/backup/{id} — حذف نسخة

## الأولوية: P1

## الملفات
- tasks/examples/19-system-complete/SY3-manage/ (21 ملف)
