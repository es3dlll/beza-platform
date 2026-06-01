# 14 - معاملات قاعدة البيانات: ضمان الذرية في تحديث الإعدادات (Database Transactions & ACID)

## نظرة عامة (Overview)

عند تحديث مجموعة إعدادات كاملة (مثلاً: كل إعدادات fees في طلب واحد)، يجب ضمان أن **كل التغييرات تنجح معاً أو تتراجع معاً**. هذا هو مبدأ **الذرية (Atomicity)** في ACID.

```php
// // بدون transactions:
// // fees.p2p = 2.0      -> نجح
// // fees.exchange = 1.5 -> نجح
// // fees.card_deposit   -> فشل !!!
// // النتيجة: بعض الإعدادات تغيرت والبعض الآخر لا
// // -> حالة غير متناسقة (inconsistent state)

// // مع transactions:
// // BEGIN TRANSACTION
// // fees.p2p = 2.0      -> نجح
// // fees.exchange = 1.5 -> نجح
// // fees.card_deposit   -> فشل
// // ROLLBACK -> كل التغييرات تتراجع
// // النتيجة: النظام في حالة متناسقة
```

## تنفيذ المعاملات في SettingsService

```php
<?php
// // مقتطف من SettingsService::setGroup() مع transaction كاملة

use Illuminate\Support\Facades\DB;

public function setGroup(string $group, array $data): void
{
    // // نقطة تفتيش: تسجيل البيانات القديمة للتدقيق
    $oldData = $this->getByGroup($group);

    // // بدء المعاملة
    DB::beginTransaction();

    try {
        // // الخطوة 1: تحديث كل إعداد في المجموعة
        foreach ($data as $key => $value) {
            $fullKey = "{$group}.{$key}";

            // // البحث أو إنشاء الإعداد
            $setting = SystemSetting::firstOrNew([
                'group' => $group,
                'key'   => $key,
            ]);

            // // تعيين القيمة مع التحويل حسب النوع
            $setting->setTypedValue($value);

            // // إذا كان جديداً، نخمن النوع
            if (!$setting->exists) {
                $setting->type = $this->guessType($value);
                $setting->description = "إعداد {$key} في مجموعة {$group}";
            }

            // // حفظ في قاعدة البيانات
            $setting->save();

            // // مسح الكاش الفردي
            $this->cacheManager->forget($fullKey);
        }

        // // الخطوة 2: تأكيد كل التغييرات
        DB::commit();

        // // الخطوة 3: إرسال الأحداث (بعد commit)
        Event::dispatch(new SettingUpdated($group, $data));
        
        // // مسح الكاش الكلي
        $this->cacheManager->forgetGroup($group);

        // // تسجيل التدقيق
        if ($adminId = auth()->id()) {
            $this->auditLogger->logChange(
                $group, $oldData, $data, $adminId
            );
        }

    } catch (\Throwable $e) {
        // // التراجع عن كل التغييرات
        DB::rollBack();

        // // إعادة تعيين الكاش (قد يكون بعض المفاتيح مسحت)
        $this->cacheManager->forgetGroup($group);

        // // تسجيل الخطأ
        Log::error('SY4: فشل transaction تحديث الإعدادات', [
            'group'     => $group,
            'data'      => $data,
            'error'     => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ]);

        // // إرسال للـ Rollbar/Sentry (إن وجد)
        if (app()->bound('sentry')) {
            \Sentry\captureException($e);
        }

        throw new SettingUpdateException(
            "فشل تحديث إعدادات {$group}: {$e->getMessage()}",
            previous: $e
        );
    }
}
```

## ضمان ACID في SY4-settings

### 1. Atomicity (الذرية)

```php
// // كل updateOrInsert داخل نفس transaction
// // إما كلها تنجح أو كلها تتراجع
// // لا يمكن أن يكون نصف الإعدادات محدثاً

DB::beginTransaction();
try {
    foreach ($data as $key => $value) {
        // // كل عملية upsert
        DB::table('system_settings')->updateOrInsert(
            ['group' => $group, 'key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }
    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    throw $e;
}
```

### 2. Consistency (الاتساق)

```php
// // بعد التحديث الناجح:
// // 1. جميع الإعدادات في الحالة الجديدة
// // 2. الكاش محدث (أو مسح لتحديث لاحق)
// // 3. سجل التدقيق يحتوي على التغيير
// // 4. كل قواعد التحقق مطبقة

// // القيود التي تضمن الاتساق:
// // - UNIQUE (group, key): لا تكرار
// // - type: يحدد كيفية تفسير value
// // - التحقق: يمنع القيم غير الصالحة
```

### 3. Isolation (العزل)

```php
// // MySQL InnoDB يوفر عزل على مستوى الصف (Row-level locking)
// // عندما يحدث مسؤول الإعدادات:
// // - الصفوف المحدثة مقفولة للتحديث
// // - القراءات تستمر دون مشاكل (لا تنتظر)
// // - مسؤول آخر لا يستطيع تحديث نفس الإعداد في نفس الوقت

DB::transaction(function () use ($group, $data) {
    foreach ($data as $key => $value) {
        // // استخدام updateOrInsert مع lockForUpdate (اختياري)
        SystemSetting::where('group', $group)
            ->where('key', $key)
            ->lockForUpdate()
            ->first();
            
        SystemSetting::upsertSetting($group, $key, $value);
    }
});
```

### 4. Durability (الديمومة)

```php
// // بعد commit:
// // 1. البيانات مكتوبة على القرص (MySQL Binary Log)
// // 2. يمكن استرجاعها حتى بعد انقطاع الكهرباء
// // 3. النسخ الاحتياطي يضمن عدم فقدان التغييرات

// // إعدادات MySQL الموصى بها:
// // innodb_flush_log_at_trx_commit = 1
// // sync_binlog = 1
// // هذه تضمن أن كل commit مكتوب فعلياً على القرص
```

## التعامل مع الصراع (Concurrency Handling)

```php
// // السيناريو: مسؤولان يحدثان نفس الإعداد في نفس الوقت

// // Admin A: PUT /admin/system/settings/fees  {p2p: 2.0}
// // Admin B: PUT /admin/system/settings/fees  {p2p: 3.0}

// // الترتيب الزمني:
// // 1. Admin A يبدأ transaction
// // 2. Admin A يقرأ fees.p2p (lockForUpdate)
// // 3. Admin B يحاول بدأ transaction -> ينتظر
// // 4. Admin A يكتب p2p = 2.0
// // 5. Admin A commit
// // 6. Admin B transaction تبدأ -> p2p الآن = 2.0
// // 7. Admin B يكتب p2p = 3.0
// // 8. Admin B commit -> القيمة النهائية 3.0

// // هذا مقبول: آخر تحديث يفوز (Last Write Wins)
// // لا يوجد فقدان للبيانات ولا حالة غير مستقرة
```

## مثال كامل: تحديث متعدد المجموعات (اختياري)

```php
// // قد نحتاج في المستقبل تحديث مجموعات متعددة في
// // طلب واحد. هذا يتطلب transaction على مستوى أكبر:

public function updateMultiple(array $groupsData): void
{
    DB::beginTransaction();

    try {
        foreach ($groupsData as $group => $data) {
            // // تحقق لكل مجموعة
            $validated = $this->validator->validate($group, $data);

            foreach ($validated as $key => $value) {
                SystemSetting::upsertSetting($group, $key, $value);
            }
        }

        DB::commit();

        // // مسح كل الكاش بعد التحديث المتعدد
        $this->cacheManager->flush();

    } catch (\Throwable $e) {
        DB::rollBack();
        throw new SettingUpdateException(
            'فشل التحديث المتعدد: ' . $e->getMessage()
        );
    }
}
```
