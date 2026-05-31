# 05 - الميغريشن (Migrations)

## merchant_products table
```php
<?php
use IlluminateDatabaseMigrationsMigration;
use IlluminateDatabaseSchemaBlueprint;
use IlluminateSupportFacadesSchema;

return new class extends Migration {
    public function up(): void {
        Schema::create('merchant_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_syp', 15, 2);
            $table->decimal('price_usd', 15, 2);
            $table->string('category', 100)->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['merchant_id', 'is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('merchant_products'); }
};
```

## product_images table
```php
Schema::create('product_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('merchant_products')->onDelete('cascade');
    $table->string('image_path', 500);
    $table->boolean('is_primary')->default(false);
    $table->integer('sort_order')->default(0);
});

-- إضافة unique constraint لمنع تكرار الترتيب
Schema::table('product_images', function (Blueprint $table) {
    $table->unique(['product_id', 'sort_order']);
});
```

## شرح الميغريشن
- merchant_id: مفتاح خارجي يشير إلى merchants، مع onDelete cascade
- price_syp/price_usd: أسعار المنتج بالعملتين
- stock: nullable للمنتجات الرقمية غير المحدودة
- is_active: للتحكم في ظهور المنتج
- الصور تحمل sort_order لترتيب العرض
