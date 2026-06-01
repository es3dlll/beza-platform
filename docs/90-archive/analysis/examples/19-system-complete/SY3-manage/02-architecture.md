# 02 - الرسم المعماري: SystemManageController ← مديري الخدمات (Architecture: SystemManageController → Service Managers)

<div dir="rtl">

## مخطط العمارة

```
┌─────────────────────────────────────────────────────────────────────┐
│                         طبقة API (Routes)                           │
│  POST GET DELETE /admin/system/*                                    │
│  auth:api + admin middleware                                        │
└─────────────────────────┬───────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    SystemManageController                           │
│  ▸ cacheClear()     ▸ cacheOptimize()      ▸ logView()             │
│  ▸ logClear()       ▸ queueStatus()         ▸ queueRestart()       │
│  ▸ scheduleList()   ▸ maintenanceToggle()   ▸ systemInfo()         │
│  ▸ backupCreate()   ▸ backupList()          ▸ backupRestore()      │
│  ▸ backupDelete()   ▸ logsList()            ▸ logsShow()           │
└──────┬──────────────┬──────────────┬──────────────┬─────────────────┘
       │              │              │              │
       ▼              ▼              ▼              ▼
┌────────────┐ ┌──────────┐ ┌──────────────┐ ┌──────────────┐
│CacheManager│ │LogManager│ │QueueManager  │ │MaintenanceMan│
│            │ │          │ │              │ │              │
│• clear()   │ │• view()  │ │• status()    │ │• enable()    │
│• optimize()│ │• clear() │ │• restart()   │ │• disable()   │
│            │ │• list()  │ │              │ │• isActive()  │
│            │ │• show()  │ │              │ │              │
└──────┬─────┘ └────┬─────┘ └──────┬───────┘ └──────┬───────┘
       │            │              │                │
       ▼            ▼              ▼                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       BackupManager                                 │
│  ▸ create()  → mysqldump wrapper                                   │
│  ▸ list()    → scan backup directory                               │
│  ▸ restore() → mysql import                                        │
│  ▸ delete()  → unlink file                                         │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                   SystemInfoCollector                               │
│  ▸ phpInfo()       ▸ laravelVersion()   ▸ environment()            │
│  ▸ diskUsage()     ▸ memoryUsage()      ▸ uptime()                 │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      الطبقة التحتية (Infrastructure)                 │
│                                                                     │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌───────────────┐ │
│  │Artisan     │  │mysqldump   │  │  File      │  │  Queue        │ │
│  │Commands    │  │mysql       │  │  System    │  │  Workers      │ │
│  └────────────┘  └────────────┘  └────────────┘  └───────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

## تدفق المسؤولية (Responsibility Flow)

### الطبقة الأولى: المسارات (Routes)
```
/routes/api.php
Route::group(['prefix' => 'admin/system', 'middleware' => ['auth:api', 'role:admin']], function () {
    Route::post('/cache/clear',   [SystemManageController::class, 'cacheClear']);
    Route::post('/cache/optimize',[SystemManageController::class, 'cacheOptimize']);
    Route::post('/log/view',      [SystemManageController::class, 'logView']);
    Route::post('/log/clear',     [SystemManageController::class, 'logClear']);
    Route::get('/queue/status',   [SystemManageController::class, 'queueStatus']);
    Route::post('/queue/restart', [SystemManageController::class, 'queueRestart']);
    Route::get('/schedule',       [SystemManageController::class, 'scheduleList']);
    Route::post('/maintenance',   [SystemManageController::class, 'maintenanceToggle']);
    Route::get('/info',           [SystemManageController::class, 'systemInfo']);
    Route::post('/backup',        [SystemManageController::class, 'backupCreate']);
    Route::get('/backup/list',    [SystemManageController::class, 'backupList']);
    Route::post('/backup/{id}/restore', [SystemManageController::class, 'backupRestore']);
    Route::delete('/backup/{id}', [SystemManageController::class, 'backupDelete']);
    Route::get('/logs',           [SystemManageController::class, 'logsList']);
    Route::get('/logs/{file}',    [SystemManageController::class, 'logsShow']);
});
```

### الطبقة الثانية: المتحكم (Controller)
SystemManageController يستقبل الطلبات، يتحقق من الصلاحيات، ويوجه الطلبات إلى مديري الخدمات المناسبين.

### الطبقة الثالثة: مديري الخدمات (Service Managers)
كل مدير خدمة مسؤول عن عملية محددة. يقومون بتنفيذ الأوامر الفعلية عبر Artisan أو exec أو عمليات الملفات.

### الطبقة الرابعة: البنية التحتية (Infrastructure)
- **Artisan**: تنفيذ أوامر Laravel مثل `cache:clear`, `queue:restart`, `down`, `up`
- **mysqldump**: إنشاء نسخ احتياطية لقاعدة البيانات
- **نظام الملفات**: قراءة وكتابة الملفات (السجلات، النسخ الاحتياطية)
- **Queue Workers**: إدارة عمال قائمة الانتظار

## قرارات معمارية رئيسية

| القرار | السبب |
|--------|-------|
| استخدام Artisan بدلاً من exec المباشر | يوفر Artisan بيئة آمنة ومنضبطة لتنفيذ أوامر Laravel |
| نظام الملفات لتخزين النسخ الاحتياطية | حل بسيط وفعال، يمكن التوسع إلى S3 لاحقاً |
| فصل كل مدير خدمة إلى كلاس مستقل | قابلية الاختبار (Testability)، فصل المسؤوليات (SRP) |
| استخدام الأحداث (Events) للإشعارات | فصل منطق الإشعارات عن منطق العمل |
| التحقق من الصلاحيات على مستوى المسار | أمان متعدد الطبقات، تقليل التكرار في الكود |

</div>
