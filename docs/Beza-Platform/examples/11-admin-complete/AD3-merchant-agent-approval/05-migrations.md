# 05 - كود الميغريشن (Migrations)

## جدول التجار (merchants)

```php
<?php
// database/migrations/2024_06_01_000020_create_merchants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->string('business_type')->nullable();
            $table->string('commercial_reg_no')->nullable();
            $table->string('tax_card_no')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'active', 'rejected', 'suspended'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->bigInteger('total_transactions')->default(0);
            $table->decimal('total_volume', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
```

## جدول مستندات التجار

```php
<?php
// database/migrations/2024_06_01_000021_create_merchant_documents_table.php

Schema::create('merchant_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->enum('type', [
        'commercial_reg', 'tax_card', 'id_photo',
        'license', 'contract', 'other'
    ]);
    $table->string('file_path');
    $table->string('original_name');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

## جدول الوكلاء (agents)

```php
<?php
// database/migrations/2024_06_01_000022_create_agents_table.php

Schema::create('agents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
    $table->string('office_name');
    $table->string('license_number')->nullable();
    $table->text('address')->nullable();
    $table->json('service_areas')->nullable();
    $table->enum('status', ['pending', 'active', 'rejected', 'suspended'])->default('pending');
    $table->text('rejection_reason')->nullable();
    $table->foreignId('reviewed_by')->nullable()->constrained('users');
    $table->timestamp('reviewed_at')->nullable();
    $table->bigInteger('total_transactions')->default(0);
    $table->decimal('total_commission', 15, 2)->default(0);
    $table->timestamps();
});
```
