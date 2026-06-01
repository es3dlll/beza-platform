# 05 - إنشاء جدول system_settings وإضافة القيم الافتراضية (Migrations)

## هيكل الملفات (Migration Files)

```
database/migrations/
├── 2024_01_01_000001_create_system_settings_table.php
└── 2024_01_01_000002_seed_default_system_settings.php
```

## ملف الهيكلة: إنشاء الجدول (Create Table Migration)

```php
<?php
// // ملف: database/migrations/2024_01_01_000001_create_system_settings_table.php
// // إنشاء جدول إعدادات النظام مع الفهارس المناسبة

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            
            // // مجموعة الإعداد: general, features, fees, limits, ...
            $table->string('group', 50);
            
            // // مفتاح فريد ضمن كل مجموعة
            $table->string('key', 100);
            
            // // القيمة مخزنة كنص وتحول حسب النوع
            $table->text('value')->nullable();
            
            // // string, integer, float, boolean, json
            $table->string('type', 20)->default('string');
            
            // // وصف الإعداد للتوثيق
            $table->text('description')->nullable();
            
            $table->timestamps();

            // // منع تكرار نفس المفتاح في نفس المجموعة
            $table->unique(['group', 'key'], 'uq_settings_group_key');
            
            // // تسريع البحث حسب المجموعة
            $table->index('group', 'idx_settings_group');
        });

        // // تعليق على الجدول للتوثيق في قاعدة البيانات
        DB::statement("ALTER TABLE `system_settings` 
            COMMENT = 'إعدادات النظام العامة: يتم تخزين جميع إعدادات المنصة هنا'");
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
```

## ملف التعبئة: إضافة القيم الافتراضية (Seed Default Settings)

```php
<?php
// // ملف: database/migrations/2024_01_01_000002_seed_default_system_settings.php
// // إضافة جميع الإعدادات الافتراضية للمنصة
// // يتم تشغيل هذا الملف مرة واحدة عند التثبيت

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $defaults = [
        // ========== الإعدادات العامة ==========
        ['group' => 'general',  'key' => 'app_name',        'value' => 'Beza',              'type' => 'string',  'description' => 'اسم التطبيق الرئيسي يظهر في العنوان والشعار'],
        ['group' => 'general',  'key' => 'app_description', 'value' => 'منصة بيزا للمعاملات المالية', 'type' => 'string', 'description' => 'وصف مختصر للتطبيق يظهر في محركات البحث'],
        ['group' => 'general',  'key' => 'app_logo',        'value' => '/img/logo.png',      'type' => 'string',  'description' => 'رابط شعار التطبيق'],
        ['group' => 'general',  'key' => 'app_favicon',     'value' => '/img/favicon.ico',   'type' => 'string',  'description' => 'أيقونة التطبيق في علامة التبويب'],
        ['group' => 'general',  'key' => 'timezone',        'value' => 'Asia/Riyadh',        'type' => 'string',  'description' => 'المنطقة الزمنية الافتراضية للمنصة'],
        ['group' => 'general',  'key' => 'locale',          'value' => 'ar',                 'type' => 'string',  'description' => 'اللغة الافتراضية: ar, en'],

        // ========== خصائص المنصة ==========
        ['group' => 'features', 'key' => 'gold',            'value' => '1',                  'type' => 'boolean', 'description' => 'تفعيل/تعطيل ميزة تداول الذهب'],
        ['group' => 'features', 'key' => 'deals',           'value' => '1',                  'type' => 'boolean', 'description' => 'تفعيل/تعطيل العروض والخصومات'],
        ['group' => 'features', 'key' => 'cards',           'value' => '1',                  'type' => 'boolean', 'description' => 'تفعيل/تعطيل البطاقات الافتراضية'],
        ['group' => 'features', 'key' => 'agents',          'value' => '1',                  'type' => 'boolean', 'description' => 'تفعيل/تعطيل نظام الوكلاء'],
        ['group' => 'features', 'key' => 'loans',           'value' => '0',                  'type' => 'boolean', 'description' => 'تفعيل/تعطيل نظام القروض'],

        // ========== نسب الرسوم ==========
        ['group' => 'fees',     'key' => 'p2p',             'value' => '0',                  'type' => 'float',   'description' => 'نسبة رسوم التحويل من شخص لشخص (0 = مجاني)'],
        ['group' => 'fees',     'key' => 'exchange',        'value' => '1.5',                'type' => 'float',   'description' => 'نسبة رسوم صرف العملات'],
        ['group' => 'fees',     'key' => 'card_deposit',    'value' => '2.5',                'type' => 'float',   'description' => 'نسبة رسوم الإيداع عبر البطاقة'],
        ['group' => 'fees',     'key' => 'withdrawal',      'value' => '1.0',                'type' => 'float',   'description' => 'نسبة رسوم السحب'],

        // ========== الحدود ==========
        ['group' => 'limits',   'key' => 'daily_transfer',  'value' => '100000',             'type' => 'integer', 'description' => 'الحد الأقصى للتحويل اليومي بالريال'],
        ['group' => 'limits',   'key' => 'max_wallet',      'value' => '500000',             'type' => 'integer', 'description' => 'الحد الأقصى لرصيد المحفظة'],
        ['group' => 'limits',   'key' => 'min_withdrawal',  'value' => '100',                'type' => 'integer', 'description' => 'الحد الأدنى للسحب'],

        // ========== إعدادات الصرف ==========
        ['group' => 'exchange', 'key' => 'margin',          'value' => '0.5',                'type' => 'float',   'description' => 'هامش الربح في صرف العملات (%)'],
        ['group' => 'exchange', 'key' => 'update_interval', 'value' => '300',                'type' => 'integer', 'description' => 'فترة تحديث أسعار الصرف بالثواني'],

        // ========== إعدادات الأمان ==========
        ['group' => 'security', 'key' => 'max_attempts',    'value' => '5',                  'type' => 'integer', 'description' => 'الحد الأقصى لمحاولات تسجيل الدخول الفاشلة'],
        ['group' => 'security', 'key' => 'lockout_minutes', 'value' => '30',                 'type' => 'integer', 'description' => 'مدة القفل بعد تجاوز المحاولات (دقائق)'],
        ['group' => 'security', 'key' => 'password_policy', 'value' => '{"min_length":8,"require_upper":true,"require_special":true}', 'type' => 'json', 'description' => 'سياسة كلمة المرور'],

        // ========== الإشعارات ==========
        ['group' => 'notifications', 'key' => 'default_channels', 'value' => '["push","email","sms"]', 'type' => 'json', 'description' => 'قنوات الإشعارات الافتراضية'],

        // ========== البريد الإلكتروني ==========
        ['group' => 'mail',    'key' => 'smtp',            'value' => '{"host":"","port":587,"encryption":"tls","username":"","password":"","from_address":"","from_name":""}', 'type' => 'json', 'description' => 'إعدادات SMTP للبريد الإلكتروني'],

        // ========== الصيانة ==========
        ['group' => 'maintenance', 'key' => 'mode',        'value' => '0',                  'type' => 'boolean', 'description' => 'تفعيل/تعطيل وضع الصيانة'],
        ['group' => 'maintenance', 'key' => 'message',     'value' => 'نظام بيزا تحت الصيانة حالياً. سنعود قريباً!', 'type' => 'string', 'description' => 'رسالة الصيانة التي تظهر للمستخدمين'],
        ['group' => 'maintenance', 'key' => 'allowed_ips', 'value' => '["127.0.0.1"]',       'type' => 'json',    'description' => 'عناوين IP المسموح لها بتجاوز وضع الصيانة'],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->defaults as $setting) {
            DB::table('system_settings')->insert(array_merge($setting, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->truncate();
    }
};
```

## ملاحظات مهمة (Important Notes)

```php
// // 1. استخدام migration بدلاً من seeder
// //    - migrations تضمن تشغيل الكود مرة واحدة فقط
// //    - يمكن الرجوع عنها via rollback

// // 2. القيم الافتراضية
// //    - gold = 1 (مفعل) لأن تداول الذهب ميزة أساسية
// //    - loans = 0 (معطل) لأن القروض تحتاج موافقات تنظيمية

// // 3. فريد (group + key)
// //    - يمنع تكرار الإعدادات عند إعادة تشغيل الميغريشن

// // 4. type field
// //    - يسمح بالتحويل التلقائي للأنواع في طبقة الخدمة
// //    - يحافظ على توافق البيانات مع الإصدارات القديمة
```
