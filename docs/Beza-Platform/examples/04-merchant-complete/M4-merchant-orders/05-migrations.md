# 05 - الميغريشن

```php
Schema::create('merchant_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained('users');
    $table->string('order_number', 50)->unique();
    $table->enum('status', ['pending','processing','shipped','delivered','cancelled'])->default('pending');
    $table->decimal('total_amount', 15, 2);
    $table->enum('currency', ['SYP', 'USD']);
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['merchant_id', 'status']);
});

Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained('merchant_orders')->onDelete('cascade');
    $table->string('product_name');
    $table->unsignedInteger('quantity');
    $table->decimal('unit_price', 15, 2);
});
```
