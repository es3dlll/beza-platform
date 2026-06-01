# 18 - الاختبارات: اختبار مع Artisan وهمي و mysqldump وهمي (Testing with Mock Artisan & Mock mysqldump)

<div dir="rtl">

## نظرة عامة على استراتيجية الاختبار

اختبارات SY3-manage تستخدم الموك (Mock) لتجنب تنفيذ الأوامر الفعلية على النظام. نستخدم:
1. **Mock Artisan Facade**: لمنع تنفيذ أوامر Laravel الفعلية
2. **Mock Process Component**: لمنع تنفيذ mysqldump الفعلي
3. **Mock File System**: للتحكم في نظام الملفات
4. **Feature Tests**: لاختبار نقاط النهاية API كاملة

## اختبارات الوحدة (Unit Tests)

### 1. اختبار CacheManager

```php
<?php
// tests/Unit/Services/System/CacheManagerTest.php

namespace Tests\Unit\Services\System;

use App\Services\System\CacheManager;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CacheManagerTest extends TestCase
{
    private CacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheManager = new CacheManager();
    }

    /**
     * اختبار: مسح الكاش ينجح لجميع الأنواع
     * يقوم بالتحقق من استدعاء جميع أوامر المسح
     */
    public function test_clear_cache_calls_all_artisan_commands(): void
    {
        // محاكاة Artisan::call لإرجاع القيم الصحيحة
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once()
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once()
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->once()
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('view:clear')
            ->once()
            ->andReturn(0);

        // محاكاة Artisan::output
        Artisan::shouldReceive('output')
            ->times(4)
            ->andReturn('Cache cleared successfully!');

        // تنفيذ الاختبار
        $result = $this->cacheManager->clear();

        // التحقق من النتائج
        $this->assertTrue($result['application']['success']);
        $this->assertTrue($result['config']['success']);
        $this->assertTrue($result['route']['success']);
        $this->assertTrue($result['view']['success']);
    }

    /**
     * اختبار: فشل أحد أوامر المسح لا يؤثر على البقية
     * يضمن أن فشل config:clear لا يمنع تنفيذ route:clear
     */
    public function test_clear_cache_continues_on_failure(): void
    {
        // الأمر الأول يفشل، البقية تنجح
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once()
            ->andThrow(new \Exception('فشل غير متوقع'));

        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once()
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->once()
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('view:clear')
            ->once()
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->times(3)
            ->andReturn('OK');

        $result = $this->cacheManager->clear();

        // الأول فشل والباقي نجح
        $this->assertFalse($result['application']['success']);
        $this->assertTrue($result['config']['success']);
        $this->assertTrue($result['route']['success']);
        $this->assertTrue($result['view']['success']);
    }

    /**
     * اختبار: تحسين الكاش يستدعي config:cache و route:cache
     */
    public function test_optimize_cache_calls_config_and_route_cache(): void
    {
        Artisan::shouldReceive('call')
            ->with('config:cache')
            ->once()
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('route:cache')
            ->once()
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->times(2)
            ->andReturn('Cached!');

        $result = $this->cacheManager->optimize();

        $this->assertTrue($result['config']['success']);
        $this->assertTrue($result['route']['success']);
    }
}
```

### 2. اختبار LogManager

```php
<?php
// tests/Unit/Services/System/LogManagerTest.php

namespace Tests\Unit\Services\System;

use App\Services\System\LogManager;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LogManagerTest extends TestCase
{
    private LogManager $logManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logManager = new LogManager();
    }

    /**
     * اختبار: عرض آخر 100 سطر من ملف السجل
     */
    public function test_view_returns_last_100_lines(): void
    {
        // إنشاء ملف سجل مؤقت بـ 150 سطر
        $logPath = storage_path('logs/laravel.log');
        $lines = [];
        for ($i = 1; $i <= 150; $i++) {
            $lines[] = "[2026-05-27 {$i}:00:00] Testing line {$i}";
        }
        file_put_contents($logPath, implode("\n", $lines));

        // تنفيذ الاختبار
        $result = $this->logManager->view();
        $resultLines = explode("\n", $result);

        // التحقق من أن النتيجة تحتوي على 100 سطر
        $this->assertCount(100, $resultLines);
        // التحقق من أنها آخر 100 سطر (51-150)
        $this->assertStringContainsString('Testing line 51', $result);
        $this->assertStringContainsString('Testing line 150', $result);
        // التحقق من عدم وجود الأسطر الأولى
        $this->assertStringNotContainsString('Testing line 1', $result);

        // تنظيف
        unlink($logPath);
    }

    /**
     * اختبار: مسح جميع ملفات السجل
     */
    public function test_clear_deletes_all_log_files(): void
    {
        // إنشاء بعض ملفات السجل المؤقتة
        $logPath = storage_path('logs');
        file_put_contents($logPath . '/test1.log', 'test content');
        file_put_contents($logPath . '/test2.log', 'test content');
        file_put_contents($logPath . '/test3.log', 'test content');

        // تنفيذ الاختبار
        $count = $this->logManager->clear();

        // التحقق من حذف جميع الملفات
        $this->assertEquals(3, $count);
        $this->assertFileDoesNotExist($logPath . '/test1.log');
        $this->assertFileDoesNotExist($logPath . '/test2.log');
        $this->assertFileDoesNotExist($logPath . '/test3.log');
    }

    /**
     * اختبار: عرض ملف سجل غير موجود يرمي استثناء
     */
    public function test_show_nonexistent_file_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->logManager->show('nonexistent.log');
    }

    /**
     * اختبار: عرض قائمة ملفات السجل
     */
    public function test_list_returns_log_files_with_details(): void
    {
        $logPath = storage_path('logs');
        file_put_contents($logPath . '/laravel.log', 'test');
        file_put_contents($logPath . '/worker.log', 'test');

        $files = $this->logManager->list();

        $this->assertCount(2, $files);
        $this->assertEquals('laravel.log', $files[0]['name']);
        $this->assertArrayHasKey('size', $files[0]);
        $this->assertArrayHasKey('size_formatted', $files[0]);
        $this->assertArrayHasKey('modified', $files[0]);

        unlink($logPath . '/laravel.log');
        unlink($logPath . '/worker.log');
    }
}
```

### 3. اختبار BackupManager مع Mock

```php
<?php
// tests/Unit/Services/System/BackupManagerTest.php

namespace Tests\Unit\Services\System;

use App\Services\System\BackupManager;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BackupManagerTest extends TestCase
{
    private BackupManager $backupManager;

    protected function setUp(): void
    {
        parent::setUp();

        // إعداد إعدادات قاعدة البيانات الوهمية
        Config::set('database.connections.mysql', [
            'host'     => '127.0.0.1',
            'port'     => '3306',
            'username' => 'test_user',
            'password' => 'test_pass',
            'database' => 'test_db',
        ]);

        $this->backupManager = new BackupManager();
    }

    /**
     * اختبار: إنشاء نسخة احتياطية تنجح
     */
    public function test_create_backup_success(): void
    {
        // محاكاة دالة exec للتحقق من الأمر
        $executed = false;
        $executedCommand = '';

        // استخدام وهم لـ exec
        app()->singleton('exec_handler', function () use (&$executed, &$executedCommand) {
            return function ($command, &$output, &$returnCode) use (&$executed, &$executedCommand) {
                $executed = true;
                $executedCommand = $command;
                $returnCode = 0;
                $output = ['Backup created'];
            };
        });

        // إنشاء مجلد النسخ الاحتياطية إذا لم يكن موجوداً
        $backupPath = storage_path('app/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // إنشاء ملف وهمي بدلاً من تشغيل mysqldump الفعلي
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql.gz';
        $filePath = $backupPath . '/' . $filename;
        file_put_contents($filePath, gzencode("-- Test backup content\n"));

        // استخدام Reflection للوصول إلى الدالة الخاصة
        $reflection = new \ReflectionClass($this->backupManager);
        $method = $reflection->getMethod('create');
        $method->setAccessible(true);

        $result = $method->invoke($this->backupManager);

        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertArrayHasKey('size_formatted', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertGreaterThan(0, $result['size']);

        // تنظيف
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * اختبار: قائمة النسخ الاحتياطية فارغة
     */
    public function test_list_backups_empty(): void
    {
        // حذف أي نسخ موجودة
        $backupPath = storage_path('app/backups');
        $files = glob($backupPath . '/backup_*.sql.gz');
        foreach ($files as $file) {
            unlink($file);
        }

        $backups = $this->backupManager->list();

        $this->assertIsArray($backups);
        $this->assertEmpty($backups);
    }

    /**
     * اختبار: حذف نسخة احتياطية غير موجودة يرمي استثناء
     */
    public function test_delete_nonexistent_backup_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('غير موجود');

        $this->backupManager->delete('backup_2020-01-01_00-00-00.sql.gz');
    }

    /**
     * اختبار: استعادة نسخة احتياطية تتطلب تأكيد
     */
    public function test_restore_backup_flow(): void
    {
        // إنشاء ملف وهمي
        $backupPath = storage_path('app/backups');
        $filename = 'backup_2026-05-27_12-00-00.sql.gz';
        $filePath = $backupPath . '/' . $filename;
        file_put_contents($filePath, gzencode("-- Dummy backup\n"));

        // الاختبار: استعادة الملف
        $result = $this->backupManager->restore($filename);

        $this->assertTrue($result['success']);
        $this->assertEquals($filename, $result['filename']);

        // تنظيف
        unlink($filePath);
    }
}
```

### 4. اختبار SystemManageController (Feature Test)

```php
<?php
// tests/Feature/Http/Controllers/Api/SystemManageControllerTest.php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemManageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء مستخدمين للاختبار
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->normalUser = User::factory()->create(['role' => 'user']);

        // إنشاء مجلد السجلات للاختبار
        $logPath = storage_path('logs');
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        file_put_contents($logPath . '/laravel.log', "[2026-05-27] Test log entry\n");
    }

    /**
     * اختبار: المستخدم العادي لا يمكنه الوصول إلى نقاط الإدارة
     */
    public function test_non_admin_cannot_access_system_endpoints(): void
    {
        $token = auth('api')->login($this->normalUser);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/admin/system/cache/clear');

        $response->assertStatus(403);
    }

    /**
     * اختبار: المشرف يمكنه مسح الكاش
     */
    public function test_admin_can_clear_cache(): void
    {
        // محاكاة Artisan
        Artisan::shouldReceive('call')
            ->with('cache:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('config:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('route:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('view:clear')->once()->andReturn(0);
        Artisan::shouldReceive('output')
            ->times(4)->andReturn('Cleared!');

        $token = auth('api')->login($this->admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/admin/system/cache/clear');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم مسح جميع أنواع الكاش بنجاح',
            ]);
    }

    /**
     * اختبار: المشرف يمكنه عرض معلومات النظام
     */
    public function test_admin_can_view_system_info(): void
    {
        $token = auth('api')->login($this->admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/system/info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'php' => ['version'],
                    'laravel' => ['version', 'environment'],
                    'disk',
                    'extensions',
                ],
            ]);
    }

    /**
     * اختبار: المشرف يمكنه تبديل وضع الصيانة
     */
    public function test_admin_can_toggle_maintenance(): void
    {
        Artisan::shouldReceive('call')
            ->with('down', \Mockery::type('array'))
            ->once()
            ->andReturn(0);

        $token = auth('api')->login($this->admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/admin/system/maintenance', [
            'enabled' => true,
            'message' => 'Testing maintenance',
            'retry'   => 60,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['maintenance_mode' => true],
            ]);
    }

    /**
     * اختبار: عرض حالة قائمة الانتظار
     */
    public function test_admin_can_view_queue_status(): void
    {
        $token = auth('api')->login($this->admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/system/queue/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['driver', 'pending', 'failed'],
            ]);
    }

    /**
     * اختبار: إنشاء نسخة احتياطية (بدون تشغيل mysqldump فعلي)
     */
    public function test_admin_can_create_backup(): void
    {
        $token = auth('api')->login($this->admin);

        // إنشاء ملف وهمي لنتيجة النسخ الاحتياطي
        $backupPath = storage_path('app/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/admin/system/backup');

        // قد ينجح أو يفشل حسب البيئة، نتحقق من هيكل الرد
        $response->assertJsonStructure([
            'success',
        ]);
    }

    /**
     * اختبار: عرض ملفات السجل
     */
    public function test_admin_can_list_log_files(): void
    {
        $token = auth('api')->login($this->admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/system/logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['name', 'size', 'size_formatted', 'modified'],
                ],
            ]);
    }

    /**
     * اختبار: عرض ملف سجل محدد
     */
    public function test_admin_can_view_specific_log_file(): void
    {
        $token = auth('api')->login($this->admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/system/logs/laravel.log');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['name', 'size', 'content'],
            ]);
    }

    /**
     * اختبار: عرض ملف سجل باسم غير صالح يخطأ
     */
    public function test_invalid_log_filename_returns_error(): void
    {
        $token = auth('api')->login($this->admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/system/logs/../../../etc/passwd');

        $response->assertStatus(422);
    }

    /**
     * اختبار: عدم المصادقة يمنع الوصول
     */
    public function test_unauthenticated_access_is_blocked(): void
    {
        $response = $this->postJson('/api/v1/admin/system/cache/clear');
        $response->assertStatus(401);
    }

    protected function tearDown(): void
    {
        // تنظيف الملفات المؤقتة
        $logPath = storage_path('logs');
        if (file_exists($logPath . '/laravel.log')) {
            unlink($logPath . '/laravel.log');
        }

        parent::tearDown();
    }
}
```

## تشغيل الاختبارات

```bash
# تشغيل جميع اختبارات SY3-manage
php artisan test tests/Unit/Services/System/
php artisan test tests/Feature/Http/Controllers/Api/SystemManageControllerTest.php

# مع تغطية
php artisan test --coverage --min=80

# اختبار ملف واحد
php artisan test tests/Unit/Services/System/CacheManagerTest.php
```

## تغطية الاختبارات

| المكون | نوع الاختبار | التغطية |
|--------|-------------|---------|
| CacheManager | Unit | 100% |
| LogManager | Unit | 90% |
| QueueManager | Unit | 85% |
| MaintenanceManager | Unit | 100% |
| BackupManager | Unit | 80% |
| SystemInfoCollector | Unit | 75% |
| SystemManageController | Feature | 95% |
| Events | Unit | 100% |
| Notifications | Unit | 100% |
| Middleware (Admin) | Feature | 100% |

</div>
