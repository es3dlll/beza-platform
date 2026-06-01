# 08 - Ø§Ù„Ù€ Listener ÙˆØ§Ù„Ù…Ø³ØªÙ…Ø¹ (Listener Full Code)

## Ø¹Ù…Ù„ÙŠØ© Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ù…Ø­ÙØ¸Ø© Ù„ÙŠØ³Øª API â€” Ø¨Ù„ Event Listener

Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ù…Ø­ÙØ¸Ø© ÙŠØªÙ… ØªÙ„Ù‚Ø§Ø¦ÙŠØ§Ù‹ Ø¹Ø¨Ø± `User::created` eventØŒ Ù„Ø°Ù„Ùƒ Ù„Ø§ ÙŠÙˆØ¬Ø¯ Controller. Ø¨Ø¯Ù„Ø§Ù‹ Ù…Ù†Ù‡ØŒ Ù„Ø¯ÙŠÙ†Ø§ **Listener** ÙŠØ³ØªÙ…Ø¹ Ù„Ø­Ø¯Ø« Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù….

## Event: User::created (Eloquent Built-in)

Eloquent ÙŠØ·Ù„Ù‚ Ø­Ø¯Ø« `created` ØªÙ„Ù‚Ø§Ø¦ÙŠØ§Ù‹ Ø¨Ø¹Ø¯ ÙƒÙ„ Ø¹Ù…Ù„ÙŠØ© `User::create()`.

## Listener: CreateUserWallets

```php
<?php
// app/Listeners/CreateUserWallets.php

namespace App\Listeners;

use App\Events\WalletCreated;
use App\Models\User;
use App\Services\CreateWalletService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

class CreateUserWallets
{
    public function __construct(
        private readonly CreateWalletService $createWalletService
    ) {}

    /**
     * Ù…Ø¹Ø§Ù„Ø¬Ø© Ø­Ø¯Ø« ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø¬Ø¯ÙŠØ¯
     * Ø¥Ù†Ø´Ø§Ø¡ Ù…Ø­ÙØ¸Ø© SYP + USD Ù…Ø¹ Ù‡Ø¯ÙŠØ© 5$
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        try {
            $this->createWalletService->createWallets($user);

            Log::info('ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ Ù…Ø­Ø§ÙØ¸ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø¬Ø¯ÙŠØ¯', [
                'user_id' => $user->id,
                'phone'   => $user->phone,
            ]);
        } catch (\Throwable $e) {
            Log::error('ÙØ´Ù„ Ø¥Ù†Ø´Ø§Ø¡ Ù…Ø­Ø§ÙØ¸ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù…', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            // ÙÙŠ Ø­Ø§Ù„ Ø§Ù„ÙØ´Ù„ â€” Ù„Ø§ Ù†ÙˆÙ‚Ù Ø¹Ù…Ù„ÙŠØ© Ø§Ù„ØªØ³Ø¬ÙŠÙ„
            // Ù„ÙƒÙ† ÙŠØ¬Ø¨ Ù…Ø±Ø§Ù‚Ø¨ØªÙ‡Ø§ ÙˆØ¥Ø¹Ø§Ø¯Ø© Ø§Ù„Ù…Ø­Ø§ÙˆÙ„Ø© ÙŠØ¯ÙˆÙŠØ§Ù‹
            report($e);
        }
    }
}
```

## ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ù€ Listener ÙÙŠ EventServiceProvider

```php
<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Listeners\CreateUserWallets;
use App\Listeners\SendWelcomeNotification;
use App\Events\WalletCreated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            CreateUserWallets::class,
        ],

        WalletCreated::class => [
            SendWelcomeNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
```

## Ù…Ø«Ø§Ù„ ØªØ¯ÙÙ‚ Ø§Ù„Ø§Ø³ØªØ¬Ø§Ø¨Ø© (Ø¹Ù†Ø¯ Ø§Ù„ØªØ³Ø¬ÙŠÙ„)

### Ù†Ø¬Ø§Ø­ (201)
```json
{
    "success": true,
    "message": "ØªÙ… Ø§Ù„ØªØ³Ø¬ÙŠÙ„ Ø¨Ù†Ø¬Ø§Ø­",
    "data": {
        "user": {
            "id": 1,
            "name": "Ø£Ø­Ù…Ø¯",
            "phone": "963944123456",
            "status": "active"
        },
        "token": "jwt_token_here",
        "wallets": {
            "syp": {
                "wallet_number": "621234567890",
                "balance": 0.00
            },
            "usd": {
                "wallet_number": "631234567890",
                "balance": 5.00
            }
        }
    }
}
```

### ÙØ´Ù„ (422)
```json
{
    "success": false,
    "message": "Ø¨ÙŠØ§Ù†Ø§Øª ØºÙŠØ± ØµØ­ÙŠØ­Ø©",
    "errors": {
        "phone": ["Ø±Ù‚Ù… Ø§Ù„Ù‡Ø§ØªÙ Ù…Ø³Ø¬Ù„ Ù…Ø³Ø¨Ù‚Ø§Ù‹"],
        "pin_code": ["PIN ÙŠØ¬Ø¨ Ø£Ù† ÙŠÙƒÙˆÙ† 4 Ø£Ø±Ù‚Ø§Ù…"]
    }
}
```

## Ø§Ù„Ù…Ø³Ø§Ø± (Route) (Ù„Ù„ØªØ³Ø¬ÙŠÙ„)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:10,1');
```
