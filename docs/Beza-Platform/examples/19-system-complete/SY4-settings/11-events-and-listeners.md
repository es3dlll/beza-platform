# 11 - الأحداث والمستمعات: SettingUpdated وإبطال التخزين المؤقت (Events & Listeners)

## نظرة عامة (Overview)

عند تحديث إعدادات النظام، يتم إرسال حدث `SettingUpdated` الذي يقوم مستمعوه بمسح التخزين المؤقت (Redis) للتأكد من أن الإعدادات الجديدة تصبح سارية المفعول فوراً.

```
تحديث الإعدادات -> حدث SettingUpdated -> مستمع -> مسح Redis Cache -> إعادة تحميل من DB
```

## حدث SettingUpdated (The Event)

```php
<?php
// // ملف: app/Events/SettingUpdated.php
// // يتم إرسال هذا الحدث بعد تحديث أي إعدادات نظام
// // يحمل معلومات المجموعة والبيانات المحدثة

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettingUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * // اسم المجموعة التي تم تحديثها
     * // مثلاً: general, fees, features
     */
    public string $group;

    /**
     * // البيانات الجديدة التي تم تحديثها
     * // مثلاً: ['app_name' => 'Beza', 'locale' => 'ar']
     */
    public array $data;

    /**
     * // وقت التحديث (timestamp)
     */
    public int $timestamp;

    /**
     * // إنشاء حدث جديد
     * 
     * // @param string $group  اسم المجموعة
     * // @param array  $data   البيانات المحدثة
     */
    public function __construct(string $group, array $data)
    {
        $this->group     = $group;
        $this->data      = $data;
        $this->timestamp = now()->timestamp;
    }

    /**
     * // الحصول على أسماء قنوات البث (اختياري)
     * // يمكن استخدام Broadcast للبث المباشر لواجهة الإدارة
     */
    public function broadcastOn(): array
    {
        return [
            'system.settings',
        ];
    }

    /**
     * // اسم حدث البث
     */
    public function broadcastAs(): string
    {
        return 'setting.updated';
    }
}
```

## مستمع InvalidateSettingsCache (The Listener)

```php
<?php
// // ملف: app/Listeners/InvalidateSettingsCache.php
// // مستمع حدث تحديث الإعدادات: يمسح التخزين المؤقت

namespace App\Listeners;

use App\Events\SettingUpdated;
use App\Services\Settings\SettingsCacheManager;
use Illuminate\Support\Facades\Log;

class InvalidateSettingsCache
{
    /**
     * // حقن مدير التخزين المؤقت
     */
    public function __construct(
        private SettingsCacheManager $cacheManager
    ) {}

    /**
     * // معالجة الحدث: مسح التخزين المؤقت للمجموعة المحدثة
     * 
     * // @param SettingUpdated $event
     * // @return void
     */
    public function handle(SettingUpdated $event): void
    {
        // // تسجيل عملية الإبطال
        Log::info('SY4: إبطال التخزين المؤقت للإعدادات', [
            'group'     => $event->group,
            'timestamp' => $event->timestamp,
        ]);

        // // مسح الكاش الكلي (جميع الإعدادات)
        $this->cacheManager->forgetGroup($event->group);

        // // مسح كل مفتاح فردي في البيانات المحدثة
        foreach ($event->data as $key => $value) {
            $fullKey = "{$event->group}.{$key}";
            $this->cacheManager->forget($fullKey);
        }

        Log::info('SY4: تم إبطال التخزين المؤقت بنجاح', [
            'group' => $event->group,
            'keys'  => array_keys($event->data),
        ]);
    }
}
```

## تسجيل الحدث والمستمع (Event Service Provider)

```php
<?php
// // ملف: app/Providers/EventServiceProvider.php
// // تسجيل الأحداث والمستمعات

namespace App\Providers;

use App\Events\SettingUpdated;
use App\Listeners\InvalidateSettingsCache;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * // خريطة الأحداث والمستمعات
     * // كل حدث يرتبط بمستمع واحد أو أكثر
     * 
     * // @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // // عند تحديث إعدادات النظام:
        // // 1. إبطال التخزين المؤقت في Redis
        // // 2. (اختياري) بث التغيير لواجهات الإدارة المفتوحة
        SettingUpdated::class => [
            InvalidateSettingsCache::class,
            // // يمكن إضافة مستمعات أخرى هنا:
            // // BroadcastSettingUpdate::class,
            // // LogSettingAuditTrail::class,
            // // NotifyAdmins::class,
        ],
    ];

    /**
     * // تسجيل أي أحداث إضافية
     */
    public function boot(): void
    {
        parent::boot();
    }
}
```

## تدفق الحدث بالكامل (Complete Event Flow)

```php
// // الخطوة 1: تحديث الإعدادات في SettingsService
$service->setGroup('fees', ['p2p' => 2.0, 'exchange' => 1.0]);

// // الخطوة 2: داخل setGroup() يتم:
DB::transaction(function () use ($group, $data) {
    // تحديث MySQL
    foreach ($data as $key => $value) {
        SystemSetting::upsertSetting($group, $key, $value);
    }
    
    // إرسال الحدث
    event(new SettingUpdated($group, $data));
});

// // الخطوة 3: EventServiceProvider يشحن المستمع
// // InvalidateSettingsCache::handle($event) يتنفذ

// // الخطوة 4: مسح الكاش
$this->cacheManager->forgetGroup('fees');
// // هذا يمسح:
// // - system_settings:all (الكاش الكلي)
// // - system_settings:fees.p2p
// // - system_settings:fees.exchange

// // الخطوة 5: في الطلب التالي، يتم إعادة تحميل الإعدادات من DB
$settings = $service->getAll();
// // لم يجد في الكاش -> يقرأ من MySQL -> يخزن في Redis
```

## أحداث إضافية محتملة (Additional Events)

```php
// // يمكن إضافة هذه الأحداث في المستقبل:

// // 1. SettingCreated - عند إنشاء إعداد جديد
// // 2. SettingDeleted - عند حذف إعداد
// // 3. SettingsBulkUpdated - عند تحديث جماعي
// // 4. SettingsCacheWarmed - عند تسخين الكاش

// // مثال على حدث إنشاء إعداد:
class SettingCreated
{
    public function __construct(
        public SystemSetting $setting
    ) {}
}

// // مثال على مستمع للتدقيق (Audit Trail):
class LogSettingAuditTrail
{
    public function handle(SettingUpdated $event): void
    {
        AuditLog::create([
            'action'    => 'setting_updated',
            'details'   => json_encode([
                'group' => $event->group,
                'data'  => $event->data,
            ]),
            'admin_id'  => auth()->id(),
        ]);
    }
}
```

## فوائد استخدام الأحداث (Benefits)

```php
// // 1. فصل المسؤوليات (Separation of Concerns)
// //    - الخدمة تهتم بالتحديث فقط
// //    - المستمع يهتم بإبطال الكاش فقط

// // 2. قابلية التوسع (Extensibility)
// //    - إضافة مستمع جديد لا يتغير الكود الموجود
// //    - مثلاً: إضافة تدقيق (audit) أو إشعارات

// // 3. الأداء (Performance)
// //    - المستمع يعمل بعد الاستجابة (إذا استخدم queue)
// //    - لا يؤخر الاستجابة للعميل

// // 4. الموثوقية (Reliability)
// //    - حتى لو فشل المستمع، التحديث في DB نجح
// //    - الكاش سينتهي صلاحيته بعد TTL ويتجدد تلقائياً
```
