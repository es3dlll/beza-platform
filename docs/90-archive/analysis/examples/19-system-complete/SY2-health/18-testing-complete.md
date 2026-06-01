# 18 - الاختبارات الشاملة (Complete Testing)

**الرمز التشغيلي:** SY2-health  
**النوع:** اختبارات (Testing)

---

## نظرة عامة (Overview)

يغطي هذا الملف جميع الاختبارات اللازمة لنظام التحقق الصحي. تستخدم PHPUnit مع Mockery لعزل الخدمات واختبار كل مدقق بشكل مستقل.

---

## اختبار HealthService

```php
<?php

namespace Tests\Unit\Services\Health;

use App\Services\Health\HealthService;
use App\Services\Health\Checkers\DatabaseChecker;
use App\Services\Health\Checkers\RedisChecker;
use App\Services\Health\Checkers\CacheChecker;
use App\Services\Health\Checkers\QueueChecker;
use App\Services\Health\Checkers\RequirementsChecker;
use App\Services\Health\Checkers\StorageChecker;
use App\Services\Health\ValueObjects\HealthCheckResult;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HealthServiceTest extends TestCase
{
    /** ترجمة: اختبار أن التقرير العام يعيد جميع الخدمات */
    public function test_general_health_report_returns_all_services(): void
    {
        // ترجمة: تجهيز المدققين mock
        $databaseChecker = $this->createMock(DatabaseChecker::class);
        $redisChecker = $this->createMock(RedisChecker::class);
        $cacheChecker = $this->createMock(CacheChecker::class);
        $queueChecker = $this->createMock(QueueChecker::class);
        $requirementsChecker = $this->createMock(RequirementsChecker::class);
        $storageChecker = $this->createMock(StorageChecker::class);

        // ترجمة: كل المدققين يعيدون نتيجة نجاح
        $databaseChecker->method('check')
            ->willReturn(HealthCheckResult::up('database', 1.5));
        $redisChecker->method('check')
            ->willReturn(HealthCheckResult::up('redis', 0.8));
        $cacheChecker->method('check')
            ->willReturn(HealthCheckResult::up('cache', 2.1));
        $queueChecker->method('check')
            ->willReturn(HealthCheckResult::up('queue', 0.0));
        $requirementsChecker->method('check')
            ->willReturn(HealthCheckResult::up('php_requirements', 0.0));
        $storageChecker->method('check')
            ->willReturn(HealthCheckResult::up('storage', 0.0));

        // ترجمة: تعطيل الكاش لهذا الاختبار
        Cache::shouldReceive('get')
            ->with('health:general')
            ->andReturn(null);
        Cache::shouldReceive('put')
            ->with('health:general', \Mockery::type('array'), 30);

        // ترجمة: إنشاء الخدمة
        $service = new HealthService(
            $databaseChecker,
            $redisChecker,
            $cacheChecker,
            $queueChecker,
            $requirementsChecker,
            $storageChecker,
        );

        // ترجمة: تنفيذ الفحص
        $report = $service->getGeneralHealthReport();

        // ترجمة: التحقق من النتائج
        $this->assertEquals('ok', $report['status']);
        $this->assertCount(6, $report['services']);
        $this->assertFalse($report['cached']);
        $this->assertArrayHasKey('timestamp', $report);
    }

    /** ترجمة: اختبار أن فشل خدمة واحدة لا يوقف باقي الفحص */
    public function test_one_service_down_does_not_crash_entire_check(): void
    {
        $databaseChecker = $this->createMock(DatabaseChecker::class);
        $redisChecker = $this->createMock(RedisChecker::class);
        $cacheChecker = $this->createMock(CacheChecker::class);
        $queueChecker = $this->createMock(QueueChecker::class);
        $requirementsChecker = $this->createMock(RequirementsChecker::class);
        $storageChecker = $this->createMock(StorageChecker::class);

        // ترجمة: قاعدة البيانات معطلة
        $databaseChecker->method('check')
            ->willReturn(HealthCheckResult::down('database', 'فشل الاتصال'));

        // ترجمة: باقي الخدمات تعمل
        $redisChecker->method('check')
            ->willReturn(HealthCheckResult::up('redis', 0.8));
        $cacheChecker->method('check')
            ->willReturn(HealthCheckResult::up('cache', 2.1));
        $queueChecker->method('check')
            ->willReturn(HealthCheckResult::up('queue', 0.0));
        $requirementsChecker->method('check')
            ->willReturn(HealthCheckResult::up('php_requirements', 0.0));
        $storageChecker->method('check')
            ->willReturn(HealthCheckResult::up('storage', 0.0));

        Cache::shouldReceive('get')->with('health:general')->andReturn(null);
        Cache::shouldReceive('put')->with('health:general', \Mockery::type('array'), 30);

        $service = new HealthService(
            $databaseChecker,
            $redisChecker,
            $cacheChecker,
            $queueChecker,
            $requirementsChecker,
            $storageChecker,
        );

        $report = $service->getGeneralHealthReport();

        // ترجمة: الحالة العامة down لأن قاعدة البيانات معطلة
        $this->assertEquals('down', $report['status']);
        $this->assertCount(6, $report['services']);

        // ترجمة: قاعدة البيانات down وباقي الخدمات up
        $dbService = collect($report['services'])->firstWhere('name', 'database');
        $this->assertEquals('down', $dbService['status']);

        $redisService = collect($report['services'])->firstWhere('name', 'redis');
        $this->assertEquals('up', $redisService['status']);
    }

    /** ترجمة: اختبار التخزين المؤقت */
    public function test_health_report_is_cached(): void
    {
        $databaseChecker = $this->createMock(DatabaseChecker::class);
        $redisChecker = $this->createMock(RedisChecker::class);
        $cacheChecker = $this->createMock(CacheChecker::class);
        $queueChecker = $this->createMock(QueueChecker::class);
        $requirementsChecker = $this->createMock(RequirementsChecker::class);
        $storageChecker = $this->createMock(StorageChecker::class);

        // ترجمة: محاكاة وجود نتائج مخزنة
        $cachedData = [
            'status' => 'ok',
            'services' => [],
            'timestamp' => '2026-01-01T00:00:00Z',
            'cached' => false,
        ];

        Cache::shouldReceive('get')
            ->with('health:general')
            ->andReturn($cachedData);

        $service = new HealthService(
            $databaseChecker,
            $redisChecker,
            $cacheChecker,
            $queueChecker,
            $requirementsChecker,
            $storageChecker,
        );

        $report = $service->getGeneralHealthReport();

        // ترجمة: النتيجة من الكاش (cached = true)
        $this->assertTrue($report['cached']);
        // ترجمة: لم يتم استدعاء أي من المدققين
        $this->assertEmpty($report['services']);
    }
}
```

---

## اختبار DatabaseChecker

```php
<?php

namespace Tests\Unit\Services\Health\Checkers;

use App\Services\Health\Checkers\DatabaseChecker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseCheckerTest extends TestCase
{
    /** ترجمة: اختبار نجاح الاتصال بقاعدة البيانات */
    public function test_database_connection_success(): void
    {
        // ترجمة: محاكاة استعلام ناجح
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1 AS health_check')
            ->andReturn([
                (object)['health_check' => 1],
            ]);

        DB::shouldReceive('select')
            ->once()
            ->with('SELECT VERSION() AS version')
            ->andReturn([
                (object)['version' => '8.0.32'],
            ]);

        $checker = new DatabaseChecker();
        $result = $checker->check();

        $this->assertTrue($result->isUp());
        $this->assertEquals('database', $result->name);
        $this->assertGreaterThan(0, $result->latency_ms);
    }

    /** ترجمة: اختبار فشل الاتصال بقاعدة البيانات */
    public function test_database_connection_failure(): void
    {
        // ترجمة: محاكاة فشل الاتصال
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1 AS health_check')
            ->andThrow(new \Exception('فشل الاتصال: Connection refused'));

        $checker = new DatabaseChecker();
        $result = $checker->check();

        $this->assertTrue($result->isDown());
        $this->assertStringContainsString('فشل', $result->error ?? '');
    }

    /** ترجمة: اختبار بطء الاستعلام */
    public function test_database_slow_query_detection(): void
    {
        // ترجمة: محاكاة استعلام بطيء (سنقبل أي نتيجة، just checking it works)
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1 AS health_check')
            ->andReturnUsing(function () {
                usleep(500000); // 500ms delay
                return [(object)['health_check' => 1]];
            });

        DB::shouldReceive('select')
            ->once()
            ->with('SELECT VERSION() AS version')
            ->andReturn([(object)['version' => '8.0.32']]);

        $checker = new DatabaseChecker();
        $result = $checker->check();

        $this->assertTrue($result->isUp());
        $this->assertGreaterThan(400, $result->latency_ms);
    }
}
```

---

## اختبار RedisChecker

```php
<?php

namespace Tests\Unit\Services\Health\Checkers;

use App\Services\Health\Checkers\RedisChecker;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisCheckerTest extends TestCase
{
    /** ترجمة: اختبار نجاح اتصال Redis */
    public function test_redis_connection_success(): void
    {
        $connection = $this->mock(\Illuminate\Redis\Connections\PredisConnection::class);
        $connection->shouldReceive('ping')->once()->andReturn('PONG');

        Redis::shouldReceive('connection')
            ->once()
            ->withNoArgs()
            ->andReturn($connection);

        $checker = new RedisChecker();
        $result = $checker->check();

        $this->assertTrue($result->isUp());
        $this->assertEquals('redis', $result->name);
    }

    /** ترجمة: اختبار فشل اتصال Redis */
    public function test_redis_connection_failure(): void
    {
        Redis::shouldReceive('connection')
            ->once()
            ->andThrow(new \Exception('Cannot connect to Redis'));

        $checker = new RedisChecker();
        $result = $checker->check();

        $this->assertTrue($result->isDown());
        $this->assertStringContainsString('Redis', $result->error ?? '');
    }

    /** ترجمة: اختبار ping لا يعيد PONG */
    public function test_redis_unexpected_ping_response(): void
    {
        $connection = $this->mock(\Illuminate\Redis\Connections\PredisConnection::class);
        $connection->shouldReceive('ping')->once()->andReturn('UNKNOWN');

        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($connection);

        $checker = new RedisChecker();
        $result = $checker->check();

        $this->assertTrue($result->isDegraded());
    }
}
```

---

## اختبار CacheChecker

```php
<?php

namespace Tests\Unit\Services\Health\Checkers;

use App\Services\Health\Checkers\CacheChecker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheCheckerTest extends TestCase
{
    /** ترجمة: اختبار نجاح كتابة وقراءة الكاش */
    public function test_cache_write_read_success(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->with(CacheChecker::TEST_KEY, CacheChecker::TEST_VALUE, 10)
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->with(CacheChecker::TEST_KEY)
            ->andReturn(CacheChecker::TEST_VALUE);

        Cache::shouldReceive('forget')
            ->once()
            ->with(CacheChecker::TEST_KEY)
            ->andReturn(true);

        $checker = new CacheChecker();
        $result = $checker->check();

        $this->assertTrue($result->isUp());
        $this->assertEquals('cache', $result->name);
    }

    /** ترجمة: اختبار فشل كتابة الكاش */
    public function test_cache_write_failure(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->andReturn(false);

        $checker = new CacheChecker();
        $result = $checker->check();

        $this->assertTrue($result->isDown());
    }

    /** ترجمة: اختبار عدم تطابق القراءة */
    public function test_cache_read_mismatch(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->andReturn('WRONG_VALUE');

        Cache::shouldReceive('forget')
            ->once()
            ->andReturn(true);

        $checker = new CacheChecker();
        $result = $checker->check();

        $this->assertTrue($result->isDegraded());
    }
}
```

---

## اختبار QueueChecker

```php
<?php

namespace Tests\Unit\Services\Health\Checkers;

use App\Services\Health\Checkers\QueueChecker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueCheckerTest extends TestCase
{
    /** ترجمة: اختبار نجاح الاتصال بقائمة الانتظار */
    public function test_queue_connection_success(): void
    {
        Queue::shouldReceive('getDefaultDriver')
            ->once()
            ->andReturn('redis');

        $connection = $this->mock(\Illuminate\Queue\RedisQueue::class);
        $connection->shouldReceive('size')->once()->andReturn(5);

        Queue::shouldReceive('connection')
            ->once()
            ->with('redis')
            ->andReturn($connection);

        $checker = new QueueChecker();
        $result = $checker->check();

        $this->assertTrue($result->isUp());
        $this->assertEquals('queue', $result->name);
    }

    /** ترجمة: اختبار فشل الاتصال بقائمة الانتظار */
    public function test_queue_connection_failure(): void
    {
        Queue::shouldReceive('getDefaultDriver')
            ->once()
            ->andReturn('redis');

        Queue::shouldReceive('connection')
            ->once()
            ->with('redis')
            ->andThrow(new \Exception('Connection refused'));

        $checker = new QueueChecker();
        $result = $checker->check();

        $this->assertTrue($result->isDown());
    }
}
```

---

## اختبار RequirementsChecker

```php
<?php

namespace Tests\Unit\Services\Health\Checkers;

use App\Services\Health\Checkers\RequirementsChecker;
use Tests\TestCase;

class RequirementsCheckerTest extends TestCase
{
    /** ترجمة: اختبار أن PHP يعمل بالإصدار المطلوب */
    public function test_php_version_check(): void
    {
        // ترجمة: نختبر أن الإصدار الحالي يعمل (مهما كان)
        $checker = new RequirementsChecker();
        $result = $checker->check();

        $this->assertContains($result->status, ['up', 'degraded']);
        $this->assertEquals('php_requirements', $result->name);
        $this->assertArrayHasKey('php_version', $result->details);
        $this->assertArrayHasKey('extensions', $result->details);
    }

    /** ترجمة: اختبار الإضافات المطلوبة */
    public function test_required_extensions_check(): void
    {
        $checker = new RequirementsChecker();
        $result = $checker->check();

        $extensions = $result->details['extensions'];

        // ترجمة: هذه الإضافات يجب أن تكون محملة دائماً في Laravel
        $this->assertTrue($extensions['pdo'] ?? false);
        $this->assertTrue($extensions['json'] ?? false);
        $this->assertTrue($extensions['mbstring'] ?? false);
        $this->assertTrue($extensions['openssl'] ?? false);
    }
}
```

---

## اختبار StorageChecker

```php
<?php

namespace Tests\Unit\Services\Health\Checkers;

use App\Services\Health\Checkers\StorageChecker;
use Tests\TestCase;

class StorageCheckerTest extends TestCase
{
    /** ترجمة: اختبار فحص التخزين */
    public function test_storage_check(): void
    {
        $checker = new StorageChecker();
        $result = $checker->check();

        // ترجمة: التخزين يجب أن يكون قابلاً للكتابة في بيئة الاختبار
        $this->assertContains($result->status, ['up', 'degraded']);
        $this->assertEquals('storage', $result->name);
        $this->assertArrayHasKey('directories', $result->details);

        // ترجمة: التحقق من أن storage/logs قابل للكتابة
        $directories = $result->details['directories'];
        $logsDir = collect($directories)->firstWhere(
            'path',
            storage_path('logs')
        );
        $this->assertNotNull($logsDir);
        $this->assertTrue($logsDir['writable']);
    }
}
```

---

## اختبار HealthController (Integration)

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class HealthControllerTest extends TestCase
{
    /** ترجمة: اختبار نقطة النهاية العامة للتحقق الصحي */
    public function test_general_health_endpoint(): void
    {
        $response = $this->getJson('/api/system/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'services' => [
                '*' => ['name', 'status', 'latency_ms'],
            ],
            'timestamp',
            'cached',
        ]);
    }

    /** ترجمة: اختبار نقطة النهاية الفردية للقاعدة */
    public function test_db_health_endpoint(): void
    {
        $response = $this->getJson('/api/system/health/db');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'service',
            'latency_ms',
            'timestamp',
        ]);
    }

    /** ترجمة: اختبار نقطة نهاية Redis */
    public function test_redis_health_endpoint(): void
    {
        $response = $this->getJson('/api/system/health/redis');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'service',
            'latency_ms',
            'timestamp',
        ]);
    }

    /** ترجمة: اختبار نقطة نهاية الكاش */
    public function test_cache_health_endpoint(): void
    {
        $response = $this->getJson('/api/system/health/cache');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'service',
            'latency_ms',
            'timestamp',
        ]);
    }

    /** ترجمة: اختبار نقطة نهاية قائمة الانتظار */
    public function test_queue_health_endpoint(): void
    {
        $response = $this->getJson('/api/system/health/queue');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'service',
            'latency_ms',
            'timestamp',
        ]);
    }

    /** ترجمة: اختبار نقطة نهاية المتطلبات */
    public function test_requirements_health_endpoint(): void
    {
        $response = $this->getJson('/api/system/health/requirements');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'service',
            'php_version',
            'extensions',
            'timestamp',
        ]);
    }

    /** ترجمة: اختبار نقطة نهاية التخزين */
    public function test_storage_health_endpoint(): void
    {
        $response = $this->getJson('/api/system/health/storage');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'service',
            'directories',
            'timestamp',
        ]);
    }

    /** ترجمة: اختبار نقطة نهاية المشرف (بدون توكن) */
    public function test_admin_health_endpoint_without_token(): void
    {
        $response = $this->getJson('/api/admin/system/health');

        $response->assertStatus(401);
    }

    /** ترجمة: اختبار نقطة نهاية المشرف (مع توكن غير admin) */
    public function test_admin_health_endpoint_with_user_token(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => 'user',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader(
            'Authorization', "Bearer {$token}"
        )->getJson('/api/admin/system/health');

        $response->assertStatus(403);
    }
}
```

---

## ملخص التغطية الاختبارية (Test Coverage Summary)

| الاختبار (Test) | يختبر (Tests) | الحالة (Status) |
|----------------|--------------|----------------|
| `HealthServiceTest` | الخدمة الرئيسية مع كل الحالات | مكتمل |
| `DatabaseCheckerTest` | اتصال MySQL - نجاح، فشل، بطء | مكتمل |
| `RedisCheckerTest` | اتصال Redis - نجاح، فشل، ping غير متوقع | مكتمل |
| `CacheCheckerTest` | كاش - نجاح، فشل كتابة، عدم تطابق | مكتمل |
| `QueueCheckerTest` | قائمة انتظار - نجاح، فشل | مكتمل |
| `RequirementsCheckerTest` | PHP - إصدار، إضافات | مكتمل |
| `StorageCheckerTest` | تخزين - صلاحيات الكتابة | مكتمل |
| `HealthControllerTest` | نقاط النهاية الكاملة | مكتمل |
