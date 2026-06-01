# 07 - سكريبتات التحقق من الصحة (Verification Scripts)

## التحقق من صحة الإعداد (PHP)

```php
<?php
echo "=== Beza Platform Setup Verification ===\n\n";

echo "PHP Version: " . PHP_VERSION . " ";
echo version_compare(PHP_VERSION, '8.2.0', '>=') ? "OK" : "FAIL (needs 8.2+)";
echo "\n";

$required = ['pdo', 'pdo_mysql', 'mbstring', 'xml', 'curl', 'gd', 'bcmath', 'redis'];
foreach ($required as $ext) {
    echo "Extension $ext: " . (extension_loaded($ext) ? "OK" : "FAIL") . "\n";
}

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=beza", "root", "");
    echo "MySQL Connection: OK\n";
} catch (PDOException $e) {
    echo "MySQL Connection: FAIL " . $e->getMessage() . "\n";
}

try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    echo "Redis Connection: OK\n";
} catch (Exception $e) {
    echo "Redis Connection: FAIL " . $e->getMessage() . "\n";
}

echo "Composer: ";
echo file_exists(__DIR__ . '/../vendor/autoload.php') ? "OK" : "FAIL (run composer install)";
echo "\n";
echo ".env file: " . (file_exists(__DIR__ . '/../.env') ? "OK" : "FAIL") . "\n";
echo "=== Verification Complete ===";
```

## اختبار API Ping

```bash
curl http://localhost:8000/api/ping
# {"success":true,"message":"pong","data":{"timestamp":"2026-05-27T12:00:00+03:00"}}
```

## التحقق من جداول قاعدة البيانات

```bash
php artisan db:show
php artisan db:table --name=users
```
