# 10 - سيرفس لير — EnvironmentConfigurator (إدارة ملف .env)

```php
<?php
// app/Services/Install/EnvironmentConfigurator.php

namespace App\Services\Install;

use Illuminate\Support\Facades\Log;

class EnvironmentConfigurator
{
    /**
     * المسار إلى ملف .env
     */
    private string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    /**
     * قراءة ملف .env الحالي كـ array
     *
     * @return array<string, string>
     */
    public function readEnv(): array
    {
        if (!file_exists($this->envPath)) {
            return [];
        }

        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env   = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // تخطي التعليقات
            if (str_starts_with($line, '#')) {
                continue;
            }

            // تخطي الأسطر الفارغة
            if (empty($line)) {
                continue;
            }

            // تحليل السطر: KEY=VALUE
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // إزالة علامات التنصيص إن وجدت
                if (strlen($value) >= 2) {
                    if (($value[0] === '"' && $value[-1] === '"') ||
                        ($value[0] === "'" && $value[-1] === "'")) {
                        $value = substr($value, 1, -1);
                    }
                }

                $env[$key] = $value;
            }
        }

        return $env;
    }

    /**
     * كتابة ملف .env بقيم جديدة
     *
     * @param array<string, mixed> $data
     */
    public function writeEnv(array $data): void
    {
        $existing = $this->readEnv();
        $merged   = array_merge($existing, $this->mapToEnvKeys($data));
        $merged['INSTALLER_VERSION'] = '1.0.0';

        $content = '';
        foreach ($merged as $key => $value) {
            // إضافة تعليق توضيحي للمفاتيح الجديدة
            if (!isset($existing[$key])) {
                $content .= "# تمت إضافته بواسطة مثبت Beza\n";
            }

            $content .= "{$key}={$this->escapeValue($value)}\n";
        }

        // إضافة سطر جديد في النهاية
        $content .= "\n";

        $bytesWritten = file_put_contents($this->envPath, $content, LOCK_EX);

        if ($bytesWritten === false) {
            throw new \RuntimeException('فشل كتابة ملف .env — تأكد من صلاحيات الكتابة');
        }

        // إعادة تحميل الإعدادات في الذاكرة
        if (function_exists('apache_setenv')) {
            foreach ($merged as $key => $value) {
                apache_setenv($key, $value);
            }
        }

        Log::info('تم تحديث ملف .env', [
            'keys' => array_keys($data),
        ]);
    }

    /**
     * تعطيل المثبت بعد إكمال التنصيب
     */
    public function lockInstaller(): void
    {
        $existing = $this->readEnv();
        $existing['INSTALLER_LOCKED'] = 'true';
        $existing['INSTALLER_COMPLETED_AT'] = now()->toIso8601String();

        $content = '';
        foreach ($existing as $key => $value) {
            $content .= "{$key}={$this->escapeValue($value)}\n";
        }

        file_put_contents($this->envPath, $content, LOCK_EX);

        // تحديث القيمة في الذاكرة الحالية
        $_ENV['INSTALLER_LOCKED'] = 'true';

        Log::info('تم تعطيل المثبت بعد إكمال التنصيب');
    }

    /**
     * التحقق مما إذا كان المثبت مقفولاً
     */
    public function isLocked(): bool
    {
        return env('INSTALLER_LOCKED') === true ||
               env('INSTALLER_LOCKED') === 'true';
    }

    /**
     * تحويل أسماء حقول النموذج إلى مفاتيح .env
     */
    private function mapToEnvKeys(array $data): array
    {
        $mapping = [
            'db_host'          => 'DB_HOST',
            'db_port'          => 'DB_PORT',
            'db_database'      => 'DB_DATABASE',
            'db_username'      => 'DB_USERNAME',
            'db_password'      => 'DB_PASSWORD',
            'app_name'         => 'APP_NAME',
            'app_url'          => 'APP_URL',
            'app_env'          => 'APP_ENV',
            'redis_host'       => 'REDIS_HOST',
            'redis_port'       => 'REDIS_PORT',
            'redis_password'   => 'REDIS_PASSWORD',
            'mail_host'        => 'MAIL_HOST',
            'mail_port'        => 'MAIL_PORT',
            'mail_username'    => 'MAIL_USERNAME',
            'mail_password'    => 'MAIL_PASSWORD',
            'mail_encryption'  => 'MAIL_ENCRYPTION',
            'queue_connection' => 'QUEUE_CONNECTION',
        ];

        $env = [];
        foreach ($data as $key => $value) {
            if (isset($mapping[$key])) {
                $env[$mapping[$key]] = $value;
            }
        }

        return $env;
    }

    /**
     * Escape قيمة لتكون آمنة في ملف .env
     */
    private function escapeValue(string $value): string
    {
        // إذا كانت القيمة تحتوي على مسافات أو رموز خاصة، ضعها في تنصيص
        if (str_contains($value, ' ') ||
            str_contains($value, '#') ||
            str_contains($value, '\'') ||
            str_contains($value, '"') ||
            $value === '') {
            // استبدال التنصيص المزدوج بعلامات الهروب
            $value = str_replace('"', '\"', $value);
            return '"' . $value . '"';
        }

        return $value;
    }

    /**
     * الحصول على قيمة معينة من .env الحالي
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return env($key, $default);
    }
}
```

## مثال: محتوى .env بعد التنصيب

```dotenv
APP_NAME="Beza Platform"
APP_ENV=production
APP_URL=https://beza.app
APP_DEBUG=false
APP_KEY=base64:AbCdEf123456789...
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beza_prod
DB_USERNAME=beza_user
DB_PASSWORD=********
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
QUEUE_CONNECTION=redis
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@beza.app
MAIL_PASSWORD=********
MAIL_ENCRYPTION=tls
JWT_SECRET=base64:XyZ789AbC...
JWT_TTL=60
INSTALLER_LOCKED=true
INSTALLER_COMPLETED_AT=2026-05-27T14:30:00+03:00
INSTALLER_VERSION=1.0.0
```
