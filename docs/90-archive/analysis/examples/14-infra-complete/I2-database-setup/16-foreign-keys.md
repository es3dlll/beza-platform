# 16 - المفاتيح الخارجية (Foreign Keys)

## كل المفاتيح الخارجية في النظام

```php
// جدول wallets
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// جدول transactions
$table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
$table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();

// جدول merchants
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// جدول merchant_products
$table->foreignId('merchant_id')->constrained()->onDelete('cascade');

// جدول merchant_orders
$table->foreignId('user_id')->constrained();
$table->foreignId('merchant_id')->constrained();
$table->foreignId('product_id')->constrained('merchant_products');

// جدول deals
$table->foreignId('created_by')->constrained('users');

// جدول deal_investments
$table->foreignId('deal_id')->constrained()->onDelete('cascade');
$table->foreignId('user_id')->constrained();

// جدول cards
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// جدول card_transactions
$table->foreignId('card_id')->constrained()->onDelete('cascade');

// جدول agents
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// جدول agent_transactions
$table->foreignId('agent_id')->constrained();
$table->foreignId('user_id')->nullable()->constrained();

// جدول referrals
$table->foreignId('referrer_id')->constrained('users');
$table->foreignId('referred_id')->constrained('users');

// جدول kyc_documents
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// جدول disputes
$table->foreignId('user_id')->constrained();
$table->foreignId('transaction_id')->nullable()->constrained();

// جدول audit_logs
$table->foreignId('user_id')->nullable()->constrained();
```

## سلوك الحذف (ON DELETE)

| السلوك | المعنى | أين يستخدم |
|--------|--------|-----------|
| CASCADE | حذف السجل الأب يحذف الأبناء | wallets, kyc_documents, cards |
| SET NULL | حذف الأب يجعل المفتاح NULL | transactions (from/to wallet) |
| RESTRICT | منع حذف الأب إذا كان له أبناء | merchant_orders, deals |
| NO ACTION | مثل RESTRICT في InnoDB | - |
