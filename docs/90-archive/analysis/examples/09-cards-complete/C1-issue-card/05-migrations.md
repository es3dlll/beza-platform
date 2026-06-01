# 05 - الميغريشن (Migrations)

## cards table

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('card_type', ['virtual', 'physical'])->default('virtual');
            $table->string('currency', 3)->default('SYP');
            $table->string('pan_encrypted', 255);
            $table->string('pan_masked', 20);
            $table->date('expiry_date');
            $table->string('cvv_hash', 255);
            $table->string('status', 30)->default('issued');
            $table->decimal('daily_limit', 12, 2)->default(500000.00);
            $table->decimal('daily_used', 12, 2)->default(0.00);
            $table->decimal('monthly_limit', 12, 2)->default(5000000.00);
            $table->decimal('monthly_used', 12, 2)->default(0.00);
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->decimal('card_load', 12, 2)->default(0.00);
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('pin_changed_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('card_type');
            $table->unique('pan_encrypted', 'cards_pan_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('cards'); }
};
```

## card_transactions table

```php
Schema::create('card_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('card_id')->constrained()->onDelete('cascade');
    $table->enum('type', ['load', 'refund', 'payment', 'fee', 'hold', 'release']);
    $table->decimal('amount', 12, 2);
    $table->string('currency', 3)->default('SYP');
    $table->string('reference', 100)->unique();
    $table->text('description')->nullable();
    $table->string('status', 30)->default('pending');
    $table->timestamps();

    $table->index('card_id');
    $table->index('reference');
    $table->index('created_at');
});
```

## card_audit_logs table

```php
Schema::create('card_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('card_id')->constrained()->onDelete('cascade');
    $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('action', 100);
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index('card_id');
    $table->index('action');
});
```
