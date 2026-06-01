# TST1 - اختبارات Laravel

## الوصف
اختبارات الوحدة والتكامل للـ API.

## إعداد الاختبارات
```bash
php artisan make:test AuthTest
php artisan make:test WalletTest
php artisan make:test TransferTest
php artisan make:test MerchantTest
php artisan make:test AgentTest
php artisan make:test DealTest
```

## قائمة الاختبارات المطلوبة

### Auth
- تسجيل مستخدم جديد ← 200, محفظتان
- تسجيل برقم موجود ← 400
- تسجيل الدخول ← 200 + token
- تسجيل الدخول بحساب معلق ← 403
- طلب OTP ← 200
- التحقق من OTP ← 200

### Wallet
- عرض الرصيد ← 200
- تحويل بين العملات ← 200
- تحويل بين العملات برصيد غير كاف ← 400

### Transfer
- تحويل بين مستخدمين ← 200
- تحويل إلى النفس ← 400
- تحويل برصيد غير كاف ← 400
- تحويل برقم PIN خاطئ ← 400

### Merchant
- تسجيل تاجر ← 201
- إنشاء منتج ← 201
- إنشاء رابط دفع ← 200
- معالجة دفع ← 200

### Agent
- إيداع نقدي ← 200
- سحب نقدي ← 200
- رمز سحب منتهي الصلاحية ← 400

### Deals
- إنشاء صفقة ← 201
- المشاركة في صفقة ← 200
- إتمام صفقة + توزيع أرباح ← 200

## تشغيل الاختبارات
```bash
php artisan test
php artisan test --filter=TransferTest
```

## اختبارات Feature (HTTP)
```php
public function test_transfer_between_users()
{
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $senderWallet = $sender->wallets()->where('currency', 'USD')->first();
    $senderWallet->balance = 1000;
    $senderWallet->save();

    $response = $this->actingAs($sender)->postJson('/api/transfer', [
        'to_phone' => $receiver->phone,
        'amount' => 100,
        'currency' => 'USD',
        'pin' => '1234',
    ]);

    $response->assertStatus(200);
    $this->assertEquals(900, $senderWallet->fresh()->balance);
    $this->assertEquals(100, $receiver->wallets()->where('currency','USD')->first()->balance);
}
```
