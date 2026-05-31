# 20 - اختبارات أمنية (Security Testing)

## اختبار SQL Injection

```php
use Tymon\JWTAuth\Facades\JWTAuth;

/** @test */
public function it_prevents_sql_injection()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/transfer', [
            'to_phone' => "' OR '1'='1",
            'amount' => 100,
            'currency' => 'USD',
            'pin' => '1234',
        ]);

    $response->assertStatus(404); // لا يوجد مستخدم بهذا الرقم
    // لم يتم تنفيذ SQL Injection
}
```

## اختبار XSS

```php
/** @test */
public function it_prevents_xss_in_description()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount' => 100,
            'currency' => 'USD',
            'pin' => '1234',
            'description' => '<script>alert("xss")</script>',
        ]);

    $response->assertStatus(201);

    // التحقق من أن HTML تم تعقيمه
    $txn = Transaction::latest()->first();
    $this->assertStringNotContainsString('<script>', $txn->description);
}
```

## اختبار Mass Assignment

```php
/** @test */
public function it_prevents_mass_assignment()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount' => 100,
            'currency' => 'USD',
            'pin' => '1234',
            'is_admin' => true, // محاولة رفع الصلاحية
        ]);

    $response->assertStatus(201);
    $this->assertFalse($this->sender->fresh()->is_admin);
}
```

## اختبار Rate Limiting

```php
/** @test */
public function it_enforces_rate_limiting()
{
    for ($i = 0; $i < 30; $i++) {
        $this->withToken($this->token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963944654321',
                'amount' => 1,
                'currency' => 'USD',
                'pin' => '0000', // خطأ
            ]);
    }

    // المحاولة 31
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount' => 1,
            'currency' => 'USD',
            'pin' => '0000',
        ]);

    $response->assertStatus(429); // Too Many Requests
}
```

## اختبار IDOR

```php
/** @test */
public function it_prevents_idor()
{
    $otherUser = User::factory()->create();
    $otherToken = JWTAuth::fromUser($otherUser);

    // محاولة الوصول لرصيد مستخدم آخر
    $response = $this->withToken($otherToken)
        ->getJson('/api/v1/wallet?user_id=' . $this->sender->id);

    $response->assertStatus(200);
    // يجب إرجاع رصيد المستخدم المصادق فقط
    $this->assertEquals($otherUser->usdWallet->balance, $response['data']['usd']);
}
```
