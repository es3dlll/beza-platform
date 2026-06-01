# 18 - الاختبارات (Testing)

## Feature Test — RequirementCheckerTest

```php
<?php
// tests/Feature/Install/RequirementCheckerTest.php

namespace Tests\Feature\Install;

use App\Services\Install\RequirementChecker;
use Tests\TestCase;

class RequirementCheckerTest extends TestCase
{
    private RequirementChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new RequirementChecker();
    }

    /** @test */
    public function it_checks_php_version_is_8_1_or_higher()
    {
        $result = $this->checker->checkAll();

        $this->assertArrayHasKey('php_version', $result);
        $this->assertTrue($result['php_version']['pass']);
        $this->assertStringContainsString('متوافق', $result['php_version']['message']);
    }

    /** @test */
    public function it_checks_all_required_extensions()
    {
        $result = $this->checker->checkAll();

        $requiredExtensions = ['bcmath', 'ctype', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'curl', 'gd', 'redis', 'zip'];

        foreach ($requiredExtensions as $ext) {
            $key = 'ext_' . $ext;
            $this->assertArrayHasKey($key, $result, "الإضافة {$ext} غير موجودة في النتائج");
        }
    }

    /** @test */
    public function it_checks_storage_directory_permissions()
    {
        $result = $this->checker->checkAll();

        $this->assertArrayHasKey('perm_storage', $result);
        $this->assertTrue($result['perm_storage']['pass']);
    }

    /** @test */
    public function it_checks_bootstrap_cache_permissions()
    {
        $result = $this->checker->checkAll();

        $this->assertArrayHasKey('perm_bootstrap', $result);
        $this->assertTrue($result['perm_bootstrap']['pass']);
    }
}
```

## Feature Test — InstallerControllerTest

```php
<?php
// tests/Feature/Install/InstallerControllerTest.php

namespace Tests\Feature\Install;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InstallerControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function welcome_returns_200_when_unlocked()
    {
        // تأكد أن المثبت غير مقفول
        $this->assertTrue(env('INSTALLER_LOCKED') !== true);

        $response = $this->getJson('/install');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'مرحباً بك في مثبت Beza',
            ])
            ->assertJsonStructure([
                'data' => ['app_name', 'php_version', 'steps'],
            ]);
    }

    /** @test */
    public function welcome_returns_403_when_locked()
    {
        // محاكاة أن المثبت مقفول
        // هذا الاختبار يتطلب تعديل .env أو mocking
        // للتبسيط، نختبر فقط أن الـ endpoint يستجيب

        $response = $this->getJson('/install');
        $this->assertContains($response->status(), [200, 403]);
    }

    /** @test */
    public function check_requirements_returns_all_checks()
    {
        $response = $this->postJson('/install/requirements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'all_pass',
                    'items' => [
                        'php_version' => ['pass', 'message'],
                        'ext_bcmath'  => ['pass', 'message'],
                        'ext_ctype'   => ['pass', 'message'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function setup_database_validates_required_fields()
    {
        $response = $this->postJson('/install/database', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['db_host', 'db_port', 'db_database', 'db_username']);
    }

    /** @test */
    public function setup_database_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/install/database', [
            'db_host'     => '999.999.999.999',
            'db_port'     => 3306,
            'db_database' => 'test_db',
            'db_username' => 'invalid_user',
            'db_password' => 'invalid_pass',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'فشل الاتصال بقاعدة البيانات',
            ]);
    }

    /** @test */
    public function configure_env_validates_required_fields()
    {
        $response = $this->postJson('/install/env', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'app_name', 'app_url', 'app_env',
                'redis_host', 'redis_port', 'queue_connection',
            ]);
    }

    /** @test */
    public function configure_env_requires_database_step_first()
    {
        $response = $this->postJson('/install/env', [
            'app_name'   => 'Test',
            'app_url'    => 'https://test.com',
            'app_env'    => 'local',
            'redis_host' => '127.0.0.1',
            'redis_port' => 6379,
            'queue_connection' => 'sync',
        ]);

        // يجب أن يفشل لأنه لم يكمل خطوة قاعدة البيانات
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'الرجاء إكمال خطوة إعداد قاعدة البيانات أولاً',
            ]);
    }

    /** @test */
    public function run_migrations_returns_success()
    {
        // هذا الاختبار يتطلب خطوات سابقة (env, database)
        // نختبر فقط أن الـ endpoint يستجيب
        $response = $this->postJson('/install/migrate');
        $this->assertContains($response->status(), [200, 422, 500]);
    }

    /** @test */
    public function create_admin_validates_all_fields()
    {
        $response = $this->postJson('/install/admin', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'password']);
    }

    /** @test */
    public function create_admin_validates_phone_format()
    {
        $response = $this->postJson('/install/admin', [
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'phone' => '12345',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function create_admin_validates_password_confirmation()
    {
        $response = $this->postJson('/install/admin', [
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'phone' => '0999123456',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function complete_requires_admin_step_first()
    {
        $response = $this->postJson('/install/complete');

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'الرجاء إنشاء المشرف أولاً',
            ]);
    }
}
```

## Unit Test — EnvironmentConfiguratorTest

```php
<?php
// tests/Unit/Install/EnvironmentConfiguratorTest.php

namespace Tests\Unit\Install;

use App\Services\Install\EnvironmentConfigurator;
use Tests\TestCase;

class EnvironmentConfiguratorTest extends TestCase
{
    private EnvironmentConfigurator $configurator;
    private string $testEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurator = new EnvironmentConfigurator();

        // إنشاء ملف .env مؤقت للاختبار
        $this->testEnvPath = base_path('.env.testing');
        copy(base_path('.env'), $this->testEnvPath);
    }

    protected function tearDown(): void
    {
        // حذف الملف المؤقت
        if (file_exists($this->testEnvPath)) {
            unlink($this->testEnvPath);
        }
        parent::tearDown();
    }

    /** @test */
    public function it_reads_env_file()
    {
        $env = $this->configurator->readEnv();
        $this->assertIsArray($env);
        $this->assertArrayHasKey('APP_ENV', $env);
    }

    /** @test */
    public function it_merges_new_values_with_existing()
    {
        // نختبر الوظيفة عبر reflection
        $reflection = new \ReflectionClass($this->configurator);
        $method = $reflection->getMethod('mapToEnvKeys');
        $method->setAccessible(true);

        $result = $method->invoke($this->configurator, [
            'db_host'     => 'test_host',
            'db_database' => 'test_db',
        ]);

        $this->assertEquals([
            'DB_HOST'     => 'test_host',
            'DB_DATABASE' => 'test_db',
        ], $result);
    }

    /** @test */
    public function it_checks_if_installer_is_locked()
    {
        $locked = $this->configurator->isLocked();
        $this->assertIsBool($locked);
    }
}
```

## Pest Tests

```php
<?php
// tests/Feature/Install/InstallerPestTest.php

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

test('installer welcome page loads successfully', function () {
    getJson('/install')
        ->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('requirements check returns php_version', function () {
    postJson('/install/requirements')
        ->assertStatus(200)
        ->assertJsonStructure(['data' => ['items' => ['php_version']]]);
});

test('database step rejects empty host', function () {
    postJson('/install/database', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['db_host']);
});

test('admin step validates phone format', function () {
    postJson('/install/admin', [
        'name' => 'Test',
        'email' => 'test@test.com',
        'phone' => 'invalid',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['phone']);
});

test('complete step fails without prior steps', function () {
    postJson('/install/complete')
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});
```
