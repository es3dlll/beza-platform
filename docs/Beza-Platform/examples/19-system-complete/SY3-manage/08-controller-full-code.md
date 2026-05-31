# 08 - كود المتحكم الكامل: SystemManageController مع جميع النقاط النهائية (Full Controller Code with ALL Endpoints)

<div dir="rtl">

## الكود الكامل للمتحكم

```php
<?php
// app/Http/Controllers/Api/SystemManageController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\System\CacheManager;
use App\Services\System\LogManager;
use App\Services\System\QueueManager;
use App\Services\System\MaintenanceManager;
use App\Services\System\BackupManager;
use App\Services\System\SystemInfoCollector;
use App\Events\System\CacheCleared;
use App\Events\System\BackupCreated;
use App\Events\System\MaintenanceModeChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * متحكم إدارة النظام
 * 
 * يوفر واجهة API لإدارة جميع جوانب النظام:
 * الكاش، السجلات، قائمة الانتظار، الصيانة، النسخ الاحتياطي
 */
class SystemManageController extends Controller
{
    public function __construct(
        private CacheManager         $cacheManager,
        private LogManager           $logManager,
        private QueueManager         $queueManager,
        private MaintenanceManager   $maintenanceManager,
        private BackupManager        $backupManager,
        private SystemInfoCollector  $infoCollector,
    ) {}

    /**
     * مسح جميع أنواع الذاكرة المؤقتة (Cache Clear)
     * يقوم بمسح: config cache, route cache, view cache, application cache
     */
    public function cacheClear(): JsonResponse
    {
        // محاولة مسح جميع أنواع الكاش
        $result = $this->cacheManager->clear();

        // تسجيل حدث مسح الكاش للإشعارات والتدقيق
        event(new CacheCleared(
            admin: auth()->user(),
            results: $result
        ));

        return response()->json([
            'success' => true,
            'message' => 'تم مسح جميع أنواع الكاش بنجاح', // تم مسح الكاش
            'data'    => $result,
        ]);
    }

    /**
     * تحسين الكاش (Cache Optimize)
     * يقوم بتخزين الإعدادات والمسارات لتحسين الأداء
     */
    public function cacheOptimize(): JsonResponse
    {
        // تحسين الكاش: تخزين الإعدادات والمسارات
        $result = $this->cacheManager->optimize();

        return response()->json([
            'success' => true,
            'message' => 'تم تحسين الكاش بنجاح', // تم تحسين الكاش
            'data'    => $result,
        ]);
    }

    /**
     * عرض أحدث إدخالات السجل (Log View)
     * يعرض آخر 100 سطر من سجل Laravel
     */
    public function logView(): JsonResponse
    {
        // قراءة آخر 100 سطر من سجل التطبيق
        $logContent = $this->logManager->view();

        return response()->json([
            'success' => true,
            'data'    => [
                'content'  => $logContent,
                'lines'    => substr_count($logContent, "\n") + 1,
                'file'     => 'laravel.log',
            ],
        ]);
    }

    /**
     * مسح ملفات السجل (Log Clear)
     * يقوم بحذف جميع ملفات السجل القديمة
     */
    public function logClear(): JsonResponse
    {
        // مسح جميع ملفات السجل
        $deletedCount = $this->logManager->clear();

        return response()->json([
            'success' => true,
            'message' => "تم مسح {$deletedCount} ملف سجل بنجاح", // تم مسح ملفات السجل
            'data'    => ['deleted_files' => $deletedCount],
        ]);
    }

    /**
     * حالة عمال قائمة الانتظار (Queue Status)
     * يعرض حالة عمال قائمة الانتظار وعدد المهام المعلقة
     */
    public function queueStatus(): JsonResponse
    {
        // الحصول على حالة قائمة الانتظار
        $status = $this->queueManager->status();

        return response()->json([
            'success' => true,
            'data'    => $status,
        ]);
    }

    /**
     * إعادة تشغيل عمال قائمة الانتظار (Queue Restart)
     * يقوم بإعادة تشغيل جميع عمال قائمة الانتظار
     */
    public function queueRestart(): JsonResponse
    {
        // إعادة تشغيل عمال قائمة الانتظار
        $this->queueManager->restart();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تشغيل عمال قائمة الانتظار بنجاح', // تم إعادة تشغيل العمال
        ]);
    }

    /**
     * عرض المهام المجدولة (Schedule List)
     * يعرض قائمة المهام المجدولة في التطبيق
     */
    public function scheduleList(): JsonResponse
    {
        // الحصول على قائمة المهام المجدولة
        $schedule = $this->queueManager->scheduleList();

        return response()->json([
            'success' => true,
            'data'    => $schedule,
        ]);
    }

    /**
     * تبديل وضع الصيانة (Maintenance Toggle)
     * تفعيل أو تعطيل وضع الصيانة للتطبيق
     */
    public function maintenanceToggle(Request $request): JsonResponse
    {
        // التحقق من صحة البيانات المرسلة
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',                              // حالة التفعيل (مطلوب)
            'message' => 'nullable|string|max:500',                      // رسالة الصيانة (اختياري)
            'retry'   => 'nullable|integer|min:1|max:1440',             // دقائق إعادة المحاولة (اختياري)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // تنفيذ عملية تبديل وضع الصيانة
        $result = $this->maintenanceManager->toggle(
            enabled: $request->boolean('enabled'),
            message: $request->input('message'),
            retry: $request->integer('retry')
        );

        // تسجيل حدث تغيير وضع الصيانة
        event(new MaintenanceModeChanged(
            enabled: $request->boolean('enabled'),
            admin: auth()->user()
        ));

        return response()->json($result);
    }

    /**
     * معلومات النظام (System Info)
     * يعرض معلومات عن PHP و Laravel والبيئة
     */
    public function systemInfo(): JsonResponse
    {
        // جمع معلومات النظام
        $info = $this->infoCollector->collect();

        return response()->json([
            'success' => true,
            'data'    => $info,
        ]);
    }

    /**
     * إنشاء نسخة احتياطية لقاعدة البيانات (Backup Create)
     * يقوم بإنشاء نسخة احتياطية كاملة باستخدام mysqldump
     */
    public function backupCreate(): JsonResponse
    {
        // التحقق من عدم وجود عملية نسخ احتياطي قيد التشغيل
        $lockFile = storage_path('app/backups/.backup_lock');
        if (file_exists($lockFile)) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد نسخة احتياطية قيد التشغيل حالياً. الرجاء الانتظار.',
            ], 409);
        }

        try {
            // إنشاء النسخة الاحتياطية
            $backup = $this->backupManager->create();

            // تسجيل حدث إنشاء النسخة الاحتياطية
            event(new BackupCreated(
                filename: $backup['filename'],
                size: $backup['size'],
                admin: auth()->user()
            ));

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء النسخة الاحتياطية بنجاح',
                'data'    => $backup,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل إنشاء النسخة الاحتياطية: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض النسخ الاحتياطية المتاحة (Backup List)
     * يعرض قائمة بجميع ملفات النسخ الاحتياطي
     */
    public function backupList(): JsonResponse
    {
        // الحصول على قائمة النسخ الاحتياطية
        $backups = $this->backupManager->list();

        return response()->json([
            'success' => true,
            'data'    => $backups,
        ]);
    }

    /**
     * استعادة نسخة احتياطية (Backup Restore)
     * استعادة قاعدة البيانات من نسخة احتياطية محددة
     */
    public function backupRestore(string $id, Request $request): JsonResponse
    {
        // التحقق من صحة معرف النسخة الاحتياطية
        $validator = Validator::make(['id' => $id], [
            'id' => ['required', 'string', 'regex:/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'معرف النسخة الاحتياطية غير صالح',
            ], 422);
        }

        // التحقق من وجود الملف
        if (!file_exists(storage_path("app/backups/{$id}"))) {
            return response()->json([
                'success' => false,
                'message' => 'ملف النسخة الاحتياطية غير موجود',
            ], 404);
        }

        // تأكيد الاستعادة
        $confirmValidator = Validator::make($request->all(), [
            'confirm' => 'required|accepted',
        ]);

        if ($confirmValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'الرجاء تأكيد عملية الاستعادة. سيتم فقدان أي تغييرات منذ آخر نسخة احتياطية.',
            ], 422);
        }

        try {
            $result = $this->backupManager->restore($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل استعادة النسخة الاحتياطية: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف نسخة احتياطية (Backup Delete)
     */
    public function backupDelete(string $id): JsonResponse
    {
        // التحقق من صحة المعرف
        $validator = Validator::make(['id' => $id], [
            'id' => ['required', 'string', 'regex:/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'معرف النسخة الاحتياطية غير صالح',
            ], 422);
        }

        try {
            $result = $this->backupManager->delete($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل حذف النسخة الاحتياطية: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض ملفات السجل مع الأحجام (Logs List)
     */
    public function logsList(): JsonResponse
    {
        $logs = $this->logManager->list();

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ]);
    }

    /**
     * عرض ملف سجل محدد (Logs Show)
     */
    public function logsShow(string $file): JsonResponse
    {
        // التحقق من اسم الملف لمنع هجمات directory traversal
        $validator = Validator::make(['file' => $file], [
            'file' => ['required', 'string', 'regex:/^[a-zA-Z0-9_\-]+\.log$/', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اسم الملف غير صالح',
            ], 422);
        }

        try {
            $content = $this->logManager->show($file);
            return response()->json([
                'success' => true,
                'data'    => $content,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل قراءة ملف السجل: ' . $e->getMessage(),
            ], 500);
        }
    }
}
```

## ملخص نقاط النهاية

| الدالة | المسار | HTTP | الوصف |
|--------|-------|------|-------|
| cacheClear | /admin/system/cache/clear | POST | مسح جميع أنواع الكاش |
| cacheOptimize | /admin/system/cache/optimize | POST | تحسين الكاش |
| logView | /admin/system/log/view | POST | عرض آخر 100 سطر من السجل |
| logClear | /admin/system/log/clear | POST | مسح ملفات السجل |
| queueStatus | /admin/system/queue/status | GET | حالة قائمة الانتظار |
| queueRestart | /admin/system/queue/restart | POST | إعادة تشغيل عمال قائمة الانتظار |
| scheduleList | /admin/system/schedule | GET | عرض المهام المجدولة |
| maintenanceToggle | /admin/system/maintenance | POST | تبديل وضع الصيانة |
| systemInfo | /admin/system/info | GET | معلومات النظام |
| backupCreate | /admin/system/backup | POST | إنشاء نسخة احتياطية |
| backupList | /admin/system/backup/list | GET | عرض النسخ الاحتياطية |
| backupRestore | /admin/system/backup/{id}/restore | POST | استعادة نسخة احتياطية |
| backupDelete | /admin/system/backup/{id} | DELETE | حذف نسخة احتياطية |
| logsList | /admin/system/logs | GET | عرض ملفات السجل |
| logsShow | /admin/system/logs/{file} | GET | عرض ملف سجل محدد |

</div>
