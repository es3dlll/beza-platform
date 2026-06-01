# 07 - دوال التحقق (Test Assertions)

## دوال التحقق الأساسية

```php
// استجابة API
$response->assertStatus(200);
$response->assertCreated(201);
$response->assertOk();
$response->assertNoContent(204);

// JSON
$response->assertJson(['success' => true]);
$response->assertJsonFragment(['message' => 'تم التحويل بنجاح']);
$response->assertJsonStructure(['success', 'data' => ['transaction', 'new_balance']]);
$response->assertJsonPath('data.new_balance', 400.00);

// Validation Errors
$response->assertJsonValidationErrors(['to_phone', 'amount']);
$response->assertJsonMissingValidationErrors('description');

// Headers
$response->assertHeader('Content-Type', 'application/json');

// Authentication
$response->assertUnauthorized();
$response->assertForbidden();
```

## دوال التحقق من قاعدة البيانات

```php
// وجود سجل
$this->assertDatabaseHas('transactions', [
    'reference_number' => 'BZ260527143200A1B2C3',
    'type' => 'transfer',
    'status' => 'completed',
]);

// عدم وجود سجل
$this->assertDatabaseMissing('transactions', [
    'reference_number' => 'BZINVALID',
]);

// عدد السجلات
$this->assertDatabaseCount('users', 3);
```

## دوال التحقق من النماذج

```php
$user = User::factory()->create();
$wallet = $user->usdWallet;

$this->assertTrue($user->hasTwoFactorEnabled());
$this->assertFalse($user->is_admin);
$this->assertEquals(1000.00, $wallet->balance);
$this->assertNotNull($user->wallets);
$this->assertCount(2, $user->wallets);
```

## دوال التحقق من الأحداث

```php
Event::fake();

// ... تنفيذ الإجراء ...

Event::assertDispatched(TransactionCompleted::class);
Event::assertDispatchedTimes(TransactionCompleted::class, 1);
Event::assertNotDispatched(FraudDetected::class);
```

## دوال التحقق من الإشعارات

```php
Notification::fake();

// ... تنفيذ الإجراء ...

Notification::assertSentTo($user, TransactionNotification::class);
Notification::assertNotSentTo($user, FraudAlertNotification::class);
```

## دوال التحقق من الاستثناءات

```php
$this->expectException(InsufficientBalanceException::class);
$this->expectExceptionMessage('رصيد غير كافٍ');
$this->expectExceptionCode(422);
```
