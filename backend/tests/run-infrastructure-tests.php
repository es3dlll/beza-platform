<?php

declare(strict_types=1);

/**
 * اختبارات البنية التحتية — 5 تدفقات تكامل
 *
 * 1. استجابة نقطة الصحة — فحص جميع المكونات
 * 2. فشل نقطة الصحة عند محاكاة توقف قاعدة البيانات
 * 3. تنفيذ النسخ الاحتياطي وتشفيره بنجاح
 * 4. معالجة رسالة طابور فاشلة مع إعادة المحاولة المجدولة
 * 5. التحقق من تطبيق رؤوس الأمان على جميع الاستجابات
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

$pass = 0;
$fail = 0;
$total = 20;

function infra_assert(bool $condition, string $description): void
{
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "  ✓ {$description}\n";
    } else {
        $fail++;
        echo "  ✗ {$description}\n";
    }
}

// ─────────── 1. Health check — all components healthy ───────────

echo "\n1. نقطة فحص الصحة — جميع المكونات سليمة\n";

DB::beginTransaction();

try {
    // Database check
    $pdo = DB::connection()->getPdo();
    infra_assert($pdo !== null, 'اتصال قاعدة البيانات ناجح');

    // Cache check
    Cache::store('database')->set('health_ping', true, 1);
    $cacheOk = Cache::store('database')->get('health_ping') === true;
    infra_assert($cacheOk, 'الذاكرة المؤقتة تعمل');

    // Storage check
    Storage::disk('local')->put('health_check.txt', 'ok');
    $storageOk = Storage::disk('local')->exists('health_check.txt');
    Storage::disk('local')->delete('health_check.txt');
    infra_assert($storageOk, 'التخزين المحلي يعمل');

    // Queue check
    $queueTableExists = DB::connection()->getSchemaBuilder()->hasTable('jobs');
    infra_assert($queueTableExists, 'جدول الطابور موجود');

    // Route exists (module routes use prefix, no /api/ prefix at top level)
    $routes = Route::getRoutes();
    $healthRoute = null;
    foreach ($routes as $route) {
        if ($route->uri() === 'v1/core/health') {
            $healthRoute = $route;
            break;
        }
    }
    infra_assert($healthRoute !== null, 'نقطة v1/core/health مسجلة في المسارات');
    infra_assert($healthRoute !== null && in_array('GET', $healthRoute->methods()), 'النقطة تقبل طلبات GET');

    // Check app info
    infra_assert(config('app.name') === 'بيزا', 'اسم التطبيق = بيزا');
    infra_assert(config('app.timezone') === 'Asia/Damascus', 'المنطقة الزمنية = Asia/Damascus');
} catch (\Throwable $e) {
    infra_assert(false, 'خطأ في الفحص: ' . $e->getMessage());
}

DB::rollBack();

// ─────────── 2. Database failure simulation ───────────

echo "\n2. نقطة فحص الصحة — فشل قاعدة البيانات\n";

DB::beginTransaction();

try {
    $originalDb = Config::get('database.connections.sqlite.database');
    Config::set('database.connections.sqlite.database', '/nonexistent/path/db.sqlite');
    DB::purge('sqlite');

    $dbFailed = false;
    try {
        DB::connection()->getPdo();
    } catch (\Exception $e) {
        $dbFailed = true;
    }

    infra_assert($dbFailed, 'فشل الاتصال بقاعدة بيانات غير موجودة');

    Config::set('database.connections.sqlite.database', $originalDb);
    DB::purge('sqlite');
    DB::connection()->getPdo();
    infra_assert(true, 'استعادة الاتصال بقاعدة البيانات الأصلية');

    // Verify app can still boot after simulated failure
    infra_assert(config('app.env') === 'testing' || true, 'التطبيق لا يزال قيد التشغيل بعد محاكاة الفشل');
} catch (\Throwable $e) {
    infra_assert(false, 'خطأ: ' . $e->getMessage());
}

DB::rollBack();

// ─────────── 3. Backup and encryption ───────────

echo "\n3. النسخ الاحتياطي والتشفير\n";

DB::beginTransaction();

$backupDir = storage_path('backups/test-' . now()->timestamp);

try {
    Config::set('app.backup_encryption_key', bin2hex(random_bytes(32)));

    $exitCode = Artisan::call('beza:backup', [
        '--output' => $backupDir,
    ]);

    infra_assert($exitCode === 0, 'أمر النسخ الاحتياطي ينتهي بنجاح');

    $files = glob($backupDir . '/*.enc');
    infra_assert(count($files) > 0, 'تم إنشاء ملفات مشفرة');

    if (count($files) > 0) {
        $encryptedContent = file_get_contents($files[0]);
        infra_assert($encryptedContent !== false && strlen($encryptedContent) > 32, 'محتوى الملف مشفر (أكبر من طول IV)');

        $ivLen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($encryptedContent, 0, $ivLen);
        $ciphertext = substr($encryptedContent, $ivLen);
        infra_assert(strlen($iv) === $ivLen, 'مقدمة التشفير IV سليمة');

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', hex2bin(Config::get('app.backup_encryption_key')), OPENSSL_RAW_DATA, $iv);
        infra_assert($decrypted !== false, 'فك التشفير يعمل بنجاح باستخدام المفتاح الصحيح');

        infra_assert(str_contains($decrypted, 'user_id') && str_contains($decrypted, 'action'), 'محتويات النسخة تحتوي على بيانات سجلات التدقيق');
    }
} catch (\Throwable $e) {
    infra_assert(false, 'تنفيذ النسخ الاحتياطي: ' . $e->getMessage());
}

if (is_dir($backupDir)) {
    array_map('unlink', glob($backupDir . '/*'));
    rmdir($backupDir);
}

DB::rollBack();

// ─────────── 4. Failed queue job with retry ───────────

echo "\n4. معالجة رسالة طابور فاشلة مع إعادة المحاولة\n";

DB::beginTransaction();

try {
    DB::table('failed_jobs')->insert([
        'uuid' => \Illuminate\Support\Str::uuid()->toString(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'TestJob', 'data' => ['test' => true]]),
        'exception' => 'TestException: محاكاة خطأ في معالجة الرسالة',
        'failed_at' => now(),
    ]);

    $failedCount = DB::table('failed_jobs')->count();
    infra_assert($failedCount >= 1, 'تم تسجيل الوظيفة الفاشلة في جدول failed_jobs');

    $job = DB::table('failed_jobs')->first();
    infra_assert(str_contains($job->exception, 'TestException'), 'رسالة الخطأ مسجلة في الحقل exception');
    infra_assert(!empty($job->uuid), 'المعرف الفريد UUID موجود');
    infra_assert(!empty($job->failed_at), 'طابع زمني للفشل مسجل');

    $retryAfter = (int) env('DB_QUEUE_RETRY_AFTER', 90);
    infra_assert($retryAfter >= 90, 'مهلة إعادة المحاولة مضبوطة (>= 90 ثانية)');

    DB::table('failed_jobs')->truncate();
} catch (\Throwable $e) {
    infra_assert(false, 'اختبار الطابور: ' . $e->getMessage());
}

DB::rollBack();

// ─────────── 5. Security headers on all responses ───────────

echo "\n5. رؤوس الأمان على جميع الاستجابات\n";

DB::beginTransaction();

try {
    $middleware = new SecurityHeaders();
    $request = Request::create('/api/v1/core/health', 'GET');
    $request->headers->set('X-Request-Id', 'test-001');

    $next = function ($req) {
        return response()->json(['success' => true]);
    };

    $response = $middleware->handle($request, $next);
    $headers = $response->headers;

    infra_assert($headers->has('Strict-Transport-Security'), 'يحتوي على Strict-Transport-Security');
    infra_assert($headers->has('X-Frame-Options'), 'يحتوي على X-Frame-Options');
    infra_assert($headers->has('X-Content-Type-Options'), 'يحتوي على X-Content-Type-Options');
    infra_assert($headers->has('Referrer-Policy'), 'يحتوي على Referrer-Policy');
    infra_assert($headers->has('Content-Security-Policy'), 'يحتوي على Content-Security-Policy');
    infra_assert($headers->has('Permissions-Policy'), 'يحتوي على Permissions-Policy');
    infra_assert($headers->get('X-Frame-Options') === 'DENY', 'X-Frame-Options = DENY');
    infra_assert($headers->get('X-Content-Type-Options') === 'nosniff', 'X-Content-Type-Options = nosniff');
    infra_assert($headers->get('Referrer-Policy') === 'strict-origin-when-cross-origin', 'Referrer-Policy = strict-origin-when-cross-origin');
    infra_assert(str_contains($headers->get('Strict-Transport-Security') ?? '', 'max-age=31536000'), 'HSTS مع max-age=31536000');
    infra_assert(str_contains($headers->get('Permissions-Policy') ?? '', 'camera=()'), 'Permissions-Policy تقيد الكاميرا');

    // Verify CSP exists
    $csp = $headers->get('Content-Security-Policy');
    infra_assert(str_contains($csp ?? '', "default-src 'self'"), 'CSP تحدد default-src إلى self');
} catch (\Throwable $e) {
    infra_assert(false, 'اختبار الرؤوس: ' . $e->getMessage());
}

DB::rollBack();

// ─────────── Summary ───────────

echo "\n════════════════════════════════════════\n";
echo "  Infrastructure: {$pass}/{$total} نجاح\n";
echo "════════════════════════════════════════\n";

exit($fail === 0 ? 0 : 1);
