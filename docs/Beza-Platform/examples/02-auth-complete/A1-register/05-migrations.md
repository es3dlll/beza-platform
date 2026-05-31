# 05 - الميغريشن (Migrations)

## JWT — المصادقة الإحصائية (Stateless Authentication)

تم اعتماد **JWT (JSON Web Tokens)** باستخدام الحزمة `tymon/jwt-auth` للمصادقة العديمة الحالة:

```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

## إعدادات JWT (config/jwt.php)

```php
// config/jwt.php
return [
    'secret' => env('JWT_SECRET'),
    'ttl' => env('JWT_TTL', 60),          // 60 دقيقة صلاحية التوكن
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 14 يوم صلاحية التجديد
    'algo' => env('JWT_ALGO', 'HS256'),
    'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
    'providers' => [
        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,
        'user' => Tymon\JWTAuth\Providers\User\EloquentUserAdapter::class,
    ],
];
```

## جدول المستخدمين (users)

```php
<?php
// database/migrations/2024_01_01_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->string('phone', 20)->unique();
            $table->string('password');
            $table->string('pin_code');
            $table->enum('status', ['pending', 'active', 'suspended', 'blocked'])->default('pending');
            $table->enum('kyc_status', ['not_submitted', 'pending', 'verified', 'rejected'])->default('not_submitted');
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('fcm_token')->nullable();
            $table->string('avatar')->nullable();
            $table->string('device_id')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->boolean('two_factor_confirmed')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_merchant')->default(false);
            $table->boolean('is_agent')->default(false);
            $table->json('preferences')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phone', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

## جدول المحافظ (wallets)

```php
<?php
// database/migrations/2024_01_01_000002_create_wallets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('currency', ['SYP', 'USD']);
            $table->string('wallet_number', 20)->unique();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('frozen_balance', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
            $table->index('wallet_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
```

## ملاحظات الميغريشن

| الجدول | التفاصيل |
|--------|----------|
| users | `uuid` يتم توليده تلقائياً في الكود (`Str::uuid()`) |
| users | `pin_code` يجب أن يكون 4 أرقام — يتم Hash باستخدام Bcrypt |
| wallets | يتم إنشاؤها تلقائياً عند تسجيل المستخدم |
| wallets | محفظة USD تبدأ بـ 5.00 (هدية ترحيب)، SYP تبدأ بـ 0.00 |
| engine | InnoDB لضمان ACID والعلاقات |
