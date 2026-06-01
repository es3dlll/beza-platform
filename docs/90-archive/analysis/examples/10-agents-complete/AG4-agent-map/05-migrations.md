# 05 - الترحيلات (Migrations)

## الفهرس المكاني (Spatial Index)

```php
<?php
// database/migrations/2024_06_01_000001_add_location_columns_to_agents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // أعمدة الإحداثيات
            $table->decimal('latitude', 10, 8)
                ->nullable()
                ->after('phone')
                ->comment('خط العرض');

            $table->decimal('longitude', 11, 8)
                ->nullable()
                ->after('latitude')
                ->comment('خط الطول');

            // آخر تحديث للموقع
            $table->timestamp('location_updated_at')
                ->nullable()
                ->after('longitude');

            // حالة الاتصال
            $table->boolean('is_online')
                ->default(false)
                ->after('location_updated_at');

            // آخر ظهور
            $table->timestamp('last_seen_at')
                ->nullable()
                ->after('is_online');
        });

        // إضافة عمود POINT للإحداثيات
        DB::statement('ALTER TABLE agents ADD COLUMN location_point POINT SRID 4326 AFTER longitude');

        // إنشاء فهرس مكاني (Spatial Index)
        DB::statement('CREATE SPATIAL INDEX agents_location_spatial_index ON agents(location_point)');

        // تحديث النقاط المكانية بناءً على البيانات الحالية
        DB::statement('
            UPDATE agents
            SET location_point = ST_SRID(POINT(longitude, latitude), 4326)
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'location_updated_at',
                'is_online',
                'last_seen_at',
            ]);
        });

        DB::statement('ALTER TABLE agents DROP COLUMN location_point');
    }
};
```

## جدول agent_locations (سجل المواقع)

```php
<?php
// database/migrations/2024_06_01_000002_create_agent_locations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            // نقطة مكانية للاستعلام
            $table->point('location')
                ->nullable()
                ->comment('نقطة مكانية SRID 4326');

            // دقة الموقع بالمتر
            $table->float('accuracy')->nullable();

            // عنوان تقريبي
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('SY');

            // مصدر الموقع (GPS, IP, Manual)
            $table->string('source')->default('gps');

            $table->timestamps();

            // الفهارس
            $table->index('agent_id');
            $table->index('created_at');
        });

        // إضافة فهرس مكاني
        DB::statement('ALTER TABLE agent_locations ADD SPATIAL INDEX agent_locations_spatial_index(location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_locations');
    }
};
```

## جدول agent_availability (جدول التوفر)

```php
<?php
// database/migrations/2024_06_01_000003_create_agent_availability_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_availability', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('is_available')
                ->default(true);

            $table->string('status')
                ->default('online')
                ->comment('online, offline, busy, away');

            // آخر مرة كان فيها متاحاً
            $table->timestamp('available_since')
                ->nullable();

            // نطاق الخدمات التي يقدمها
            $table->json('service_types')
                ->nullable()
                ->comment('مصفوفة من أنواع الخدمات');

            $table->timestamps();

            $table->index('agent_id');
            $table->index('is_available');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_availability');
    }
};
```

## مشغل (Trigger) لتحديث النقطة المكانية

```sql
-- database/migrations/2024_06_01_000004_create_location_trigger.php

-- مشغل لتحديث location_point تلقائياً عند تغيير الإحداثيات
DELIMITER //
CREATE TRIGGER before_agents_location_update
BEFORE UPDATE ON agents
FOR EACH ROW
BEGIN
    IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL THEN
        SET NEW.location_point = ST_SRID(POINT(NEW.longitude, NEW.latitude), 4326);
    END IF;
END;//
DELIMITER ;
```

## ملخص الترحيلات

| الترحيل | الجدول/الغرض | InnoDB | الفهارس |
|---------|--------------|--------|---------|
| 000001 | إضافة أعمدة location إلى agents | نعم | SPATIAL INDEX |
| 000002 | agent_locations (سجل المواقع) | نعم | SPATIAL + agent_id |
| 000003 | agent_availability (حالة التوفر) | نعم | agent_id, is_available |

## تشغيل الترحيلات

```bash
php artisan migrate --path=database/migrations/2024_06_01_000001_add_location_columns_to_agents_table.php
php artisan migrate --path=database/migrations/2024_06_01_000002_create_agent_locations_table.php
php artisan migrate --path=database/migrations/2024_06_01_000003_create_agent_availability_table.php
```

## الاستعلام المكاني (Spatial Query Example)

```sql
-- البحث عن الوكلاء ضمن دائرة نصف قطرها 10 كم
SELECT
    a.id,
    a.name,
    a.latitude,
    a.longitude,
    ROUND(ST_Distance_Sphere(
        POINT(36.2765, 33.5138), -- موقع المستخدم (دمشق)
        a.location_point
    )) AS distance_meters
FROM agents a
WHERE a.is_online = 1
    AND ST_Distance_Sphere(
        POINT(36.2765, 33.5138),
        a.location_point
    ) <= 10000
ORDER BY distance_meters ASC
LIMIT 20;
```
