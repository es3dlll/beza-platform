# 05 - Ø§Ù„Ù…ÙŠØºØ±ÙŠØ´Ù† (Migrations)

## JWT â€” Ø§Ù„Ù…ØµØ§Ø¯Ù‚Ø© Ø§Ù„Ø¥Ø­ØµØ§Ø¦ÙŠØ© (Stateless Authentication)

ØªÙ… Ø§Ø³ØªØ¨Ø¯Ø§Ù„ JWT Ø¨Ù€ **JWT (JSON Web Tokens)** Ø¨Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø­Ø²Ù…Ø© `tymon/jwt-auth`:

```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

## Ù…Ù„Ø§Ø­Ø¸Ø§Øª Ø¹Ù„Ù‰ JWT

JWT Ù‡Ùˆ Ù†Ø¸Ø§Ù… **Stateless** â€” Ù„Ø§ ÙŠØ­ØªØ§Ø¬ Ø¬Ø¯ÙˆÙ„ ØªØ®Ø²ÙŠÙ† Ù„Ù„ØªÙˆÙƒÙ†Ø§Øª Ù„Ø£Ù† Ø§Ù„ØªÙˆÙƒÙ† Ù…Ø´ÙØ± Ø°Ø§ØªÙŠØ§Ù‹ ÙˆÙŠØ­Ù…Ù„ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø¯Ø§Ø®Ù„Ù‡. Ù„ÙƒÙ† Ù„ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬ Ù†Ø­ØªØ§Ø¬ Ø¬Ø¯ÙˆÙ„ `token_blacklist`:

### Ø¬Ø¯ÙˆÙ„ token_blacklist

```php
<?php
// database/migrations/2024_01_01_000003_create_token_blacklist_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('jti', 255)->unique(); // JWT ID
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_blacklist');
    }
};
```

## Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª JWT

```php
<?php
// config/jwt.php â€” Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ø§Ù„Ø£Ø³Ø§Ø³ÙŠØ©

return [
    'ttl' => env('JWT_TTL', 60),                 // 60 Ø¯Ù‚ÙŠÙ‚Ø©
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 14 ÙŠÙˆÙ…
    'algo' => env('JWT_ALGO', 'HS256'),
    'secret' => env('JWT_SECRET'),
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
    'providers' => [
        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,
        'user' => Tymon\JWTAuth\Providers\User\EloquentUserAdapter::class,
    ],
];
```

## Ù…Ù„Ø§Ø­Ø¸Ø§Øª

| Ø§Ù„Ø¹Ù†ØµØ± | Ø§Ù„Ù‚ÙŠÙ…Ø© |
|--------|--------|
| Ù…Ø¯Ø© ØµÙ„Ø§Ø­ÙŠØ© Ø§Ù„ØªÙˆÙƒÙ† (TTL) | 60 Ø¯Ù‚ÙŠÙ‚Ø© (Ù‚Ø§Ø¨Ù„Ø© Ù„Ù„ØªØºÙŠÙŠØ±) |
| ØªØ¬Ø¯ÙŠØ¯ Ø§Ù„ØªÙˆÙƒÙ† (Refresh TTL) | 14 ÙŠÙˆÙ…Ø§Ù‹ |
| Ø§Ù„Ø®ÙˆØ§Ø±Ø²Ù…ÙŠØ© | HS256 (Ø£Ùˆ RS256 Ù„Ù„Ø¥Ù†ØªØ§Ø¬) |
| blacklist | `token_blacklist` Ù„ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬ |
| Ø¥Ù„ØºØ§Ø¡ Ø§Ù„ØªÙˆÙƒÙ† | `JWTAuth::invalidate($token)` |
