# 05 - الميغريشن (Migrations)

## cards table (مع حقول الإدارة)

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
            $table->decimal('hold_balance', 12, 2)->default(0.00);
            $table->decimal('frozen_balance', 12, 2)->default(0.00);
            $table->decimal('card_load', 12, 2)->default(0.00);
            $table->string('pin_hash', 255)->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('pin_changed_at')->nullable();
            $table->timestamp('limit_changed_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('reported_stolen_at')->nullable();
            $table->text('stolen_report_note')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->unique('pan_encrypted', 'cards_pan_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('cards'); }
};
```

## card_audit_logs table (لأغراض التدقيق)

```php
Schema::create('card_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('card_id')->constrained()->onDelete('cascade');
    $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('action', 100);
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index('card_id');
    $table->index('action');
    $table->index('created_at');
});
```

## ملاحظات الهجرة

| الحقل | الغرض |
|-------|-------|
| frozen_at | وقت تجميد البطاقة |
| pin_changed_at | آخر تغيير لـ PIN |
| limit_changed_at | آخر تحديث للحدود |
| reported_stolen_at | وقت الإبلاغ عن السرقة |
| hold_balance | المبلغ المعلق للمعاملات الجارية |
| frozen_balance | الرصيد المجمد |
