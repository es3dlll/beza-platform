# 08 - كود كامل لـ SystemSettingsController (Full Controller Code)

## ملف الكونترولر الكامل (Complete Controller File)

```php
<?php
// // ملف: app/Http/Controllers/Admin/SystemSettingsController.php
// // كونترولر إعدادات النظام: يعرض ويحدث جميع إعدادات المنصة
// // محمي بـ middleware: auth:api, admin

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use App\Services\Settings\SettingsValidator;
use App\Services\Settings\SettingsCacheManager;
use App\Services\Settings\Exceptions\SettingUpdateException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SystemSettingsController extends Controller
{
    /**
     * // حقن التبعيات: الخدمة والمدقق
     */
    public function __construct(
        private SettingsService      $settingsService,
        private SettingsValidator    $validator,
        private SettingsCacheManager $cacheManager
    ) {}

    /**
     * // عرض جميع إعدادات النظام مجمعة حسب المجموعة
     * // GET /admin/system/settings
     * 
     * // @return JsonResponse جميع الإعدادات مع ملخص
     */
    public function index(): JsonResponse
    {
        try {
            // // جلب جميع الإعدادات من خلال الخدمة (مع الكاش)
            $allSettings = $this->settingsService->getAll();
            
            // // تحضير الإحصائيات
            $stats = [
                'total_groups'  => count($allSettings),
                'total_settings'=> array_sum(array_map('count', $allSettings)),
                'cache_status'  => $this->cacheManager->isWarm() ? 'warm' : 'cold',
            ];

            return response()->json([
                'success'  => true,
                'data'     => $allSettings,
                'metadata' => $stats,
            ]);
        } catch (\Throwable $e) {
            // // تسجيل الخطأ في حالة فشل جلب الإعدادات
            Log::error('SY4: فشل جلب إعدادات النظام', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل جلب إعدادات النظام',
            ], 500);
        }
    }

    /**
     * // تحديث إعدادات مجموعة محددة
     * // PUT /admin/system/settings/{group}
     * 
     * // @param string  $group  اسم المجموعة (general, fees, ...)
     * // @param Request $request  البيانات الجديدة
     * // @return JsonResponse
     */
    public function update(string $group, Request $request): JsonResponse
    {
        try {
            // // التحقق من صحة البيانات حسب المجموعة
            $validated = $this->validator->validate($group, $request->all());

            // // تحديث الإعدادات في قاعدة البيانات والكاش
            $this->settingsService->setGroup($group, $validated);

            // // إعادة جميع الإعدادات بعد التحديث
            $updatedSettings = $this->settingsService->getByGroup($group);

            return response()->json([
                'success' => true,
                'message' => "تم تحديث إعدادات {$group} بنجاح",
                'data'    => $updatedSettings,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // // أخطاء التحقق من الصحة
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            // // مجموعة غير معروفة
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (SettingUpdateException $e) {
            // // فشل تحديث قاعدة البيانات
            Log::error('SY4: فشل تحديث الإعدادات', [
                'group' => $group,
                'data'  => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل تحديث الإعدادات. الرجاء المحاولة مرة أخرى',
            ], 500);
        }
    }

    /**
     * // اختبار اتصال SMTP
     * // POST /admin/system/settings/mail/test
     * 
     * // @param Request $request  يحتوي على إعدادات SMTP للاختبار
     * // @return JsonResponse
     */
    public function testMail(Request $request): JsonResponse
    {
        try {
            // // التحقق من صحة إعدادات SMTP
            $validated = $this->validator->validateSingle('mail.smtp', 
                $request->input('smtp', '{}')
            );

            // // اختبار الاتصال
            $result = $this->settingsService->testSmtpConnection(
                json_decode($validated, true)
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم الاتصال بخادم البريد بنجاح',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل الاتصال بخادم البريد. تحقق من الإعدادات',
            ], 400);
        } catch (\Throwable $e) {
            Log::error('SY4: فشل اختبار SMTP', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء اختبار اتصال SMTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * // الحصول على إعدادات مجموعة محددة
     * // GET /admin/system/settings/{group}
     * 
     * // @param string $group
     * // @return JsonResponse
     */
    public function show(string $group): JsonResponse
    {
        try {
            $settings = $this->settingsService->getByGroup($group);

            if (empty($settings)) {
                return response()->json([
                    'success' => false,
                    'message' => "لا توجد إعدادات للمجموعة '{$group}'",
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $settings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل جلب الإعدادات',
            ], 500);
        }
    }
}
```

## ملف Routes (Routes File)

```php
<?php
// // ملف: routes/api.php
// // مسارات إعدادات النظام محمية بـ auth:api

use App\Http\Controllers\Admin\SystemSettingsController;

Route::middleware(['auth:api', 'admin'])->prefix('admin/system')->group(function () {
    // // عرض جميع الإعدادات
    Route::get('/settings', [SystemSettingsController::class, 'index']);

    // // عرض إعدادات مجموعة محددة
    Route::get('/settings/{group}', [SystemSettingsController::class, 'show']);

    // // تحديث إعدادات مجموعة
    Route::put('/settings/{group}', [SystemSettingsController::class, 'update']);

    // // اختبار اتصال SMTP
    Route::post('/settings/mail/test', [SystemSettingsController::class, 'testMail']);
});
```

## مثال على الاستجابة (Response Examples)

```json
// // GET /admin/system/settings -> 200 OK
{
  "success": true,
  "data": {
    "general": {
      "app_name": "Beza",
      "app_description": "منصة بيزا للمعاملات المالية",
      "timezone": "Asia/Riyadh",
      "locale": "ar"
    },
    "features": {
      "gold": true,
      "deals": true,
      "cards": true,
      "agents": true,
      "loans": false
    },
    "fees": {
      "p2p": 0,
      "exchange": 1.5,
      "card_deposit": 2.5,
      "withdrawal": 1.0
    }
  },
  "metadata": {
    "total_groups": 9,
    "total_settings": 35,
    "cache_status": "warm"
  }
}

// // PUT /admin/system/settings/general -> 200 OK
{
  "success": true,
  "message": "تم تحديث إعدادات general بنجاح",
  "data": {
    "app_name": "Beza",
    "app_description": "منصة بيزا المالية",
    "timezone": "Asia/Riyadh",
    "locale": "ar"
  }
}

// // PUT /admin/system/settings/fees -> 422 Unprocessable
{
  "success": false,
  "message": "بيانات غير صالحة",
  "errors": {
    "p2p": ["نسبة الرسوم يجب أن تكون رقماً"],
    "exchange": ["نسبة الرسوم يجب ألا تزيد عن 100"]
  }
}
```
