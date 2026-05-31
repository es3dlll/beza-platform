# 05 - الميغريشن

```php
Schema::create('merchant_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained('users');
    $table->decimal('amount', 15, 2);
    $table->enum('currency', ['SYP', 'USD']);
    $table->enum('interval', ['monthly', 'yearly']);
    $table->enum('status', ['pending','active','paused','cancelled','completed'])->default('pending');
    $table->unsignedSmallInteger('max_cycles')->default(12);
    $table->unsignedSmallInteger('current_cycle')->default(0);
    $table->timestamp('next_charge_at')->nullable();
    $table->timestamp('customer_consented_at')->nullable();
    $table->timestamps();
});

Schema::create('subscription_charges', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subscription_id')->constrained('merchant_subscriptions')->onDelete('cascade');
    $table->unsignedSmallInteger('cycle_number');
    $table->decimal('amount', 15, 2);
    $table->enum('status', ['pending','completed','failed'])->default('pending');
    $table->timestamp('charged_at')->nullable();
    $table->timestamps();
});
```
