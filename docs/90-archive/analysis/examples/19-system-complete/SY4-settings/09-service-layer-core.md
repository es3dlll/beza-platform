# 09 - طبقة الخدمة الأساسية: SettingsService (Core Service Layer)

## ملف الخدمة الكامل (Complete Service File)

```php
<?php
// // ملف: app/Services/Settings/SettingsService.php
// // خدمة إعدادات النظام: الطبقة الأساسية للتعامل مع الإعدادات
// // تتكامل مع قاعدة البيانات و Redis و الأحداث

namespace App\Services\Settings;

use App\Models\SystemSetting;
use App\Services\Settings\Exceptions\SettingUpdateException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Events\SettingUpdated;

class SettingsService
{
    /**
     * // حقن التبعيات المساعدة
     */
    public function __construct(
        private SettingsCacheManager $cacheManager,
        private SettingsValidator    $validator
    ) {}

    /**
     * // الحصول على جميع الإعدادات مجمعة حسب المجموعة
     * // يتم القراءة من الكاش أولاً، ثم من قاعدة البيانات
     * 
     * // @return array  [group => [key => value, ...], ...]
     */
    public function getAll(): array
    {
        // // محاولة القراءة من الكاش أولاً
        $cached = $this->cacheManager->getAll();
        if ($cached !== null) {
            return $cached;
        }

        // // إذا لم نجد في الكاش -> اقرأ من قاعدة البيانات
        $settings = SystemSetting::all();
        
        // // تنظيم الإعدادات في مصفوفة حسب المجموعة
        $grouped = [];
        foreach ($settings as $setting) {
            $group = $setting->group;
            $key   = $setting->key;
            $value = $setting->getTypedValue();

            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            $grouped[$group][$key] = $value;
        }

        // // خزن في الكاش للمرة القادمة
        $this->cacheManager->setAll($grouped);

        return $grouped;
    }

    /**
     * // الحصول على إعدادات مجموعة محددة
     * 
     * // @param string $group  general, fees, limits, ...
     * // @return array  [key => value, ...]
     */
    public function getByGroup(string $group): array
    {
        $all = $this->getAll();
        return $all[$group] ?? [];
    }

    /**
     * // الحصول على إعداد واحد مع قيمة افتراضية
     * 
     * // @param string $fullKey  المفتاح الكامل (general.app_name)
     * // @param mixed  $default  القيمة الافتراضية إذا لم يوجد
     * // @return mixed
     */
    public function get(string $fullKey, mixed $default = null): mixed
    {
        [$group, $key] = explode('.', $fullKey, 2);

        // // محاولة القراءة من الكاش أولاً
        $cached = $this->cacheManager->get($fullKey);
        if ($cached !== null) {
            return $cached;
        }

        // // من قاعدة البيانات
        $setting = SystemSetting::where('group', $group)
            ->where('key', $key)
            ->first();

        if (!$setting) {
            return $default;
        }

        $value = $setting->getTypedValue();

        // // خزن في الكاش
        $this->cacheManager->set($fullKey, $value);

        return $value;
    }

    /**
     * // تحديث مجموعة كاملة من الإعدادات
     * // يتم التحديث في معاملة ذرية (transaction)
     * 
     * // @param string $group  اسم المجموعة
     * // @param array  $data   المفاتيح والقيم الجديدة
     * // @throws SettingUpdateException
     */
    public function setGroup(string $group, array $data): void
    {
        // // بدء معاملة قاعدة بيانات
        DB::beginTransaction();

        try {
            // // تحديث كل إعداد في المجموعة
            foreach ($data as $key => $value) {
                $fullKey = "{$group}.{$key}";

                // // البحث عن الإعداد أو إنشاء جديد
                $setting = SystemSetting::firstOrNew([
                    'group' => $group,
                    'key'   => $key,
                ]);

                // // تعيين القيمة مع تحويل النوع التلقائي
                $setting->setTypedValue($value);

                // // تخمين النوع إذا كان جديداً
                if (!$setting->exists) {
                    $setting->type = $this->guessType($value);
                }

                $setting->save();

                // // مسح هذا المفتاح من الكاش
                $this->cacheManager->forget($fullKey);
            }

            // // تأكيد المعاملة
            DB::commit();

            // // إرسال حدث تحديث الإعدادات
            Event::dispatch(new SettingUpdated($group, $data));

            // // مسح الكاش الكلي للمجموعة
            $this->cacheManager->forgetGroup($group);

        } catch (\Throwable $e) {
            // // التراجع عن المعاملة في حالة الفشل
            DB::rollBack();

            Log::error('SY4: فشل تحديث مجموعة الإعدادات', [
                'group' => $group,
                'error' => $e->getMessage(),
            ]);

            throw new SettingUpdateException(
                "فشل تحديث إعدادات {$group}: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * // تحديث إعداد واحد
     * 
     * // @param string $fullKey  general.app_name
     * // @param mixed  $value    القيمة الجديدة
     */
    public function set(string $fullKey, mixed $value): void
    {
        [$group, $key] = explode('.', $fullKey, 2);

        // // التحقق من صحة القيمة أولاً
        $this->validator->validateSingle($fullKey, $value);

        // // تحديث أو إنشاء الإعداد
        SystemSetting::upsertSetting($group, $key, $value);

        // // مسح الكاش
        $this->cacheManager->forget($fullKey);

        // // إرسال الحدث
        Event::dispatch(new SettingUpdated($group, [$key => $value]));
    }

    /**
     * // اختبار اتصال SMTP
     * 
     * // @param array $smtpConfig  إعدادات SMTP
     * // @return bool              نجاح أو فشل الاتصال
     */
    public function testSmtpConnection(array $smtpConfig): bool
    {
        try {
            // // تكوين بريد مؤقت للاختبار
            config(['mail.mailers.smtp' => array_merge(
                config('mail.mailers.smtp', []),
                [
                    'host'       => $smtpConfig['host'] ?? '',
                    'port'       => $smtpConfig['port'] ?? 587,
                    'encryption' => $smtpConfig['encryption'] ?? 'tls',
                    'username'   => $smtpConfig['username'] ?? '',
                    'password'   => $smtpConfig['password'] ?? '',
                ]
            )]);

            // // محاولة إرسال بريد اختباري
            $transport = app('mailer')->getSymfonyTransport();
            $transport->start();

            return true;
        } catch (\Throwable $e) {
            Log::warning('SY4: فشل اختبار SMTP', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * // تخمين نوع القيمة تلقائياً للإعدادات الجديدة
     * 
     * // @param mixed $value
     * // @return string  type: string, integer, float, boolean, json
     */
    private function guessType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value)) {
            return 'json';
        }
        if (is_string($value) && json_validate($value)) {
            return 'json';
        }
        return 'string';
    }
}
```

## مثال الاستخدام (Usage Example)

```php
// // في أي مكان في التطبيق
$settingsService = app(SettingsService::class);

// // قراءة جميع الإعدادات
$all = $settingsService->getAll();

// // قراءة إعداد واحد
$appName = $settingsService->get('general.app_name', 'Beza');

// // تحديث مجموعة
$settingsService->setGroup('fees', [
    'p2p'      => 1.0,
    'exchange' => 2.0,
]);

// // تحديث إعداد واحد
$settingsService->set('general.app_name', 'Beza 2.0');
```

## وصف الدوال (Method Summary)

```php
// // getAll(): array
// //   -> جلب كل الإعدادات مجمعة (مع كاش)
// //
// // getByGroup(string $group): array
// //   -> جلب إعدادات مجموعة محددة
// //
// // get(string $key, mixed $default): mixed
// //   -> جلب إعداد واحد مع قيمة افتراضية
// //
// // setGroup(string $group, array $data): void
// //   -> تحديث مجموعة في transaction مع حدث
// //
// // set(string $key, mixed $value): void
// //   -> تحديث إعداد واحد
// //
// // testSmtpConnection(array $config): bool
// //   -> اختبار SMTP
```
