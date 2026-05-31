# 04 - جداول نظام كشف الاحتيال (Database Schema)

## flagged_transactions جدول

```php
Schema::create('flagged_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('transaction_id')->nullable()->constrained();
    $table->foreignId('user_id')->constrained();
    $table->decimal('amount', 15, 2);
    $table->string('currency', 3);
    $table->json('triggered_rules');    // ["rule_1", "rule_4"]
    $table->integer('risk_score');       // 0-100
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('notes')->nullable();
    $table->foreignId('reviewed_by')->nullable()->constrained('users');
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamps();
});
```

## blocked_ips جدول

```php
Schema::create('blocked_ips', function (Blueprint $table) {
    $table->id();
    $table->string('ip', 45)->unique();
    $table->string('reason');
    $table->boolean('is_active')->default(true);
    $table->foreignId('blocked_by')->constrained('users');
    $table->timestamps();
});
```

## device_fingerprints جدول

```php
Schema::create('device_fingerprints', function (Blueprint $table) {
    $table->id();
    $table->string('fingerprint', 255);
    $table->foreignId('user_id')->constrained();
    $table->string('device_name')->nullable();
    $table->string('os')->nullable();
    $table->string('browser')->nullable();
    $table->ipAddress('first_seen_ip');
    $table->timestamp('first_seen_at');
    $table->timestamp('last_seen_at');
    $table->boolean('is_trusted')->default(false);
    $table->unique(['fingerprint', 'user_id']);
    $table->timestamps();
});
```
