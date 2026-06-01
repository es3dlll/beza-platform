# 05 - الهجرات (Migrations) - معاملات الوكيل

## هجرة إنشاء جدول agent_transactions

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete()
                ->comment('الوكيل المرتبط بالمعاملة');

            $table->string('type', 20)
                ->comment('نوع المعاملة: cash_in, cash_out');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('المستخدم الطرف الآخر في المعاملة');

            $table->decimal('amount', 15, 2)
                ->comment('المبلغ الإجمالي للمعاملة');

            $table->string('currency', 3)
                ->default('SAR')
                ->comment('عملة المعاملة');

            $table->decimal('commission', 15, 3)
                ->default(0.000)
                ->comment('العمولة المحصلة من هذه المعاملة');

            $table->decimal('net_amount', 15, 2)
                ->comment('المبلغ الصافي بعد خصم العمولة');

            $table->decimal('balance_before', 15, 2)
                ->comment('رصيد المحفظة قبل المعاملة');

            $table->decimal('balance_after', 15, 2)
                ->comment('رصيد المحفظة بعد المعاملة');

            $table->string('status', 30)
                ->default('completed')
                ->comment('حالة المعاملة: pending, completed, failed, reversed');

            $table->text('description')
                ->nullable()
                ->comment('وصف المعاملة');

            $table->json('metadata')
                ->nullable()
                ->comment('بيانات إضافية للمعاملة (تنسيق JSON)');

            $table->string('reference_number', 50)
                ->nullable()
                ->unique()
                ->comment('رقم مرجعي للمعاملة');

            $table->ipAddress('source_ip')
                ->nullable()
                ->comment('عنوان IP مصدر المعاملة');

            $table->timestamps();
            $table->softDeletes();

            // Indexes للبحث السريع
            $table->index('agent_id');
            $table->index('type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['agent_id', 'type', 'created_at']);
            $table->index(['agent_id', 'created_at']);
            $table->index('reference_number');
        });

        DB::statement('ALTER TABLE agent_transactions COMMENT = "جدول معاملات الوكيل (إيداع وسحب)"');
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_transactions');
    }
};
```

## هجرة إنشاء جدول agent_daily_snapshots

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_daily_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();

            $table->date('snapshot_date')
                ->comment('تاريخ اللقطة');

            $table->decimal('opening_balance', 15, 2)
                ->comment('رصيد الافتتاح');

            $table->decimal('closing_balance', 15, 2)
                ->comment('رصيد الإغلاق');

            $table->decimal('total_cash_in', 15, 2)
                ->default(0.00)
                ->comment('إجمالي الإيداعات في هذا اليوم');

            $table->decimal('total_cash_out', 15, 2)
                ->default(0.00)
                ->comment('إجمالي السحوبات في هذا اليوم');

            $table->decimal('total_commission', 15, 3)
                ->default(0.000)
                ->comment('إجمالي العمولات في هذا اليوم');

            $table->integer('transaction_count')
                ->default(0)
                ->comment('عدد المعاملات في هذا اليوم');

            $table->timestamps();

            // فريد لكل وكيل في كل يوم
            $table->unique(['agent_id', 'snapshot_date']);
            $table->index(['agent_id', 'snapshot_date']);
            $table->index('snapshot_date');
        });

        DB::statement('ALTER TABLE agent_daily_snapshots COMMENT = "لقطات يومية لحساب الوكيل"');
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_daily_snapshots');
    }
};
```

## هجرة إضافة فهارس إضافية للوحة التحكم

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة فهارس إضافية لتسريع استعلامات لوحة التحكم
        Schema::table('agent_transactions', function (Blueprint $table) {
            $table->index(['agent_id', 'type', 'status', 'created_at'], 'idx_agent_dashboard_lookup');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_agent_status_date');
        });
    }

    public function down(): void
    {
        Schema::table('agent_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_agent_dashboard_lookup');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex('idx_agent_status_date');
        });
    }
};
```

## ملخص الجداول

| الجدول | الحقول الأساسية | المفاتيح الخارجية | الفهارس |
|--------|----------------|-------------------|---------|
| agent_transactions | id, agent_id, type (cash_in/cash_out), user_id, amount, currency, commission, balance_before, balance_after, status, reference_number | agent_id → agents, user_id → users | agent_id+type+status+created_at, reference_number |
| agent_daily_snapshots | id, agent_id, snapshot_date, opening_balance, closing_balance, total_cash_in, total_cash_out, total_commission, transaction_count | agent_id → agents | agent_id+snapshot_date (unique) |

## ملاحظات الأداء

1. **فهرس مركب:** `agent_id + type + status + created_at` يُغطي معظم استعلامات لوحة التحكم
2. **لقطات يومية:** تمنع الحاجة لحساب الإحصائيات من الصفر كل مرة
3. **الترقيم:** استعلامات المعاملات تستخدم `cursor pagination` للكميات الكبيرة
4. **الكميات المالية:** تستخدم `decimal(15, 2)` للدقة العالية ومنع أخطاء التقريب
