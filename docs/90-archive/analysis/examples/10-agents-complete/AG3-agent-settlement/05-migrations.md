# 05 - الترحيلات (Migrations)

## جدول agent_settlements

```php
<?php
// database/migrations/2024_06_01_000001_create_agent_settlements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_settlements', function (Blueprint $table) {
            $table->id();

            // معرف الوكيل
            $table->foreignId('agent_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // مبلغ التسوية
            $table->decimal('amount', 15, 2);

            // العملة (SYP أو USD)
            $table->string('currency', 3)->default('SYP');

            // رسوم التسوية
            $table->decimal('fee', 15, 2)->default(0);

            // معلومات الحساب المصرفي
            $table->string('bank_account')->nullable();

            // الحالة
            $table->string('status')
                ->default('pending')
                ->comment('pending, processing, completed, failed');

            // تاريخ الطلب
            $table->timestamp('requested_at')->nullable();

            // تاريخ المعالجة
            $table->timestamp('processed_at')->nullable();

            // المشرف الذي قام بالمعالجة
            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ملاحظات
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // الفهارس
            $table->index('agent_id');
            $table->index('status');
            $table->index('currency');
            $table->index('requested_at');
            $table->index(['agent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_settlements');
    }
};
```

## جدول settlement_transactions

سجل المعاملات المصرفية لكل تسوية.

```php
<?php
// database/migrations/2024_06_01_000002_create_settlement_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')
                ->constrained('agent_settlements')
                ->cascadeOnDelete();
            $table->string('transaction_ref')->unique();
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('recipient_name');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('SYP');
            $table->string('status')->default('initiated');
            $table->text('response_data')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('settlement_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_transactions');
    }
};
```

## جدول settlement_audit_log

سجل تدقيق لجميع عمليات التسوية.

```php
<?php
// database/migrations/2024_06_01_000003_create_settlement_audit_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')
                ->constrained('agent_settlements')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action');
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('settlement_id');
            $table->index('user_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_audit_logs');
    }
};
```

## ملخص الترحيلات

| الجدول | الغرض | InnoDB | الفهارس |
|--------|-------|--------|---------|
| agent_settlements | طلبات التسوية الرئيسية | نعم | agent_id, status, currency |
| settlement_transactions | سجل التحويلات المصرفية | نعم | settlement_id, status |
| settlement_audit_logs | سجل التدقيق | نعم | settlement_id, user_id |

## تشغيل الترحيلات

```bash
php artisan migrate --path=database/migrations/2024_06_01_000001_create_agent_settlements_table.php
php artisan migrate --path=database/migrations/2024_06_01_000002_create_settlement_transactions_table.php
php artisan migrate --path=database/migrations/2024_06_01_000003_create_settlement_audit_logs_table.php
```

## التراجع

```bash
php artisan migrate:rollback --path=database/migrations/2024_06_01_000003_create_settlement_audit_logs_table.php
php artisan migrate:rollback --path=database/migrations/2024_06_01_000002_create_settlement_transactions_table.php
php artisan migrate:rollback --path=database/migrations/2024_06_01_000001_create_agent_settlements_table.php
```
