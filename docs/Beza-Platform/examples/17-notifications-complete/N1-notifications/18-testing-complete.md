# 18 - الاختبارات (Testing)

## Service Tests

```php
<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationService::class);
    }

    /** @test */
    public function it_sends_notification_to_user()
    {
        $user = User::factory()->create();
        NotificationTemplate::factory()->create([
            'type' => 'test_notification',
            'channels' => ['database'],
        ]);

        $notification = $this->service->send($user, 'test_notification', [
            'amount' => 100,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'notifiable_id' => $user->id,
            'type' => 'test_notification',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_throws_exception_for_invalid_template()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $user = User::factory()->create();
        $this->service->send($user, 'non_existent_type');
    }

    /** @test */
    public function it_sends_through_multiple_channels()
    {
        $user = User::factory()->create();
        NotificationTemplate::factory()->create([
            'type' => 'multi_channel',
            'channels' => ['fcm', 'sms', 'email', 'database'],
        ]);

        $results = $this->service->sendNow($user, 'multi_channel', []);

        $this->assertArrayHasKey('database', $results);
    }
}
```

## FCM Channel Test

```php
<?php

namespace Tests\Feature\Channels;

use App\Models\User;
use App\Services\Channels\FCMChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FCMChannelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_error_when_no_device_tokens()
    {
        $user = User::factory()->create();
        $channel = new FCMChannel();

        $result = $channel->send($user, [
            'title' => 'Test',
            'body' => 'Test body',
        ], ['type' => 'test']);

        $this->assertFalse($result['success']);
        $this->assertEquals('No device tokens', $result['error']);
    }

    /** @test */
    public function it_sends_to_active_device_tokens()
    {
        $user = User::factory()->create();
        $user->deviceTokens()->create([
            'token' => 'fake-fcm-token',
            'platform' => 'android',
            'is_active' => true,
        ]);

        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/test/messages/msg1'], 200),
        ]);

        $channel = new FCMChannel();
        $result = $channel->send($user, [
            'title' => 'Test',
            'body' => 'Test body',
        ], ['type' => 'test']);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['sent']);
    }
}
```

## API Tests

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_list_notifications()
    {
        Notification::factory()->count(5)->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => get_class($this->user),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function user_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => get_class($this->user),
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function user_cannot_mark_others_notification_as_read()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_id' => $otherUser->id,
            'notifiable_type' => get_class($otherUser),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertForbidden();
    }
}
```
