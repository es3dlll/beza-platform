# 12 - تقارير التغطية (Coverage Reporting)

## تشغيل مع تغطية

```bash
# تغطية بسيطة
php artisan test --coverage

# تغطية مع HTML report
php artisan test --coverage --coverage-html=coverage

# تغطية مع XML (لـ CI)
php artisan test --coverage --coverage-clover=coverage.xml

# تغطية مع نص
php artisan test --coverage --min=90
```

## تكوين التغطية

```xml
<!-- phpunit.xml -->
<coverage>
    <include>
        <directory suffix=".php">app/Services</directory>
        <directory suffix=".php">app/Http/Controllers/Api</directory>
        <directory suffix=".php">app/Models</directory>
    </include>
    <exclude>
        <directory suffix=".php">app/Providers</directory>
        <directory suffix=".php">app/Exceptions</directory>
        <file>app/Http/Kernel.php</file>
    </exclude>
    <report>
        <html outputDirectory="coverage" lowUpperBound="50" highLowerBound="90"/>
        <clover outputFile="coverage.xml"/>
        <text outputFile="php://stdout" showUncoveredFiles="false"/>
    </report>
</coverage>
```

## الهدف: 90%+ للـ Services و Controllers

```bash
# تحقق من تحقيق الهدف
php artisan test --coverage --min=90

# إذا كانت التغطية أقل من 90% → يفشل الاختبار
# يستخدم في CI/CD لمنع النشر قبل تغطية كافية
```

## تحليل التغطية

```bash
# إنشاء تقرير HTML
php artisan test --coverage-html=coverage

# فتح في المتصفح
start coverage/index.html
```
