# 19 - الحالات الحدية: التحديث المتزامن، انهيار الكاش، القيم غير الصالحة (Edge Cases)

## نظرة عامة (Overview)

تغطية جميع الحالات الحدية التي قد تحدث في نظام إعدادات SY4-settings، مع حلول لكل حالة.

```php
// // الحالات الحدية هي مواقف غير متوقعة
// // لكنها ممكنة ويجب أن يكون النظام مستعداً لها
```

## 1. التحديث المتزامن (Concurrent Updates)

```php
// // المشكلة: مسؤولان يحدثان نفس الإعداد في نفس الوقت

// // مسؤول أ: PUT fees.p2p = 2.0
// // مسؤول ب: PUT fees.p2p = 3.0
// // الترتيب: أ -> بدأ -> كتب 2.0 -> ب -> بدأ -> كتب 3.0 -> ب commited -> أ commited
// // النتيجة: قيمة fees.p2p = 2.0 (قيمة أ، لأن أ commited بعد ب)

// // الحل 1: استخدام lastWriteWins (مقبول هنا)
// // آخر من يكتب هو الفائز - لأن الإعدادات غير حرجة للتوقيت

// // الحل 2: optimistic locking
public function setGroupWithOptimisticLock(string $group, array $data): void
{
    $attempts = 0;
    $maxAttempts = 3;

    while ($attempts < $maxAttempts) {
        try {
            DB::transaction(function () use ($group, $data) {
                foreach ($data as $key => $value) {
                    $setting = SystemSetting::where('group', $group)
                        ->where('key', $key)
                        ->lockForUpdate() // // قفل الصف
                        ->first();

                    SystemSetting::upsertSetting($group, $key, $value);
                }
            });
            break; // // نجاح
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Deadlock')) {
                $attempts++;
                usleep(100000); // // انتظار 100ms
                continue;
            }
            throw $e;
        }
    }
}

// // الحل 3: قفل على مستوى المجموعة (Redis Lock)
public function setGroupWithRedisLock(string $group, array $data): void
{
    $lock = Cache::lock("setting_lock:{$group}", 10); // // 10 ثوانٍ

    if (!$lock->get()) {
        throw new SettingUpdateException(
            "المجموعة {$group} مقفلة حالياً بواسطة مسؤول آخر. حاول مرة أخرى"
        );
    }

    try {
        $this->setGroup($group, $data);
    } finally {
        $lock->release();
    }
}
```

## 2. انهيار التخزين المؤقت (Cache Stampede)

```php
// // المشكلة: عندما ينتهي TTL للكاش وتأتي طلبات متعددة
// // كل الطلبات تحاول إعادة بناء الكاش -> ضغط على قاعدة البيانات

// // السيناريو:
// // 1. TTL ينتهي الساعة 12:00
// // 2. 100 مستخدم يطلبون الإعدادات الساعة 12:00:01
// // 3. كل طلب يقرأ من MySQL -> 100 عملية قراءة!
// // 4. كل طلب يخزن في Redis -> 100 عملية كتابة!

// // الحل 1: قفل التجديد (Cache Lock)
public function getAllWithLock(): array
{
    $cached = $this->cacheManager->getAll();
    if ($cached !== null) {
        return $cached;
    }

    // // قفل لتجنب الـ stampede
    $lock = Cache::lock('system_settings_rebuild', 5);

    try {
        if ($lock->get()) {
            // // تحقق مرة أخرى بعد الحصول على القفل
            $cached = $this->cacheManager->getAll();
            if ($cached !== null) {
                return $cached;
            }

            // // إعادة بناء الكاش
            $settings = SystemSetting::all();
            $grouped = $this->groupSettings($settings);
            $this->cacheManager->setAll($grouped);
            return $grouped;
        }

        // // انتظار قليلاً ثم حاول مرة أخرى
        usleep(50000); // 50ms
        return $this->getAllWithLock();
    } finally {
        $lock->release();
    }
}

// // الحل 2: التجديد المبكر (Early Refresh)
// // قبل انتهاء TTL بعشر دقائق، جدد الكاش
public function getWithEarlyRefresh(string $key, mixed $default): mixed
{
    $cacheKey = "system_settings:{$key}";
    $ttl = 3600;
    $earlyRefreshAt = 3000; // // 50 دقيقة (قبل 10 دقائق من النهاية)

    // // تحقق من وجود "إشارة التجديد"
    $refreshFlag = Cache::get("{$cacheKey}:refreshing");
    if (!$refreshFlag && !Cache::has($cacheKey)) {
        // // الكاش على وشك الانتهاء
        Cache::set("{$cacheKey}:refreshing", true, 60);
        
        // // جدد في الخلفية
        dispatch(function () use ($key) {
            $this->refreshSettingInCache($key);
        });
    }

    return Cache::remember($cacheKey, $ttl, function () use ($key, $default) {
        return $this->fetchFromDatabase($key, $default);
    });
}
```

## 3. قيم غير صالحة (Invalid Values)

```php
// // المشكلة: إرسال قيم غير متوقعة قد تكسر النظام

// // السيناريو 1: نص في حقل رقمي
// PUT fees.p2p = "نص عشوائي"
// -> Validator يمنع هذا (قاعدة numeric)

// // السيناريو 2: JSON غير صالح
// PUT security.password_policy = "{invalid json}"
// -> Validator يمنع (قاعدة json)

// // السيناريو 3: قيمة منطقية غير صالحة
// PUT features.gold = "maybe"
// -> Laravel boolean rule يقبل: true, false, 1, 0, "1", "0"
// -> "maybe" -> ValidationException

// // السيناريو 4: رقم سالب في حد
// PUT limits.daily_transfer = -1000
// -> Validator يمنع (قاعدة min:0)

// // السيناريو 5: XSS في قيمة نصية
// PUT general.app_name = "<script>alert('xss')</script>"
// -> يجب sanitize القيمة عند العرض في React
// -> Laravel لا يحتاج escape لأنها API
// -> React تتعامل مع XSS prevention تلقائياً

// // الحل الأساسي: الطبقات الثلاث للتحقق
// 1. Validator -> يمنع القيم غير الصالحة
// 2. Model casting -> يحول الأنواع
// 3. Sanitization -> ينظف القيم النصية
public function sanitizeValue(string $value): string
{
    // // إزالة HTML tags من القيم النصية
    return strip_tags($value);
}
```

## 4. بيانات مفقودة (Missing Data)

```php
// // المشكلة: مجموعة إعدادات كاملة مفقودة من قاعدة البيانات

// // السيناريو: تم حذف جميع إعدادات fees
// // الحل: استخدام القيم الافتراضية

public function getByGroup(string $group): array
{
    $all = $this->getAll();
    
    // // إذا المجموعة غير موجودة، استخدم القيم الافتراضية
    if (!isset($all[$group])) {
        return $this->getDefaultForGroup($group);
    }

    return $all[$group];
}

private function getDefaultForGroup(string $group): array
{
    return match ($group) {
        'fees' => [
            'p2p' => 0,
            'exchange' => 1.5,
            'card_deposit' => 2.5,
            'withdrawal' => 1.0,
        ],
        'limits' => [
            'daily_transfer' => 100000,
            'max_wallet' => 500000,
            'min_withdrawal' => 100,
        ],
        'features' => [
            'gold' => true,
            'deals' => true,
            'cards' => true,
            'agents' => true,
            'loans' => false,
        ],
        default => [],
    };
}
```

## 5. أحرف خاصة في الإعدادات (Special Characters)

```php
// // المشكلة: أحرف Unicode أو RTL في الإعدادات

// // رسالة الصيانة بالعربية:
// "نظام بيزا تحت الصيانة حالياً. سنعود قريباً!"

// // JSON مع Unicode:
// '{"min_length":8,"require_upper":true}'

// // URL لوجو:
// "https://beza.sa/img/logo-2024.png"

// // جميع هذه الحالات مدعومة لأن:
// 1. قيمة النوع TEXT تدعم UTF-8
// 2. json_encode مع JSON_UNESCAPED_UNICODE
// 3. قيمة الإعداد مسموح بها كـ string
public function setTypedValue(mixed $value): void
{
    $this->value = match ($this->type) {
        'json' => json_encode($value, JSON_UNESCAPED_UNICODE),
        default => (string) $value,
    };
}
```

## 6. Redis غير متصل (Redis Connection Failure)

```php
// // المشكلة: Redis down -> كل طلب يقرأ من MySQL مباشرة

// // الحل: Failover تلقائي
public function get(string $key, mixed $default = null): mixed
{
    try {
        $cached = $this->cacheManager->get($key);
        if ($cached !== null) {
            return $cached;
        }
    } catch (\Predis\Connection\ConnectionException $e) {
        // // Redis غير متصل -> تجاهل الكاش
        Log::warning('SY4: Redis غير متصل، نقرأ من MySQL مباشرة');
    }

    // // قراءة من قاعدة البيانات
    [$group, $settingKey] = explode('.', $key, 2);
    $setting = SystemSetting::where('group', $group)
        ->where('key', $settingKey)
        ->first();

    return $setting ? $setting->getTypedValue() : $default;
}
```

## 7. حجم البيانات الكبير (Large Data)

```php
// // المشكلة: mail.smtp يحتوي على JSON كبير

// // عملياً، SMTP JSON لا يتجاوز 1KB
// // لكن القيمة النظرية يمكن أن تصل إلى 64KB (TEXT column)

// // لا توجد مشكلة أداء لأن:
// // 1. cache يخزن القيم المسلسلة
// // 2. MySQL TEXT يتسع لـ 64KB
// // 3. نادراً ما نقرأ إعداد واحد

// // لكن إذا كان هناك إعدادات JSON كبيرة جداً:
// // استخدم MEDIUMTEXT بدلاً من TEXT
// $table->mediumText('value')->nullable();
```
