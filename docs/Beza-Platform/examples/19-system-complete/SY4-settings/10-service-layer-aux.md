# 10 - طبقة الخدمات المساعدة: SettingsCacheManager, SettingsHelper, SettingsValidator (Auxiliary Services)

## SettingsCacheManager — مدير التخزين المؤقت

```php
<?php
// // ملف: app/Services/Settings/SettingsCacheManager.php
// // إدارة التخزين المؤقت لإعدادات النظام في Redis
// // استراتيجية: Cache-Aside مع TTL = 3600 ثانية

namespace App\Services\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SettingsCacheManager
{
    // // مدة صلاحية الكاش: ساعة واحدة (بالثواني)
    private const TTL = 3600;

    // // بادئة مفاتيح الكاش
    private const PREFIX = 'system_settings';

    // // مفتاح الكاش الكلي (جميع الإعدادات)
    private const ALL_KEY = self::PREFIX . ':all';

    /**
     * // الحصول على جميع الإعدادات من الكاش
     * 
     * // @return array|null  null إذا لم يكن في الكاش
     */
    public function getAll(): ?array
    {
        try {
            $cached = Cache::get(self::ALL_KEY);
            if ($cached !== null) {
                // // تجديد TTL عند القراءة الناجحة
                Cache::expire(self::ALL_KEY, self::TTL);
                return $cached;
            }
        } catch (\Throwable $e) {
            // // إذا فشل Redis، نقرأ من DB (failsafe)
            Log::warning('SY4: فشل قراءة الكاش الكلي', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * // تخزين جميع الإعدادات في الكاش
     */
    public function setAll(array $settings): void
    {
        try {
            Cache::put(self::ALL_KEY, $settings, self::TTL);
        } catch (\Throwable $e) {
            Log::warning('SY4: فشل تخزين الكاش الكلي', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * // الحصول على إعداد واحد من الكاش
     * 
     * // @param string $key  المفتاح الكامل (general.app_name)
     * // @return mixed|null
     */
    public function get(string $key): mixed
    {
        try {
            $cacheKey = self::PREFIX . ":{$key}";
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                Cache::expire($cacheKey, self::TTL);
                return $cached;
            }
        } catch (\Throwable $e) {
            Log::warning('SY4: فشل قراءة كاش', ['key' => $key]);
        }

        return null;
    }

    /**
     * // تخزين إعداد واحد في الكاش
     */
    public function set(string $key, mixed $value): void
    {
        try {
            Cache::put(self::PREFIX . ":{$key}", $value, self::TTL);
        } catch (\Throwable $e) {
            Log::warning('SY4: فشل تخزين كاش', ['key' => $key]);
        }
    }

    /**
     * // مسح إعداد واحد من الكاش
     */
    public function forget(string $key): void
    {
        try {
            Cache::forget(self::PREFIX . ":{$key}");
        } catch (\Throwable $e) {
            Log::warning('SY4: فشل مسح كاش', ['key' => $key]);
        }
    }

    /**
     * // مسح جميع إعدادات مجموعة من الكاش
     */
    public function forgetGroup(string $group): void
    {
        try {
            // // مسح الكاش الكلي أيضاً لأنه يحتوي على هذه المجموعة
            Cache::forget(self::ALL_KEY);

            // // مسح جميع مفاتيح هذه المجموعة باستخدام pattern
            $pattern = self::PREFIX . ":{$group}.*";
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        } catch (\Throwable $e) {
            Log::warning('SY4: فشل مسح كاش المجموعة', ['group' => $group]);
        }
    }

    /**
     * // مسح كل الكاش (للاستخدام في الصيانة)
     */
    public function flush(): void
    {
        try {
            $pattern = self::PREFIX . ':*';
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        } catch (\Throwable $e) {
            Log::warning('SY4: فشل مسح كل الكاش');
        }
    }

    /**
     * // التحقق من وجود الكاش (هل هو دافئ؟)
     */
    public function isWarm(): bool
    {
        return Cache::has(self::ALL_KEY);
    }
}
```

## SettingsHelper — الدالة المساعدة العامة

```php
<?php
// // ملف: app/Services/Settings/SettingsHelper.php
// // دالة مساعدة لقراءة إعدادات النظام من أي مكان في التطبيق

namespace App\Services\Settings;

use Illuminate\Support\Facades\App;

class SettingsHelper
{
    /**
     * // الحصول على إعداد نظام مع قيمة افتراضية
     * // هذه الدالة تستخدم في جميع أنحاء التطبيق
     * 
     * // @param string $key     المفتاح مثل general.app_name
     * // @param mixed  $default القيمة الافتراضية
     * // @return mixed
     * 
     * // مثال:
     * //   $name = system_settings('general.app_name', 'Beza');
     * //   $fee  = system_settings('fees.p2p', 0);
     * //   $gold = system_settings('features.gold', true);
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $service = App::make(SettingsService::class);
        return $service->get($key, $default);
    }

    /**
     * // الحصول على جميع إعدادات مجموعة
     * 
     * // @param string $group general, fees, etc.
     * // @return array
     */
    public static function getGroup(string $group): array
    {
        $service = App::make(SettingsService::class);
        return $service->getByGroup($group);
    }

    /**
     * // هل الميزة مفعلة؟
     * // تستخدم للتحقق من feature flags
     * 
     * // @param string $feature  gold, deals, cards, agents, loans
     * // @return bool
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return (bool) self::get("features.{$feature}", false);
    }

    /**
     * // الحصول على نسبة رسوم محددة
     * 
     * // @param string $feeType  p2p, exchange, card_deposit, withdrawal
     * // @return float
     */
    public static function getFee(string $feeType): float
    {
        return (float) self::get("fees.{$feeType}", 0);
    }
}
```

## الدالة العامة (Global Helper Function)

```php
<?php
// // ملف: app/helpers.php
// // دالة عامة لقراءة إعدادات النظام من أي مكان

if (!function_exists('system_settings')) {
    /**
     * // الحصول على إعداد نظام مع قيمة افتراضية
     * // هذه دالة عامة متاحة في كل مكان في التطبيق
     * // تستخدم بديلاً عن config() لإعدادات النظام
     * 
     * // @param string $key     general.app_name
     * // @param mixed  $default القيمة الافتراضية
     * // @return mixed
     */
    function system_settings(string $key, mixed $default = null): mixed
    {
        return \App\Services\Settings\SettingsHelper::get($key, $default);
    }
}

if (!function_exists('system_settings_group')) {
    /**
     * // الحصول على إعدادات مجموعة كاملة
     */
    function system_settings_group(string $group): array
    {
        return \App\Services\Settings\SettingsHelper::getGroup($group);
    }
}
```

## SettingsValidator (المستكمل من الملف 07)

ملف `SettingsValidator` تم شرحه كاملاً في الملف رقم 07. هذا ملخص مختصر:

```php
<?php
namespace App\Services\Settings;

class SettingsValidator
{
    /**
     * // التحقق من صحة بيانات مجموعة
     */
    public function validate(string $group, array $data): array
    {
        $rules = $this->getRulesForGroup($group);

        if (empty($rules)) {
            throw new \InvalidArgumentException("المجموعة {$group} غير معروفة");
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            $data, $rules, $this->getMessages()
        );

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * // التحقق من إعداد واحد
     */
    public function validateSingle(string $fullKey, mixed $value): mixed
    {
        [$group, $key] = explode('.', $fullKey, 2);

        $rules = $this->getRulesForGroup($group);
        $rule = $rules[$key] ?? null;

        if (!$rule) {
            throw new \InvalidArgumentException("المفتاح {$fullKey} غير معروف");
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            [$key => $value], [$key => $rule], $this->getMessages()
        );

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated()[$key];
    }

    /**
     * // قواعد كل مجموعة
     */
    private function getRulesForGroup(string $group): array
    {
        return [
            'general' => [
                'app_name'        => 'required|string|min:2|max:100',
                'app_description' => 'nullable|string|max:500',
                'app_logo'        => 'nullable|string|url|max:500',
                'app_favicon'     => 'nullable|string|url|max:500',
                'timezone'        => 'required|string|timezone',
                'locale'          => 'required|string|in:ar,en',
            ],
            'features' => [
                'gold'   => 'boolean', 'deals' => 'boolean',
                'cards'  => 'boolean', 'agents' => 'boolean', 'loans' => 'boolean',
            ],
            'fees' => [
                'p2p' => 'required|numeric|min:0|max:100',
                'exchange' => 'required|numeric|min:0|max:100',
                'card_deposit' => 'required|numeric|min:0|max:100',
                'withdrawal' => 'required|numeric|min:0|max:100',
            ],
            'limits' => [
                'daily_transfer' => 'required|integer|min:0|max:999999999',
                'max_wallet' => 'required|integer|min:0|max:999999999',
                'min_withdrawal' => 'required|integer|min:0|max:999999999',
            ],
            'exchange' => [
                'margin' => 'required|numeric|min:0|max:100',
                'update_interval' => 'required|integer|min:10|max:86400',
            ],
            'security' => [
                'max_attempts' => 'required|integer|min:1|max:100',
                'lockout_minutes' => 'required|integer|min:1|max:1440',
                'password_policy' => 'required|json',
            ],
            'notifications' => [
                'default_channels' => 'required|json',
            ],
            'mail' => [
                'smtp' => 'required|json',
            ],
            'maintenance' => [
                'mode' => 'boolean',
                'message' => 'required_if:mode,true|string|max:1000',
                'allowed_ips' => 'nullable|json',
            ],
        ][$group] ?? [];
    }

    private function getMessages(): array
    {
        return [
            'app_name.required' => 'اسم التطبيق مطلوب',
            'timezone.timezone' => 'المنطقة الزمنية غير صالحة',
            'locale.in' => 'اللغة يجب أن تكون ar أو en',
        ];
    }
}
```
