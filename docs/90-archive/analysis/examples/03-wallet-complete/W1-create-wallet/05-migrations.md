# 05 - كود الميغريشن الكامل (Migrations)

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
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('pin_code')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'blocked'])->default('pending');
            $table->enum('kyc_status', ['not_submitted', 'pending', 'verified', 'rejected'])->default('not_submitted');
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_merchant')->default(false);
            $table->boolean('is_agent')->default(false);
            $table->json('preferences')->nullable();
            $table->string('device_id')->nullable();
            $table->string('fcm_token')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phone', 'status']);
            $table->index('email');
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

## جدول المعاملات (transactions)

```php
<?php
// database/migrations/2024_01_01_000003_create_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_in_usd', 15, 2);
            $table->enum('type', [
                'deposit', 'withdraw', 'transfer', 'exchange',
                'merchant_payment', 'agent_cash_in', 'agent_cash_out',
                'investment', 'investment_profit', 'card_load',
                'card_payment', 'fee'
            ]);
            $table->enum('status', [
                'pending', 'processing', 'completed',
                'failed', 'cancelled', 'refunded'
            ])->default('pending');
            $table->string('reference_number', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('fee', 15, 2)->default(0.00);
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['from_wallet_id', 'status']);
            $table->index(['to_wallet_id', 'status']);
            $table->index(['type', 'created_at']);
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
```

## ملاحظات الميغريشن

| الجدول | التفاصيل |
|--------|----------|
| users | ينشأ عند التسجيل — يطلق حدث `created` |
| wallets | ينشأ تلقائياً مع المستخدم — محفظة SYP + USD لكل مستخدم |
| transactions | يسجل إيداع هدية 5 USD — reference_number يُنشأ عشوائياً |

## Seeders (للتطوير)

```php
<?php
// database/seeders/WalletSeeder.php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Services\CreateWalletService;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public function run(CreateWalletService $createWalletService): void
    {
        // إنشاء محافظ لجميع المستخدمين الموجودين
        User::whereDoesntHave('wallets')->each(function (User $user) use ($createWalletService) {
            $createWalletService->createWallets($user);
        });
    }
}
```
