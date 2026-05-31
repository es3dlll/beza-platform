# 05 - الميغريشن (Migrations)

## merchants table
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->string('business_type', 100);
            $table->string('commercial_registration', 100)->unique();
            $table->string('tax_id', 100)->unique();
            $table->string('owner_phone', 20);
            $table->string('owner_name');
            $table->json('bank_account_info');
            $table->enum('status', ['pending','active','rejected','suspended'])->default('pending');
            $table->decimal('fee_percentage', 5, 2)->default(2.00);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('merchants'); }
};
```

## merchant_documents table
```php
Schema::create('merchant_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->enum('type', ['registration','commercial','tax','owner_id','bank_proof','other']);
    $table->string('file_path', 500);
    $table->string('file_type', 20);
    $table->unsignedInteger('file_size');
    $table->boolean('is_verified')->default(false);
    $table->timestamp('uploaded_at')->useCurrent();
});
```
