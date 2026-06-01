# 03 - تدفق البيانات: مسح الكاش، إنشاء نسخة احتياطية، تبديل وضع الصيانة (Data Flow Sequence for Cache Clear, Backup Create, Maintenance Toggle)

<div dir="rtl">

## 1. مسح الكاش (Cache Clear Sequence)

```
المشرف                    SystemManageController          CacheManager               Artisan
  │                                │                          │                        │
  │  POST /admin/system/cache/clear                         │                        │
  │  Headers: Authorization: Bearer JWT                     │                        │
  │──────────────────────────────►│                          │                        │
  │                                │                          │                        │
  │                                │  تحقق من صلاحية المشرف   │                        │
  │                                │  middleware: role:admin  │                        │
  │                                │──────────────────────────│                        │
  │                                │  ✓ مصادقة ناجحة          │                        │
  │                                │                          │                        │
  │                                │  clear()                 │                        │
  │                                │─────────────────────────►│                        │
  │                                │                          │                        │
  │                                │                          │  Artisan::call(         │
  │                                │                          │    'cache:clear')       │
  │                                │                          │────────────────────────►│
  │                                │                          │                        │
  │                                │                          │◄────────────────────────│
  │                                │                          │  ✓ Cache cleared        │
  │                                │                          │                        │
  │                                │                          │  Artisan::call(         │
  │                                │                          │    'config:clear')      │
  │                                │                          │────────────────────────►│
  │                                │                          │                        │
  │                                │                          │◄────────────────────────│
  │                                │                          │  ✓ Config cleared       │
  │                                │                          │                        │
  │                                │                          │  Artisan::call(         │
  │                                │                          │    'route:clear')       │
  │                                │                          │────────────────────────►│
  │                                │                          │                        │
  │                                │                          │◄────────────────────────│
  │                                │                          │  ✓ Route cleared        │
  │                                │                          │                        │
  │                                │  dispatch(new CacheCleared($admin))               │
  │                                │◄─────────────────────────│                        │
  │                                │                          │                        │
  │  { message: "تم مسح جميع       │                          │                        │
  │     أنواع الكاش بنجاح",        │                          │                        │
  │   success: true }              │                          │                        │
  │◄──────────────────────────────│                          │                        │
```

## 2. إنشاء نسخة احتياطية (Backup Create Sequence)

```
المشرف                    SystemManageController          BackupManager             mysqldump
  │                                │                          │                        │
  │  POST /admin/system/backup     │                          │                        │
  │  Headers: Authorization: Bearer JWT                     │                        │
  │──────────────────────────────►│                          │                        │
  │                                │                          │                        │
  │                                │  create()                │                        │
  │                                │─────────────────────────►│                        │
  │                                │                          │                        │
  │                                │                          │  1. توليد اسم الملف     │
  │                                │                          │     backup_2026-05-27_  │
  │                                │                          │     14-30-00.sql.gz     │
  │                                │                          │                        │
  │                                │                          │  2. التحقق من المساحة   │
  │                                │                          │     المتاحة على القرص   │
  │                                │                          │                        │
  │                                │                          │  3. بناء أمر mysqldump  │
  │                                │                          │     مع --single-        │
  │                                │                          │     transaction         │
  │                                │                          │                        │
  │                                │                          │  mysqldump --host=...   │
  │                                │                          │  --user=... --password=│
  │                                │                          │  --single-transaction   │
  │                                │                          │  --databases beza_db    │
  │                                │                          │  --routines --events    │
  │                                │                          │  | gzip > backup.sql.gz │
  │                                │                          │────────────────────────►│
  │                                │                          │                        │
  │                                │                          │◄────────────────────────│
  │                                │                          │  ✓ Backup file created  │
  │                                │                          │                        │
  │                                │                          │  4. التحقق من سلامة     │
  │                                │                          │     الملف (حجم > 0)     │
  │                                │                          │                        │
  │                                │  return BackupResource   │                        │
  │                                │◄─────────────────────────│                        │
  │                                │                          │                        │
  │  dispatch(new BackupCreated($filename, $admin))                            │
  │                                │                          │                        │
  │  { message: "تم إنشاء النسخة   │                          │                        │
  │     الاحتياطية بنجاح",         │                          │                        │
  │   data: { filename: "...",    │                          │                        │
  │          size: 15728640,      │                          │                        │
  │          created_at: "..." }, │                          │                        │
  │   success: true }             │                          │                        │
  │◄──────────────────────────────│                          │                        │
```

## 3. تبديل وضع الصيانة (Maintenance Toggle Sequence)

```
المشرف                    SystemManageController         MaintenanceManager         Artisan
  │                                │                          │                        │
  │  POST /admin/system/maintenance                        │                        │
  │  Body: { enabled: true,                                │                        │
  │          message: "نحن تحت الصيانة حالياً",            │                        │
  │          retry: 60 }                                  │                        │
  │──────────────────────────────►│                          │                        │
  │                                │                          │                        │
  │                                │  التحقق من البيانات:     │                        │
  │                                │  enabled (required, bool)│                        │
  │                                │  message (string)        │                        │
  │                                │  retry (int, minutes)    │                        │
  │                                │                          │                        │
  │                                │  toggled(enabled,        │                        │
  │                                │    message, retry)       │                        │
  │                                │─────────────────────────►│                        │
  │                                │                          │                        │
  │                                │                          │  if enabled:            │
  │                                │                          │  Artisan::call('down',  │
  │                                │                          │    ['--message' => ..., │
  │                                │                          │     '--retry' => 60])   │
  │                                │                          │────────────────────────►│
  │                                │                          │                        │
  │                                │                          │◄────────────────────────│
  │                                │                          │  ✓ Application is down  │
  │                                │                          │                        │
  │                                │                          │  else:                  │
  │                                │                          │  Artisan::call('up')    │
  │                                │                          │────────────────────────►│
  │                                │                          │                        │
  │                                │                          │◄────────────────────────│
  │                                │                          │  ✓ Application is up    │
  │                                │                          │                        │
  │                                │  dispatch(new           │                        │
  │                                │    MaintenanceModeChanged│                        │
  │                                │    ($enabled, $admin))   │                        │
  │                                │                          │                        │
  │  { message: "تم تفعيل وضع     │                          │                        │
  │     الصيانة",                 │                          │                        │
  │   data: { maintenance_mode:   │                          │                        │
  │           true },             │                          │                        │
  │   success: true }             │                          │                        │
  │◄──────────────────────────────│                          │                        │
```

## مبادئ تدفق البيانات

1. **التحقق أولاً**: يتم التحقق من صحة البيانات وصلاحية المشرف قبل أي عملية
2. **التنفيذ المتسلسل**: كل خطوة تعتمد على نجاح الخطوة السابقة
3. **التسجيل**: كل عملية يتم تسجيلها في سجل التدقيق
4. **الإشعار**: يتم إرسال إشعار عند اكتمال العمليات غير المتزامنة
5. **التغذية الراجعة**: يتم إرجاع رسالة واضحة بالعربية للمستخدم

</div>
