# 05 - كود الميغريشن (Migrations)

## جدول النزاعات

```php
<?php
// database/migrations/2024_06_01_000040_create_disputes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('complainant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('respondent_id')->constrained('users')->onDelete('cascade');
            $table->string('reason');
            $table->text('description');
            $table->enum('status', ['open', 'investigating', 'resolved', 'rejected'])
                ->default('open');
            $table->enum('resolution', ['refund', 'reject', 'partial_refund'])->nullable();
            $table->decimal('partial_amount', 15, 2)->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('auto_closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
```

## جدول أدلة النزاعات

```php
<?php
// database/migrations/2024_06_01_000041_create_dispute_evidence_table.php

Schema::create('dispute_evidence', function (Blueprint $table) {
    $table->id();
    $table->foreignId('dispute_id')->constrained()->onDelete('cascade');
    $table->string('file_path');
    $table->string('original_name');
    $table->enum('type', ['image', 'document', 'other'])->default('image');
    $table->timestamps();
});
```
