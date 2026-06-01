# 07 - قواعد التحقق: صلاحية المشرف والتحقق من معرف النسخة الاحتياطية (Validation Rules: Admin Role Validation & Backup ID Validation)

<div dir="rtl">

## نظرة عامة

نظام التحقق في SY3-manage يعتمد على طبقتين:
1. **طبقة المصادقة**: التحقق من هوية المستخدم عبر JWT
2. **طبقة الصلاحيات**: التحقق من دور المستخدم (admin)
3. **طبقة البيانات**: التحقق من صحة البيانات المرسلة في الطلب

## 1. طبقة المصادقة (Authentication Layer)

```php
// routes/api.php
// استخدام middleware auth:api لمصادقة JWT
Route::middleware(['auth:api'])->group(function () {
    // جميع مسارات الإدارة تتطلب مصادقة
});
```

### تكوين JWT في config/jwt.php
```php
<?php
/*
 * إعدادات JWT للمصادقة
 * يستخدم tymon/jwt-auth بدلاً من Sanctum
 */
return [
    'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],
    'ttl' => env('JWT_TTL', 60), // 60 دقيقة
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 14 يوماً
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
];
```

## 2. طبقة الصلاحيات (Authorization Layer)

### Middleware مخصص للتحقق من دور المشرف

```php
<?php
// app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * معالجة الطلب والتحقق من صلاحية المشرف
     * 
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من وجود مستخدم في الطلب بعد مصادقة JWT
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح به. يجب تسجيل الدخول أولاً.',
            ], 401);
        }

        // التحقق من أن المستخدم له دور admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح به. هذا الإجراء يتطلب صلاحيات المشرف.',
            ], 403);
        }

        return $next($request);
    }
}
```

### تسجيل Middleware
```php
// bootstrap/app.php
use App\Http\Middleware\AdminMiddleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => AdminMiddleware::class,
    ]);
})
```

### تطبيق Middleware على المسارات
```php
// routes/api.php
Route::middleware(['auth:api', 'admin'])->prefix('admin/system')->group(function () {
    // جميع مسارات الإدارة هنا
});
```

## 3. طبقة البيانات (Data Validation)

### التحقق من صحة المدخلات في SystemManageController

```php
<?php
// app/Http/Controllers/Api/SystemManageController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SystemManageController extends Controller
{
    /**
     * تبديل وضع الصيانة - مع التحقق من البيانات
     */
    public function maintenanceToggle(Request $request)
    {
        // التحقق من صحة البيانات المرسلة
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',                           // حالة التفعيل مطلوبة ومنطقية
            'message' => 'nullable|string|max:500',                    // رسالة الصيانة اختيارية
            'retry'   => 'nullable|integer|min:1|max:1440',           // دقائق إعادة المحاولة (1-24 ساعة)
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

        return response()->json($result);
    }

    /**
     * إنشاء نسخة احتياطية - التحقق من عدم وجود عملية قيد التشغيل
     */
    public function backupCreate(Request $request)
    {
        // التحقق من عدم وجود نسخة احتياطية قيد التشغيل
        $lockFile = storage_path('app/backups/.backup_lock');
        if (file_exists($lockFile)) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد نسخة احتياطية قيد التشغيل حالياً. الرجاء الانتظار.',
            ], 409); // Conflict
        }

        // تنفيذ عملية النسخ الاحتياطي
        try {
            $result = $this->backupManager->create();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل إنشاء النسخة الاحتياطية: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * استعادة نسخة احتياطية - التحقق من صحة المعرف
     */
    public function backupRestore(string $id, Request $request)
    {
        // التحقق من صحة معرف النسخة الاحتياطية
        $validator = Validator::make(['id' => $id], [
            'id' => [
                'required',
                'string',
                'regex:/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$/', // تنسيق: backup_YYYY-MM-DD_HH-MM-SS.sql.gz
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'معرف النسخة الاحتياطية غير صالح',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // التحقق من وجود الملف
        $filePath = storage_path("app/backups/{$id}");
        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'الملف غير موجود',
            ], 404);
        }

        // تأكيد الاستعادة (يجب إرسال confirm = true)
        $confirmValidator = Validator::make($request->all(), [
            'confirm' => 'required|accepted', // يجب أن يكون true
        ]);

        if ($confirmValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'الرجاء تأكيد عملية الاستعادة. سيتم فقدان أي تغييرات منذ آخر نسخة احتياطية.',
            ], 422);
        }

        // تنفيذ الاستعادة
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
     * حذف نسخة احتياطية
     */
    public function backupDelete(string $id)
    {
        // التحقق من صحة المعرف
        $validator = Validator::make(['id' => $id], [
            'id' => [
                'required',
                'string',
                'regex:/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$/',
            ],
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
     * عرض ملف سجل محدد - التحقق من اسم الملف
     */
    public function logsShow(string $file, Request $request)
    {
        // التحقق من اسم الملف لمنع directory traversal
        $validator = Validator::make(['file' => $file], [
            'file' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9_\-]+\.log$/', // أسماء ملفات آمنة فقط
                'max:100',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اسم الملف غير صالح',
            ], 422);
        }

        try {
            $result = $this->logManager->show($file);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل قراءة الملف: ' . $e->getMessage(),
            ], 500);
        }
    }
}
```

## ملخص قواعد التحقق

| نقطة النهاية | حقل التحقق | القاعدة | رسالة الخطأ (بالعربية) |
|-------------|-----------|---------|----------------------|
| جميع النقاط | JWT Token | `auth:api` | غير مصرح به |
| جميع النقاط | User Role | `role === 'admin'` | يتطلب صلاحيات المشرف |
| maintenance | `enabled` | `required, boolean` | حالة التفعيل مطلوبة |
| maintenance | `message` | `nullable, string, max:500` | رسالة الصيانة طويلة جداً |
| maintenance | `retry` | `nullable, integer, min:1, max:1440` | وقت إعادة المحاولة غير صالح |
| backup/{id} | `id` | `regex:/^backup_...\.sql\.gz$/` | معرف النسخة غير صالح |
| backup/{id}/restore | `confirm` | `required, accepted` | يجب تأكيد الاستعادة |
| logs/{file} | `file` | `regex:/^[a-zA-Z0-9_\-]+\.log$/` | اسم الملف غير صالح |

</div>
