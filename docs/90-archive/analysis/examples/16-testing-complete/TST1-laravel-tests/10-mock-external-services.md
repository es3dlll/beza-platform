# 10 - محاكاة الخدمات الخارجية (Mock External Services)

## محاكاة FCM

```php
<?php

namespace Tests\Feature;

use App\Notifications\TransferNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_notification_after_transfer()
    {
        Notification::fake();

        // ... إجراء تحويل ...

        Notification::assertSentTo(
            $receiver,
            TransferNotification::class,
            function ($notification, $channels) {
                return in_array('fcm', $channels);
            }
        );
    }
}
```

## محاكاة Twilio (SMS)

```php
/** @test */
public function it_sends_otp_via_sms()
{
    // محاكاة Twilio
    $this->mock(\Twilio\Rest\Client::class, function ($mock) {
        $mock->shouldReceive('messages->create')
            ->once()
            ->with('963900000001', \Mockery::on(function ($options) {
                return str_contains($options['body'], 'رمز التحقق Beza');
            }));
    });

    $response = $this->postJson('/api/v1/auth/otp', [
        'phone' => '963900000001',
    ]);

    $response->assertStatus(200);
}
```

## محاكاة البريد الإلكتروني

```php
/** @test */
public function it_sends_email_notification()
{
    \Illuminate\Support\Facades\Mail::fake();

    // ... إجراء يستدعي إرسال بريد ...

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\WelcomeEmail::class);
    \Illuminate\Support\Facades\Mail::assertSentCount(1);
}
```

## محاكاة Redis

```php
/** @test */
public function it_uses_redis_for_rate_limiting()
{
    \Illuminate\Support\Facades\Redis::shouldReceive('incr')
        ->once()
        ->andReturn(1);

    \Illuminate\Support\Facades\Redis::shouldReceive('expire')
        ->once();

    // ... إجراء يستخدم Rate Limiter ...
}
```

## Mocking HTTP Clients

```php
/** @test */
public function it_calls_external_api()
{
    Http::fake([
        'https://api.external-service.com/*' => Http::response([
            'status' => 'success',
            'data' => ['id' => 'ext_123'],
        ], 200),
    ]);

    // ... إجراء يستدعي API خارجي ...

    Http::assertSent(function ($request) {
        return $request->url() == 'https://api.external-service.com/v1/verify'
            && $request->method() == 'POST';
    });
}
```
