# 05 - الميغريشن (Migrations)

## لا يوجد ميغريشن خاص بـ OTP

OTP لا يحتاج جداول MySQL — يخزن في Redis Cache فقط.

## العمود المطلوب في users

عمود `phone_verified_at` موجود في جدول `users`:

```php
// موجود مسبقاً في create_users_table.php
$table->timestamp('phone_verified_at')->nullable();
```

## إضافة العمود إذا لم يكن موجوداً

```php
<?php
// database/migrations/2024_06_01_000001_add_phone_verified_at_to_users.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'phone_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('phone_verified_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_verified_at');
        });
    }
};
```

## تكوين Redis

```php
<?php
// config/database.php — قسم redis

'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
],
```

```php
// config/cache.php — استخدام Redis للتخزين المؤقت

'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
],
```
