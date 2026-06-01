# 07 - قواعد التحقق لكل مجموعة إعدادات (Validation Rules per Group)

## نظرة عامة (Overview)

كل مجموعة إعدادات لها قواعد تحقق مختلفة. يتم التحقق من صحة البيانات قبل تحديثها. قواعد التحقق مبنية باستخدام FormRequest أو Validator facade.

```php
// // مثال: قواعد التحقق تختلف حسب المجموعة
// // general: نصوص مع حدود طول
// // fees: أرقام عشرية بين 0 و 100
// // security: أعداد صحيحة موجبة
// // features: قيم منطقية فقط
```

## قواعد التحقق الكاملة لكل مجموعة (Validation Rules)

```php
<?php
// // ملف: app/Services/Settings/SettingsValidator.php
// // مدقق إعدادات النظام: يتحقق من صحة البيانات حسب كل مجموعة

namespace App\Services\Settings;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SettingsValidator
{
    /**
     * // خريطة قواعد التحقق لكل مجموعة
     * // كل مجموعة لها دوال تحقق مختلفة
     */
    private const GROUP_RULES = [
        'general' => [
            'app_name'        => 'required|string|min:2|max:100',
            'app_description' => 'nullable|string|max:500',
            'app_logo'        => 'nullable|string|url|max:500',
            'app_favicon'     => 'nullable|string|url|max:500',
            'timezone'        => 'required|string|timezone',
            'locale'          => 'required|string|in:ar,en',
        ],

        'features' => [
            'gold'   => 'boolean',
            'deals'  => 'boolean',
            'cards'  => 'boolean',
            'agents' => 'boolean',
            'loans'  => 'boolean',
        ],

        'fees' => [
            'p2p'           => 'required|numeric|min:0|max:100',
            'exchange'      => 'required|numeric|min:0|max:100',
            'card_deposit'  => 'required|numeric|min:0|max:100',
            'withdrawal'    => 'required|numeric|min:0|max:100',
        ],

        'limits' => [
            'daily_transfer' => 'required|integer|min:0|max:999999999',
            'max_wallet'     => 'required|integer|min:0|max:999999999',
            'min_withdrawal' => 'required|integer|min:0|max:999999999',
        ],

        'exchange' => [
            'margin'          => 'required|numeric|min:0|max:100',
            'update_interval' => 'required|integer|min:10|max:86400',
        ],

        'security' => [
            'max_attempts'    => 'required|integer|min:1|max:100',
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
            'mode'        => 'boolean',
            'message'     => 'required_if:mode,true|string|max:1000',
            'allowed_ips' => 'nullable|json',
        ],
    ];

    /**
     * // رسائل الخطأ المخصصة (بالعربية)
     */
    private const CUSTOM_MESSAGES = [
        'app_name.required'        => 'اسم التطبيق مطلوب',
        'app_name.min'             => 'اسم التطبيق يجب أن يكون على الأقل حرفين',
        'app_name.max'             => 'اسم التطبيق يجب ألا يتجاوز 100 حرف',
        'timezone.timezone'        => 'المنطقة الزمنية غير صالحة',
        'locale.in'                => 'اللغة يجب أن تكون ar أو en',
        'fees.*.numeric'           => 'نسبة الرسوم يجب أن تكون رقماً',
        'fees.*.min'               => 'نسبة الرسوم يجب ألا تقل عن 0',
        'fees.*.max'               => 'نسبة الرسوم يجب ألا تزيد عن 100',
        'daily_transfer.integer'   => 'الحد الأقصى للتحويل اليومي يجب أن يكون رقماً صحيحاً',
        'max_attempts.min'         => 'حد أدنى لمحاولات الدخول هو 1',
        'password_policy.json'     => 'سياسة كلمة المرور يجب أن تكون JSON صالحاً',
        'maintenance.message.required_if' => 'رسالة الصيانة مطلوبة عند تفعيل وضع الصيانة',
    ];

    /**
     * // التحقق من صحة إعدادات مجموعة محددة
     * 
     * // @param string $group  اسم المجموعة (general, fees, ...)
     * // @param array  $data   البيانات المراد التحقق منها
     * // @return array         البيانات بعد التحقق
     * // @throws ValidationException
     */
    public function validate(string $group, array $data): array
    {
        // // التأكد من وجود قواعد لهذه المجموعة
        if (!isset(self::GROUP_RULES[$group])) {
            throw new \InvalidArgumentException(
                "المجموعة '{$group}' غير معروفة. المجموعات المسموحة: " 
                . implode(', ', array_keys(self::GROUP_RULES))
            );
        }

        // // الحصول على قواعد هذه المجموعة فقط
        $rules = self::GROUP_RULES[$group];

        // // تصفية البيانات: نتحقق فقط من الحقول الموجودة في القواعد
        $filteredData = array_intersect_key($data, $rules);

        // // إنشاء المvalidator مع القواعد والرسائل المخصصة
        $validator = Validator::make($filteredData, $rules, self::CUSTOM_MESSAGES);

        // // إذا فشل التحقق -> استثناء
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * // التحقق من صحة إعداد واحد فقط
     * 
     * // @param string $fullKey المفتاح الكامل (group.key)
     * // @param mixed  $value   القيمة
     * // @return mixed          القيمة بعد التحقق
     */
    public function validateSingle(string $fullKey, mixed $value): mixed
    {
        [$group, $key] = explode('.', $fullKey, 2);

        $rule = self::GROUP_RULES[$group][$key] ?? null;

        if (!$rule) {
            throw new \InvalidArgumentException(
                "المفتاح '{$fullKey}' غير معروف في قواعد التحقق"
            );
        }

        $validator = Validator::make(
            [$key => $value],
            [$key => $rule],
            self::CUSTOM_MESSAGES
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated()[$key];
    }

    /**
     * // الحصول على جميع قواعد مجموعة معينة
     * // مفيد لتوثيق API وعرضها في الواجهة
     */
    public function getRulesForGroup(string $group): array
    {
        return self::GROUP_RULES[$group] ?? [];
    }

    /**
     * // الحصول على جميع المجموعات المسموحة
     */
    public function getAllowedGroups(): array
    {
        return array_keys(self::GROUP_RULES);
    }
}
```

## أمثلة على التحقق (Validation Examples)

```php
// // المثال 1: تحديث الإعدادات العامة
$validator = new SettingsValidator();
$data = $validator->validate('general', [
    'app_name' => 'Beza',
    'timezone' => 'Asia/Riyadh',
    'locale'   => 'ar',
]);
// // نجاح: البيانات صحيحة

// // المثال 2: خطأ في التحقق
try {
    $validator->validate('fees', [
        'p2p'      => -5,      // // خطأ: أقل من 0
        'exchange' => 150,     // // خطأ: أكثر من 100
    ]);
} catch (ValidationException $e) {
    $errors = $e->errors();
    // // p2p: ['نسبة الرسوم يجب ألا تقل عن 0']
    // // exchange: ['نسبة الرسوم يجب ألا تزيد عن 100']
}

// // المثال 3: مجموعة غير معروفة
try {
    $validator->validate('unknown_group', []);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage();
    // // "المجموعة 'unknown_group' غير معروفة..."
}
```

## قواعد خاصة (Special Rules)

```php
// // 1. قواعد مشروطة
// // إذا تم تفعيل وضع الصيانة، رسالة الصيانة تصبح إجبارية
'message' => 'required_if:mode,true|string|max:1000',

// // 2. قواعد JSON
// // password_policy و smtp يجب أن تكون JSON صالحاً
// // يتم التحقق من الصلاحية syntax وليس المحتوى
'password_policy' => 'required|json',

// // 3. قواعد Boolean
// // gold, deals, cards, agents, loans, mode
// // Laravel تقبل: true, false, 1, 0, "1", "0"
'gold' => 'boolean',

// // 4. حدود قصوى للحماية
// // max_wallet: 999,999,999 لمنع القيم الخيالية
'max_wallet' => 'required|integer|min:0|max:999999999',
```
