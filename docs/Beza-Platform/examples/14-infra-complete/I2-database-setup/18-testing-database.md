# 18 - اختبار قاعدة البيانات (Testing Database)

## اختبار إنشاء الجداول

```php
public function test_database_has_all_tables()
{
    $tables = ['users', 'wallets', 'transactions', 'merchants', 'merchant_products',
               'merchant_orders', 'deals', 'deal_investments', 'cards', 'card_transactions',
               'agents', 'agent_transactions', 'referrals', 'kyc_documents', 'disputes',
               'settings', 'notifications', 'audit_logs'];

    foreach ($tables as $table) {
        $this->assertTrue(Schema::hasTable($table), "Table {$table} does not exist");
    }
}
```

## اختبار العلاقات

```php
public function test_user_wallet_relationship()
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);

    $this->assertTrue($user->wallets->contains($wallet));
    $this->assertEquals($user->id, $wallet->user->id);
}
```

## اختبار القيود

```php
public function test_unique_phone_constraint()
{
    User::factory()->create(['phone' => '963900000001']);

    $this->expectException(\Illuminate\Database\QueryException::class);
    $this->expectExceptionMessageMatches('/Duplicate entry/');

    User::factory()->create(['phone' => '963900000001']);
}
```

## اختبار المفاتيح الخارجية

```php
public function test_foreign_key_cascade_delete()
{
    $user = User::factory()->create();
    Wallet::factory()->create(['user_id' => $user->id]);

    $this->assertEquals(2, Wallet::where('user_id', $user->id)->count());

    $user->delete();

    $this->assertEquals(0, Wallet::where('user_id', $user->id)->count());
}
```

## اختبار ACID

```php
public function test_transaction_atomicity()
{
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id, 'currency' => 'USD', 'balance' => 100
    ]);
    $receiverWallet = Wallet::factory()->create([
        'user_id' => $receiver->id, 'currency' => 'USD', 'balance' => 0
    ]);

    try {
        DB::transaction(function () use ($senderWallet, $receiverWallet) {
            DB::update('UPDATE wallets SET balance = balance - 50 WHERE id = ?', [$senderWallet->id]);
            throw new \Exception('فشل محاكى');
            DB::update('UPDATE wallets SET balance = balance + 50 WHERE id = ?', [$receiverWallet->id]);
        });
    } catch (\Exception $e) {
        // متوقع
    }

    $this->assertEquals(100, $senderWallet->fresh()->balance); // لم يتغير
    $this->assertEquals(0, $receiverWallet->fresh()->balance); // لم يتغير
}
```

## تشغيل اختبارات قاعدة البيانات

```bash
php artisan test --filter=DatabaseTest
php artisan test --filter=WalletTest
php artisan test --filter=TransactionTest
```
