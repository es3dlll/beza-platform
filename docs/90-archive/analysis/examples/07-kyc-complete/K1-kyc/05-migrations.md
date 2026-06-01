# 05 - كود الميغريشن الكامل (Migrations)

## جدول وثائق KYC (kyc_documents)

```php
<?php
// database/migrations/2024_01_01_000030_create_kyc_documents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('doc_type', ['ID', 'Passport', 'Driver_License']);
            $table->enum('doc_category', ['front_id', 'back_id', 'selfie', 'address_proof']);
            $table->string('file_path', 500);
            $table->string('file_hash', 64);
            $table->string('mime_type', 50);
            $table->boolean('auto_verified')->default(false);
            $table->text('auto_rejection_reason')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'doc_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};
```

## جدول مراجعات KYC (kyc_reviews)

```php
<?php
// database/migrations/2024_01_01_000031_create_kyc_reviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('reviewed_by')->constrained('users');
            $table->enum('status', ['approved', 'rejected']);
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->useCurrent();

            $table->index('user_id');
            $table->index('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_reviews');
    }
};
```

## إضافة أعمدة KYC للمستخدمين

```php
<?php
// database/migrations/2024_01_01_000032_add_kyc_fields_to_users.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('kyc_status', [
                'not_submitted', 'pending', 'verified', 'rejected'
            ])->default('not_submitted')->after('status');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kyc_status', 'kyc_verified_at']);
        });
    }
};
```
