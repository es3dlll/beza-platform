# 05 - كود الميغريشن الكامل (Migrations)

## جدول users

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('name');
    $table->string('email')->nullable()->unique();
    $table->string('phone')->unique();
    $table->string('password');
    $table->string('pin_code')->nullable();
    $table->string('avatar')->nullable();
    $table->enum('status', ['pending','active','suspended','blocked'])->default('pending');
    $table->enum('kyc_status', ['not_submitted','pending','verified','rejected'])->default('not_submitted');
    $table->boolean('is_admin')->default(false);
    $table->boolean('is_merchant')->default(false);
    $table->boolean('is_agent')->default(false);
    $table->string('device_id')->nullable();
    $table->string('fcm_token')->nullable();
    $table->string('last_login_ip', 45)->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->json('preferences')->nullable();
    $table->timestamp('phone_verified_at')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->text('two_factor_secret')->nullable();
    $table->text('two_factor_recovery_codes')->nullable();
    $table->softDeletes();
    $table->timestamps();
});
```

## جدول wallets

```php
Schema::create('wallets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('currency', ['SYP', 'USD']);
    $table->string('wallet_number', 20)->unique();
    $table->decimal('balance', 15, 2)->default(0);
    $table->decimal('frozen_balance', 15, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->unique(['user_id', 'currency']);
    $table->timestamps();
});
```

## جدول transactions

```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
    $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
    $table->decimal('amount', 15, 2);
    $table->decimal('amount_in_usd', 15, 2);
    $table->enum('type', ['deposit','withdraw','transfer','exchange','merchant_payment','agent_cash_in','agent_cash_out','investment','investment_profit','card_load','card_payment','fee']);
    $table->enum('status', ['pending','processing','completed','failed','cancelled','refunded'])->default('pending');
    $table->string('reference_number', 50)->unique();
    $table->text('description')->nullable();
    $table->decimal('fee', 15, 2)->default(0);
    $table->json('metadata')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```
