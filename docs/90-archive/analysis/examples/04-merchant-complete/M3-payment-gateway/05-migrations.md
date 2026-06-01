# 05 - الميغريشن (Migrations)

```php
<?php
use IlluminateDatabaseMigrationsMigration;
use IlluminateDatabaseSchemaBlueprint;
use IlluminateSupportFacadesSchema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique();
            $table->decimal('amount', 15, 2);
            $table->enum('currency', ['SYP', 'USD']);
            $table->text('description')->nullable();
            $table->string('redirect_url', 500)->nullable();
            $table->enum('status', ['active','used','expired','cancelled'])->default('active');
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['token', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('payment_links'); }
};
```

## شرح الميغريشن
- token: 64 حرف عشوائي آمن، UNIQUE لمنع التكرار
- amount: دقة عالية (15,2) للعملات
- currency: SYP/USD فقط
- status: أربع حالات: active, used, expired, cancelled
- expires_at: وقت انتهاء صلاحية الرابط
- paid_at: وقت إتمام الدفع (nullable)
- index على (token, status) لتسريع البحث
