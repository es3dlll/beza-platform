# 05 - الهجرات (Migrations) - جدول الوكلاء

## هجرة إنشاء جدول agents

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\AgentStatus;
use App\Enums\ServiceType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->unique()
                ->comment('المستخدم المرتبط بالوكيل');

            $table->string('full_name', 150)
                ->comment('الاسم الكامل للوكيل');

            $table->string('phone', 20)
                ->unique()
                ->comment('رقم الهاتف');

            $table->string('national_id', 50)
                ->unique()
                ->comment('رقم الهوية الوطنية');

            $table->point('location', 4326)
                ->comment('موقع الوكيل (إحداثيات GPS)');

            $table->string('service_type', 50)
                ->comment('نوع الخدمة (transfer/bill_payment/cash_in_cash_out)');

            $table->string('status', 30)
                ->default(AgentStatus::PENDING->value)
                ->comment('حالة الوكيل: pending, active, suspended, rejected');

            $table->decimal('commission_rate', 5, 3)
                ->default(0.010)
                ->comment('نسبة العمولة (مثلاً 0.010 = 1%)');

            $table->decimal('max_daily_limit', 15, 2)
                ->default(500000.00)
                ->comment('الحد الأقصى اليومي للمعاملات');

            $table->decimal('current_daily_total', 15, 2)
                ->default(0.00)
                ->comment('إجمالي المعاملات اليومية الحالي');

            $table->timestamp('last_daily_reset_at')
                ->nullable()
                ->comment('آخر مرة تم فيها إعادة تعيين المجموع اليومي');

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('approved_at')->nullable()->comment('تاريخ الموافقة على الوكيل');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->comment('المسؤول الذي وافق على الوكيل');

            // Indexes
            $table->index('status');
            $table->index('service_type');
            $table->index('phone');
            $table->index('national_id');
            $table->index('created_at');

            // Spatial index for location queries
            $table->spatialIndex('location');
        });

        DB::statement('ALTER TABLE agents COMMENT = "جدول الوكلاء المسجلين في النظام"');
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
```

## هجرة إنشاء جدول agent_requests

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('مقدم الطلب');

            $table->string('full_name', 150);
            $table->string('phone', 20);
            $table->string('national_id', 50);
            $table->point('location', 4326);
            $table->string('service_type', 50);
            $table->string('status', 30)
                ->default('pending')
                ->comment('pending, approved, rejected');

            $table->text('rejection_reason')->nullable();
            $table->json('admin_notes')->nullable();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users');
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('national_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_requests');
    }
};
```

## هجرة إنشاء جدول agent_wallets

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete()
                ->unique();

            $table->decimal('balance', 15, 2)
                ->default(0.00)
                ->comment('الرصيد الحالي');

            $table->decimal('pending_balance', 15, 2)
                ->default(0.00)
                ->comment('الرصيد المعلق');

            $table->decimal('total_commission_earned', 15, 2)
                ->default(0.00)
                ->comment('إجمالي العمولات المكتسبة');

            $table->decimal('total_cash_in', 15, 2)
                ->default(0.00)
                ->comment('إجمالي الإيداعات');

            $table->decimal('total_cash_out', 15, 2)
                ->default(0.00)
                ->comment('إجمالي السحوبات');

            $table->string('currency', 3)
                ->default('SAR')
                ->comment('عملة المحفظة');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_wallets');
    }
};
```

## ملخص العلاقات

| الجدول | الحقول الأساسية | المفاتيح الخارجية | الفهارس المكانية |
|--------|-----------------|-------------------|-------------------|
| agents | id, user_id, full_name, phone, national_id, location, service_type, status, commission_rate, max_daily_limit | user_id → users, approved_by → users | location (SPATIAL) |
| agent_requests | id, user_id, full_name, phone, national_id, location, service_type, status | user_id → users, reviewed_by → users | — |
| agent_wallets | id, agent_id, balance, pending_balance, total_commission_earned | agent_id → agents | — |
