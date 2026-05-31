# 05 - كود الميغريشن الكامل — المصادقة الثنائية (2FA)

## إضافة أعمدة 2FA إلى جدول users

```php
<?php
// database/migrations/2024_06_01_000002_add_two_factor_columns_to_users.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')
                ->nullable()
                ->after('fcm_token')
                ->comment('مفتاح TOTP السري — مشفر');
            
            $table->text('two_factor_recovery_codes')
                ->nullable()
                ->after('two_factor_secret')
                ->comment('رموز الاسترداد (JSON)');
            
            $table->boolean('two_factor_confirmed')
                ->default(false)
                ->after('two_factor_recovery_codes')
                ->comment('هل تم تأكيد تفعيل 2FA');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed',
            ]);
        });
    }
};
```

## تثبيت مكتبة Google2FA

```bash
composer require pragmarx/google2fa-laravel

# لنشر الإعدادات
php artisan vendor:publish --provider="PragmaRX\Google2FALaravel\ServiceProvider"
```

## ملف الإعدادات

```php
<?php
// config/google2fa.php

return [
    'enabled' => env('GOOGLE2FA_ENABLED', true),
    'secret_key_length' => 32,
    'otp_window' => 1, // ±1 فترة (30 ثانية) لتسامح فرق التوقيت
    'site_name' => env('APP_NAME', 'Beza'),
    'throw_exception' => env('GOOGLE2FA_THROW_EXCEPTION', true),
];
```
