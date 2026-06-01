# 03 - تدفق تشغيل الاختبارات (Test Workflow)

## تشغيل الاختبارات

```bash
# تشغيل جميع الاختبارات
php artisan test

# تشغيل مع تفاصيل
php artisan test --verbose

# تشغيل ملف معين
php artisan test --filter=TransferTest

# تشغيل مجموعة
php artisan test --testsuite=Feature

# تشغيل مع توازي
php artisan test --parallel

# تشغيل مع تغطية
php artisan test --coverage
```

## إعداد phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">app</directory>
        </include>
        <report>
            <html outputDirectory="coverage"/>
            <clover outputFile="coverage.xml"/>
        </report>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_DATABASE" value="beza_testing"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
    </php>
</phpunit>
```

## إعداد قاعدة بيانات الاختبار

```bash
# إنشاء قاعدة بيانات للاختبار
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS beza_testing"

# تشغيل الترحيلات
php artisan migrate --env=testing
```
