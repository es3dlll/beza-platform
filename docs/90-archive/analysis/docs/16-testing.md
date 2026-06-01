# 16. الاختبارات الشاملة

## 16.1 Laravel Unit & Feature Tests

```php
// tests/Feature/TransferTest.php
public function test_user_can_transfer_money_to_another_user()
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
    $this->assertDatabaseHas('transactions', [
        'amount' => 100,
        'type' => 'transfer',
        'status' => 'completed',
    ]);
    $this->assertEquals(900, $senderWallet->fresh()->balance);
    $this->assertEquals(100, $receiver->wallets()->where('currency','USD')->first()->balance);
}
```

## 16.2 Flutter Widget & Integration Tests

```dart
testWidgets('Login flow', (tester) async {
  await tester.pumpWidget(MyApp());
  await tester.enterText(find.byKey(Key('phone')), '0991234567');
  await tester.enterText(find.byKey(Key('password')), 'password');
  await tester.tap(find.text('دخول'));
  await tester.pumpAndSettle();
  expect(find.text('الرصيد'), findsOneWidget);
});
```

## 16.3 اختبار الحمل باستخدام K6

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '30s', target: 100 },
    { duration: '1m', target: 100 },
    { duration: '30s', target: 0 },
  ],
};

export default function () {
  let res = http.get('https://api.beza.com/v1/ping');
  check(res, { 'status is 200': (r) => r.status === 200 });
  sleep(1);
}
```
