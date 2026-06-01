# 10 - تكامل FCM (Firebase Cloud Messaging)

```php
<?php

namespace App\Services\Channels;

use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMChannel
{
    private string $projectId;
    private string $accessToken;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id');
    }

    private function getAccessToken(): string
    {
        if (isset($this->accessToken)) return $this->accessToken;

        $client = new GoogleClient();
        $client->setAuthConfig(storage_path('app/fcm-credentials.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $this->accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        return $this->accessToken;
    }

    public function send(User $user, array $compiled, array $data): array
    {
        $deviceTokens = $user->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($deviceTokens)) {
            return ['success' => false, 'error' => 'No device tokens'];
        }

        $success = 0;
        $failed = 0;

        foreach ($deviceTokens as $token) {
            try {
                $response = Http::withToken($this->getAccessToken())
                    ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                        'message' => [
                            'token' => $token,
                            'notification' => [
                                'title' => $compiled['title'],
                                'body' => $compiled['body'],
                            ],
                            'data' => array_merge($data, ['type' => $data['type'] ?? 'general']),
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'channel_id' => 'default',
                                    'sound' => 'default',
                                ],
                            ],
                            'apns' => [
                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                        'badge' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    $success++;
                } else {
                    $failed++;
                    Log::warning("FCM failed for token {$token}: {$response->body()}");
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error("FCM error: {$e->getMessage()}");
            }
        }

        return [
            'success' => $failed === 0,
            'sent' => $success,
            'failed' => $failed,
            'total' => count($deviceTokens),
        ];
    }
}
```
