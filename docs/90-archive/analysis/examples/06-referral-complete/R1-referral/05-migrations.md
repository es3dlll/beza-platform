# 05 - كود الميغريشن الكامل (Migrations)

## جدول أكواد الإحالة (referral_codes)

```php
<?php
// database/migrations/2024_01_01_000020_create_referral_codes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            $table->index('code');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_codes');
    }
};
```

## جدول مكافآت الإحالة (referral_rewards)

```php
<?php
// database/migrations/2024_01_01_000021_create_referral_rewards_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users');
            $table->foreignId('referred_id')->constrained('users');
            $table->foreignId('referral_code_id')->constrained('referral_codes');
            $table->enum('reward_type', ['signup', 'transaction'])->default('signup');
            $table->decimal('referrer_amount', 15, 2)->default(2.00);
            $table->decimal('referred_amount', 15, 2)->default(2.00);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->foreignId('trigger_transaction_id')->nullable()->constrained('transactions');
            $table->timestamps();

            $table->index('referrer_id');
            $table->index('referred_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};
```

## إضافة عمود referred_by للمستخدمين

```php
<?php
// database/migrations/2024_01_01_000022_add_referred_by_to_users.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('referred_by')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn('referred_by');
        });
    }
};
```
