# 19 - حالات الحافة (Edge Cases)

## 1. اختبار معاملة متزامنة (Race Condition)

```php
/** @test */
public function it_handles_concurrent_transfers()
{
    // محاولة 10 تحويلات متزامنة برصيد 500 فقط
    $amount = 50;
    $responses = [];

    for ($i = 0; $i < 10; $i++) {
        $responses[] = $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963944654321',
                'amount' => $amount,
                'currency' => 'USD',
                'pin' => '1234',
            ]);
    }

    $successCount = collect($responses)->where('status', 201)->count();
    $this->assertEquals(10, $successCount); // 10 × 50 = 500 (بالضبط)
}
```

## 2. اختبار حدود DECIMAL

```php
/** @test */
public function it_handles_decimal_amounts()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount' => 0.01, // أصغر مبلغ
            'currency' => 'USD',
            'pin' => '1234',
        ]);

    $response->assertStatus(201);
}
```

## 3. اختبار رموز التشفير

```php
/** @test */
public function pin_is_never_returned()
{
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/user/profile');

    $response->assertJsonMissing(['pin_code']);
    $response->assertJsonMissing(['password']);
}
```

## 4. اختبار Soft Delete

```php
/** @test */
public function soft_deleted_user_cannot_transfer()
{
    $this->sender->delete();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount' => 100,
            'currency' => 'USD',
            'pin' => '1234',
        ]);

    $response->assertStatus(401);
}
```
