# 12 - نظام الإشعارات: لا توجد إشعارات مطلوبة (Notification System)

## نظرة عامة (Overview)

على عكس معظم عمليات Beza الأخرى (مثل المعاملات والمستخدمين)، **لا يتطلب SY4-settings أي نظام إشعارات**. إعدادات النظام هي إعدادات داخلية يغيرها المسؤولون، وليست أحداثاً تحتاج إشعار المستخدمين.

```php
// // لماذا لا توجد إشعارات؟
// // - إعدادات النظام يعدلها المسؤولون فقط
// // - لا تؤثر التغييرات على مستخدم معين واحد
// // - لا حاجة لإرسال بريد أو SMS أو Push
// // - التغييرات تنعكس فوراً على كل المنصة
```

## التحليل (Analysis)

### متى نحتاج إشعارات؟ (When Are Notifications Needed?)

```php
// // السيناريوهات التي نحتاج فيها إشعارات في عمليات أخرى:
// // 
// // SY1-users:     إشعار ترحيبي لمستخدم جديد
// // SY2-wallets:   إشعار تغيير رصيد
// // SY3-exchange:  إشعار تنفيذ صفقة
// // SY5-support:   إشعار رد على تذكرة
// // SY6-agents:    إشعار طلب وكيل جديد
// //
// // SY4-settings:  لا توجد حالة تستدعي إشعار المستخدم
```

### متى قد نحتاج إشعارات مستقبلاً؟ (Future Possibilities)

```php
// // قد نحتاج إشعارات في المستقبل لهذه الحالات:
// // 
// // 1. تنبيه المسؤولين عند تغيير إعدادات الأمان
// //    -> مثلاً: تغيير max_attempts من 5 إلى 3
// //    -> إرسال إشعار لجميع المسؤولين
// //
// // 2. تنبيه عند تفعيل وضع الصيانة
// //    -> إعلام جميع المسؤولين قبل تفعيل الصيانة
// //
// // 3. سجل تدقيق (Audit Log) بدلاً من الإشعارات
// //    -> تسجيل كل تغيير مع معرف المسؤول والتوقيت
```

## البديل: سجل التدقيق (Audit Trail Instead)

بدلاً من الإشعارات، نستخدم سجل تدقيق لتتبع تغييرات الإعدادات:

```php
<?php
// // ملف: app/Services/Settings/SettingsAuditLogger.php
// // تسجيل تغييرات إعدادات النظام للتدقيق (بديل الإشعارات)

namespace App\Services\Settings;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsAuditLogger
{
    /**
     * // تسجيل تغيير في إعدادات النظام
     * // هذا يحل محل الحاجة للإشعارات
     * 
     * // @param string $group    المجموعة المحدثة
     * // @param array  $oldData  البيانات القديمة (قبل التحديث)
     * // @param array  $newData  البيانات الجديدة (بعد التحديث)
     * // @param int    $adminId  معرف المسؤول الذي أجرى التغيير
     */
    public function logChange(
        string $group,
        array $oldData,
        array $newData,
        int $adminId
    ): void {
        // // تسجيل في قاعدة بيانات التدقيق
        DB::table('audit_logs')->insert([
            'auditable_type' => 'system_settings',
            'auditable_id'   => 0, // لا يوجد ID محدد
            'event'          => 'setting_updated',
            'old_values'     => json_encode($oldData),
            'new_values'     => json_encode($newData),
            'group'          => $group,
            'admin_id'       => $adminId,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'created_at'     => now(),
        ]);

        // // تسجيل في سجل النظام (Log)
        Log::info('SY4: تغيير إعدادات النظام', [
            'group'    => $group,
            'admin_id' => $adminId,
            'changes'  => array_keys($newData),
            'ip'       => request()->ip(),
        ]);
    }

    /**
     * // مقارنة البيانات القديمة والجديدة
     * // ترجع فقط الحقول التي تغيرت فعلاً
     * 
     * // @return array  [field => ['old' => ..., 'new' => ...]]
     */
    public function getChanges(array $oldData, array $newData): array
    {
        $changes = [];

        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
```

## دمج التدقيق مع SettingsService

```php
<?php
// // إضافة التدقيق إلى SettingsService::setGroup()

public function setGroup(string $group, array $data): void
{
    // // الحصول على البيانات القديمة قبل التحديث
    $oldData = $this->getByGroup($group);

    DB::beginTransaction();

    try {
        foreach ($data as $key => $value) {
            $setting = SystemSetting::firstOrNew([
                'group' => $group,
                'key'   => $key,
            ]);
            $setting->setTypedValue($value);
            if (!$setting->exists) {
                $setting->type = $this->guessType($value);
            }
            $setting->save();
            $this->cacheManager->forget("{$group}.{$key}");
        }

        DB::commit();
        Event::dispatch(new SettingUpdated($group, $data));
        $this->cacheManager->forgetGroup($group);

        // // تسجيل التدقيق (بدون إشعارات)
        $adminId = auth()->id();
        if ($adminId) {
            $this->auditLogger->logChange(
                $group, $oldData, $data, $adminId
            );
        }

    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    }
}
```

## الخلاصة (Conclusion)

```php
// // SY4-settings لا يحتاج إشعارات للأسباب التالية:
// //
// // 1. الجمهور المستهدف: المسؤولون فقط
// //    -> المستخدمون العاديون لا يتأثرون بشكل مباشر
// //
// // 2. طبيعة التغييرات: إعدادات داخلية
// //    -> اسمها، نسب رسوم، حدود
// //    -> ليست أحداثاً تحتاج رد فعل فوري
// //
// // 3. البديل: سجل تدقيق شامل
// //    -> كل تغيير مسجل مع من ومتى
// //    -> يمكن مراجعة التغييرات في أي وقت
// //
// // 4. إذا احتجنا إشعارات مستقبلاً:
// //    -> أضف مستمعاً لحدث SettingUpdated
// //    -> أرسل إشعار للمسؤولين عبر القناة المناسبة
```
