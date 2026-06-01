# 18 - الاختبارات الشاملة: CRUD، الكاش، التحقق، الحالات الحدية (Complete Testing)

## نظرة عامة (Overview)

اختبارات شاملة لجميع وظائف SY4-settings: قراءة وكتابة الإعدادات، التخزين المؤقت، التحقق من الصحة، والحالات الحدية.

```php
// // الأدوات المستخدمة: PHPUnit, Mockery, RefreshDatabase
// // اختبارات Feature لـ API endpoints
// // اختبارات Unit للخدمات
```

## اختبارات وحدة التحكم (Controller Tests)

```php
<?php
// // ملف: tests/Feature/Admin/SystemSettingsControllerTest.php
// // اختبارات API لإعدادات النظام

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SystemSettingsControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // // إنشاء مستخدم مسؤول للاختبار
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->token = auth('api')->login($this->admin);
    }

    /** @test // عرض جميع الإعدادات */
    public function test_admin_can_view_all_settings(): void
    {
        // // ترتيب: إعدادات مسبقة من seeder
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);

        // // تنفيذ: طلب GET مع توكن
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/admin/system/settings');

        // // تأكيد: نجاح الطلب وعودة البيانات
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'general',
                    'features',
                    'fees',
                    'limits',
                ],
                'metadata' => [
                    'total_groups',
                    'total_settings',
                    'cache_status',
                ],
            ])
            ->assertJsonPath('data.general.app_name', 'Beza');
    }

    /** @test // عرض إعدادات مجموعة محددة */
    public function test_admin_can_view_settings_by_group(): void
    {
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/admin/system/settings/fees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'p2p',
                    'exchange',
                    'card_deposit',
                    'withdrawal',
                ],
            ]);
    }

    /** @test // تحديث إعدادات عامة */
    public function test_admin_can_update_general_settings(): void
    {
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);

        $newData = [
            'app_name' => 'Beza Plus',
            'locale'   => 'en',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/admin/system/settings/general', $newData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم تحديث إعدادات general بنجاح',
            ]);

        // // تأكيد تحديث قاعدة البيانات
        $this->assertDatabaseHas('system_settings', [
            'group' => 'general',
            'key'   => 'app_name',
            'value' => 'Beza Plus',
        ]);
    }

    /** @test // فشل التحقق من صحة البيانات */
    public function test_validation_fails_for_invalid_data(): void
    {
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);

        // // اسم تطبيق فارغ
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/admin/system/settings/general', [
            'app_name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'بيانات غير صالحة',
            ]);
    }

    /** @test // مجموعة غير معروفة */
    public function test_unknown_group_returns_400(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/admin/system/settings/unknown_group', []);

        $response->assertStatus(400);
    }

    /** @test // طلب بدون توكن */
    public function test_unauthenticated_request_fails(): void
    {
        $response = $this->getJson('/api/admin/system/settings');

        $response->assertStatus(401);
    }

    /** @test // طلب من مستخدم غير مسؤول */
    public function test_non_admin_user_cannot_access(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/system/settings');

        $response->assertStatus(403);
    }
}
```

## اختبارات الخدمة (Service Tests)

```php
<?php
// // ملف: tests/Unit/Services/SettingsServiceTest.php
// // اختبارات وحدة لخدمة إعدادات النظام

namespace Tests\Unit\Services;

use App\Models\SystemSetting;
use App\Services\Settings\SettingsService;
use App\Services\Settings\SettingsCacheManager;
use App\Services\Settings\SettingsValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $cacheMgr = $this->createMock(SettingsCacheManager::class);
        $validator = $this->createMock(SettingsValidator::class);

        $this->service = new SettingsService($cacheMgr, $validator);
    }

    /** @test // الحصول على إعداد واحد */
    public function test_can_get_single_setting(): void
    {
        // // ترتيب: إنشاء إعداد في قاعدة البيانات
        SystemSetting::create([
            'group' => 'general',
            'key'   => 'app_name',
            'value' => 'Beza',
            'type'  => 'string',
        ]);

        // // تنفيذ: قراءة الإعداد
        $value = $this->service->get('general.app_name', 'Default');

        // // تأكيد: القيمة صحيحة
        $this->assertEquals('Beza', $value);
    }

    /** @test // القيمة الافتراضية عند عدم وجود الإعداد */
    public function test_returns_default_when_setting_not_found(): void
    {
        $value = $this->service->get('nonexistent.key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    /** @test // تحديث مجموعة إعدادات */
    public function test_can_update_setting_group(): void
    {
        // // ترتيب: إعدادات مسبقة
        SystemSetting::create([
            'group' => 'fees',
            'key'   => 'p2p',
            'value' => '0',
            'type'  => 'float',
        ]);

        // // تنفيذ: تحديث
        $this->service->setGroup('fees', ['p2p' => 2.5]);

        // // تأكيد: القيمة تغيرت
        $this->assertDatabaseHas('system_settings', [
            'group' => 'fees',
            'key'   => 'p2p',
            'value' => '2.5',
        ]);
    }

    /** @test // إنشاء إعداد جديد عند عدم وجوده */
    public function test_creates_setting_if_not_exists(): void
    {
        $this->service->setGroup('fees', ['p2p' => 1.0]);

        $this->assertDatabaseHas('system_settings', [
            'group' => 'fees',
            'key'   => 'p2p',
            'value' => '1',
        ]);
    }
}
```

## اختبارات الكاش (Cache Tests)

```php
<?php
// // ملف: tests/Unit/Services/SettingsCacheManagerTest.php
// // اختبارات التخزين المؤقت

namespace Tests\Unit\Services;

use App\Services\Settings\SettingsCacheManager;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsCacheManagerTest extends TestCase
{
    private SettingsCacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheManager = new SettingsCacheManager();
    }

    /** @test // تخزين وقراءة إعداد من الكاش */
    public function test_cache_set_and_get(): void
    {
        $this->cacheManager->set('general.app_name', 'Beza');

        $value = $this->cacheManager->get('general.app_name');

        $this->assertEquals('Beza', $value);
    }

    /** @test // إبطال إعداد واحد */
    public function test_cache_forget_single_key(): void
    {
        $this->cacheManager->set('general.app_name', 'Beza');
        $this->cacheManager->forget('general.app_name');

        $this->assertNull($this->cacheManager->get('general.app_name'));
    }

    /** @test // إبطال مجموعة كاملة */
    public function test_cache_forget_group(): void
    {
        $this->cacheManager->setAll([
            'general' => ['app_name' => 'Beza'],
            'fees'    => ['p2p' => 0],
        ]);

        $this->cacheManager->forgetGroup('general');

        // // الكاش الكلي يجب أن يكون null
        $all = $this->cacheManager->getAll();
        $this->assertNull($all);
    }

    /** @test // التحقق من حالة الكاش */
    public function test_cache_is_warm_check(): void
    {
        $this->assertFalse($this->cacheManager->isWarm());

        $this->cacheManager->setAll(['test' => []]);

        $this->assertTrue($this->cacheManager->isWarm());
    }
}
```

## اختبارات التحقق (Validation Tests)

```php
<?php
// // ملف: tests/Unit/Services/SettingsValidatorTest.php
// // اختبارات التحقق من صحة الإعدادات

namespace Tests\Unit\Services;

use App\Services\Settings\SettingsValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SettingsValidatorTest extends TestCase
{
    private SettingsValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new SettingsValidator();
    }

    /** @test // تحقق صحيح للإعدادات العامة */
    public function test_valid_general_data_passes(): void
    {
        $data = [
            'app_name' => 'Beza',
            'timezone' => 'Asia/Riyadh',
            'locale'   => 'ar',
        ];

        $validated = $this->validator->validate('general', $data);

        $this->assertEquals($data['app_name'], $validated['app_name']);
    }

    /** @test // فشل تحقق اسم التطبيق الفارغ */
    public function test_empty_app_name_fails(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate('general', [
            'app_name' => '',
        ]);
    }

    /** @test // فشل تحقق منطقة زمنية غير صالحة */
    public function test_invalid_timezone_fails(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate('general', [
            'app_name' => 'Beza',
            'timezone' => 'Invalid/Zone',
        ]);
    }

    /** @test // نسبة رسوم خارج النطاق */
    public function test_fee_out_of_range_fails(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate('fees', [
            'p2p' => 150, // // أكثر من 100
        ]);
    }

    /** @test // مجموعة غير معروفة */
    public function test_unknown_group_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->validator->validate('unknown', []);
    }
}
```

## تشغيل الاختبارات (Running Tests)

```bash
# تشغيل جميع اختبارات SY4
php artisan test tests/Feature/Admin/SystemSettingsControllerTest.php
php artisan test tests/Unit/Services/SettingsServiceTest.php
php artisan test tests/Unit/Services/SettingsCacheManagerTest.php
php artisan test tests/Unit/Services/SettingsValidatorTest.php

# أو تشغيل جميع اختبارات النظام
php artisan test --filter="SystemSettings"
```
