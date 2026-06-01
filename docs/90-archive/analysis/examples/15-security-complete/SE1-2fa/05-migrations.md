# 05 - ميغريشن 2FA (Migrations)

## إضافة حقول 2FA لجدول users

```php
<?php
// database/migrations/2024_01_01_000010_add_two_factor_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('pin_code');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
```

## Accessors في User Model

```php
public function getTwoFactorSecretAttribute($value): ?string
{
    return $value ? decrypt($value) : null;
}

public function setTwoFactorSecretAttribute($value): void
{
    $this->attributes['two_factor_secret'] = $value ? encrypt($value) : null;
}

public function getTwoFactorRecoveryCodesAttribute($value): ?array
{
    return $value ? json_decode(decrypt($value), true) : null;
}

public function setTwoFactorRecoveryCodesAttribute($value): void
{
    $this->attributes['two_factor_recovery_codes'] = $value
        ? encrypt(json_encode($value))
        : null;
}
```
