# 08 - كود المتحكم الكامل (Controller Full Code)

## InstallerController — كامل مع 6 خطوات

```php
<?php
// app/Http/Controllers/Install/InstallerController.php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Http\Requests\Install\AdminUserRequest;
use App\Http\Requests\Install\DatabaseRequest;
use App\Http\Requests\Install\EnvironmentRequest;
use App\Models\User;
use App\Services\Install\EnvironmentConfigurator;
use App\Services\Install\RequirementChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Events\InstallationCompleted;

class InstallerController extends Controller
{
    public function __construct(
        private readonly RequirementChecker $checker,
        private readonly EnvironmentConfigurator $configurator,
    ) {}

    /**
     * GET /install
     * شاشة الترحيب — تظهر فقط إذا كان المثبت غير مقفول
     */
    public function welcome(): JsonResponse
    {
        if (env('INSTALLER_LOCKED') === true) {
            return response()->json([
                'success' => false,
                'message' => 'تم إكمال التنصيب مسبقاً. المثبت معطل.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'مرحباً بك في مثبت Beza',
            'data'    => [
                'app_name'    => env('APP_NAME', 'Beza Platform'),
                'php_version' => PHP_VERSION,
                'steps'       => [
                    'requirements',
                    'database',
                    'environment',
                    'migration',
                    'admin',
                    'complete',
                ],
            ],
        ]);
    }

    /**
     * POST /install/requirements
     * الخطوة 1: فحص جميع متطلبات PHP والخادم
     */
    public function checkRequirements(): JsonResponse
    {
        if (env('INSTALLER_LOCKED') === true) {
            return response()->json(['success' => false, 'message' => 'المثبت معطل'], 403);
        }

        $requirements = $this->checker->checkAll();

        $allPass = collect($requirements)->every(fn($item) => $item['pass'] === true);

        return response()->json([
            'success' => true,
            'data'    => [
                'all_pass' => $allPass,
                'items'    => $requirements,
            ],
        ]);
    }

    /**
     * POST /install/database
     * الخطوة 2: اختبار اتصال MySQL وإنشاء قاعدة البيانات
     */
    public function setupDatabase(DatabaseRequest $request): JsonResponse
    {
        if (env('INSTALLER_LOCKED') === true) {
            return response()->json(['success' => false, 'message' => 'المثبت معطل'], 403);
        }

        $host     = $request->input('db_host');
        $port     = (int) $request->input('db_port', 3306);
        $database = $request->input('db_database');
        $username = $request->input('db_username');
        $password = $request->input('db_password', '');

        try {
            // اختبار الاتصال بدون قاعدة بيانات محددة أولاً
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_CONNECT_TIMEOUT    => 5,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            // إنشاء قاعدة البيانات إن لم توجد
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` 
                        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // اختبار الاتصال بقاعدة البيانات المحددة
            $dsnWithDb = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            new \PDO($dsnWithDb, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_CONNECT_TIMEOUT => 5,
            ]);

            // تخزين مؤقتاً في الجلسة لتستخدم في الخطوة التالية
            session()->put('install.db', [
                'host'     => $host,
                'port'     => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم الاتصال بقاعدة البيانات بنجاح',
            ]);

        } catch (\PDOException $e) {
            Log::error('فشل الاتصال بقاعدة البيانات أثناء التنصيب', [
                'host'    => $host,
                'db_name' => $database,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل الاتصال بقاعدة البيانات',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /install/env
     * الخطوة 3: كتابة ملف .env وتوليد المفاتيح
     */
    public function configureEnv(EnvironmentRequest $request): JsonResponse
    {
        if (env('INSTALLER_LOCKED') === true) {
            return response()->json(['success' => false, 'message' => 'المثبت معطل'], 403);
        }

        $dbConfig = session('install.db');

        if (!$dbConfig) {
            return response()->json([
                'success' => false,
                'message' => 'الرجاء إكمال خطوة إعداد قاعدة البيانات أولاً',
            ], 422);
        }

        try {
            // تجميع جميع الإعدادات
            $envData = array_merge($dbConfig, [
                'app_name'        => $request->input('app_name'),
                'app_url'         => $request->input('app_url'),
                'app_env'         => $request->input('app_env'),
                'redis_host'      => $request->input('redis_host'),
                'redis_port'      => $request->input('redis_port'),
                'redis_password'  => $request->input('redis_password', ''),
                'mail_host'       => $request->input('mail_host', ''),
                'mail_port'       => $request->input('mail_port', 587),
                'mail_username'   => $request->input('mail_username', ''),
                'mail_password'   => $request->input('mail_password', ''),
                'mail_encryption' => $request->input('mail_encryption', 'tls'),
                'queue_connection' => $request->input('queue_connection', 'redis'),
            ]);

            // كتابة ملف .env
            $this->configurator->writeEnv($envData);

            // توليد APP_KEY
            Artisan::call('key:generate', ['--force' => true]);
            $keyOutput = Artisan::output();

            // توليد JWT_SECRET
            Artisan::call('jwt:secret', ['--force' => true]);
            $jwtOutput = Artisan::output();

            Log::info('تم إعداد ملف .env بنجاح أثناء التنصيب');

            return response()->json([
                'success' => true,
                'message' => 'تم إعداد البيئة بنجاح',
                'data'    => [
                    'app_key_generated' => str_contains($keyOutput, 'base64'),
                    'jwt_secret_generated' => str_contains($jwtOutput, 'base64'),
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('فشل إعداد البيئة أثناء التنصيب', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل إعداد البيئة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /install/migrate
     * الخطوة 4: تشغيل الترحيلات والبذور
     */
    public function runMigrations(): JsonResponse
    {
        if (env('INSTALLER_LOCKED') === true) {
            return response()->json(['success' => false, 'message' => 'المثبت معطل'], 403);
        }

        try {
            // تشغيل الترحيلات
            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = Artisan::output();

            // تشغيل البذور
            Artisan::call('db:seed', ['--force' => true]);
            $seedOutput = Artisan::output();

            Log::info('تم تشغيل الترحيلات والبذور بنجاح أثناء التنصيب');

            return response()->json([
                'success' => true,
                'message' => 'تم تشغيل الترحيلات والبذور بنجاح',
                'data'    => [
                    'migration_output' => $migrateOutput,
                    'seed_output'      => $seedOutput,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('فشل تشغيل الترحيلات أثناء التنصيب', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل تشغيل الترحيلات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /install/admin
     * الخطوة 5: إنشاء المشرف الأول
     */
    public function createAdmin(AdminUserRequest $request): JsonResponse
    {
        if (env('INSTALLER_LOCKED') === true) {
            return response()->json(['success' => false, 'message' => 'المثبت معطل'], 403);
        }

        try {
            $user = User::create([
                'name'              => $request->input('name'),
                'email'             => $request->input('email'),
                'phone'             => $request->input('phone'),
                'password'          => Hash::make($request->input('password')),
                'is_admin'          => true,
                'email_verified_at' => now(),
            ]);

            // تخزين بيانات المشرف لعرضها في الملخص
            session()->put('install.admin', [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

            Log::info('تم إنشاء المشرف الأول أثناء التنصيب', [
                'admin_id' => $user->id,
                'email'    => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المشرف الأول بنجاح',
                'data'    => [
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('فشل إنشاء المشرف أثناء التنصيب', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل إنشاء المشرف: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /install/complete
     * الخطوة 6: تعطيل المثبت وعرض الملخص
     */
    public function complete(): JsonResponse
    {
        if (env('INSTALLER_LOCKED') === true) {
            return response()->json(['success' => false, 'message' => 'المثبت معطل'], 403);
        }

        $adminData = session('install.admin');

        if (!$adminData) {
            return response()->json([
                'success' => false,
                'message' => 'الرجاء إنشاء المشرف أولاً',
            ], 422);
        }

        try {
            // تعطيل المثبت — كتابة INSTALLER_LOCKED=true في .env
            $this->configurator->lockInstaller();

            // إطلاق حدث إكمال التنصيب
            InstallationCompleted::dispatch($adminData);

            // مسح بيانات الجلسة
            session()->forget(['install.db', 'install.admin']);

            Log::info('تم إكمال تنصيب Beza بنجاح');

            return response()->json([
                'success' => true,
                'message' => 'تم إكمال تنصيب Beza بنجاح',
                'data'    => [
                    'summary' => [
                        'app_name'    => env('APP_NAME'),
                        'app_url'     => env('APP_URL'),
                        'admin_name'  => $adminData['name'],
                        'admin_email' => $adminData['email'],
                        'admin_phone' => $adminData['phone'],
                        'db_host'     => env('DB_HOST'),
                        'db_name'     => env('DB_DATABASE'),
                        'php_version' => PHP_VERSION,
                        'completed_at' => now()->toIso8601String(),
                    ],
                    'warning' => 'تم تعطيل المثبت. احتفظ ببيانات الدخول في مكان آمن.',
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('فشل تعطيل المثبت أثناء الإكمال', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل إكمال التنصيب: ' . $e->getMessage(),
            ], 500);
        }
    }
}
```

## المسارات (Routes)

```php
<?php
// routes/web.php — ملاحظة: المثبت يستخدم web.php وليس api.php

use App\Http\Controllers\Install\InstallerController;

Route::prefix('install')->group(function () {
    Route::get('/', [InstallerController::class, 'welcome']);
    Route::post('/requirements', [InstallerController::class, 'checkRequirements']);
    Route::post('/database', [InstallerController::class, 'setupDatabase']);
    Route::post('/env', [InstallerController::class, 'configureEnv']);
    Route::post('/migrate', [InstallerController::class, 'runMigrations']);
    Route::post('/admin', [InstallerController::class, 'createAdmin']);
    Route::post('/complete', [InstallerController::class, 'complete']);
});
```
