# 20 - Ø£Ù…Ø§Ù† ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬ (Security Audit)

## Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø©

Ø£Ù…Ø§Ù† ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬ Ù„Ø§ ÙŠÙ‚Ù„ Ø£Ù‡Ù…ÙŠØ© Ø¹Ù† Ø£Ù…Ø§Ù† ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø¯Ø®ÙˆÙ„. Ø£ÙŠ Ø«ØºØ±Ø© ÙÙŠ logout Ù‚Ø¯ ØªØ¤Ø¯ÙŠ Ø¥Ù„Ù‰ Ø¨Ù‚Ø§Ø¡ Ø§Ù„Ø¬Ù„Ø³Ø© Ù†Ø´Ø·Ø© Ø±ØºÙ… Ø·Ù„Ø¨ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø¥Ù†Ù‡Ø§Ø¡Ù‡Ø§ØŒ Ù…Ù…Ø§ ÙŠØ³Ù…Ø­ Ø¨Ø³Ø±Ù‚Ø© Ø§Ù„Ø­Ø³Ø§Ø¨.

## 1. Ø¥Ø¨Ø·Ø§Ù„ Ø§Ù„ØªÙˆÙƒÙ† (Token Invalidation)

Ø¹Ù†Ø¯ ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬ØŒ ÙŠØ¬Ø¨ Ø¥Ø¨Ø·Ø§Ù„ Ø§Ù„ØªÙˆÙƒÙ† Ø§Ù„Ø­Ø§Ù„ÙŠ ÙÙˆØ±Ø§Ù‹ (Ø¥Ø¶Ø§ÙØªÙ‡ Ø¥Ù„Ù‰ Ø§Ù„Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ø³ÙˆØ¯Ø§Ø¡ Ø£Ùˆ Ø§Ù„Ø§Ø¹ØªÙ…Ø§Ø¯ Ø¹Ù„Ù‰ Ø§Ù†ØªÙ‡Ø§Ø¡ Ø§Ù„ØµÙ„Ø§Ø­ÙŠØ©).

### Ø¢Ù„ÙŠØ© Ø§Ù„Ø¹Ù…Ù„

```php
// ÙÙŠ AuthService
public function logout(User $user): void
{
    $token = JWTAuth::parseToken()->authenticate();

    if (! $token) {
        Log::warning('Ù…Ø­Ø§ÙˆÙ„Ø© Ø®Ø±ÙˆØ¬ Ø¨Ø¯ÙˆÙ† ØªÙˆÙƒÙ† Ù†Ø´Ø·', [
            'user_id' => $user->id,
            'ip'      => request()->ip(),
        ]);
        return;
    }

    // ØªØ³Ø¬ÙŠÙ„ Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„ØªÙˆÙƒÙ† Ù‚Ø¨Ù„ Ø§Ù„Ø­Ø°Ù
    $tokenData = [
        'token_id' => $token->id,
        'name'     => $token->name,
        'abilities' => $token->abilities,
    ];

    JWTAuth::invalidate($token);

    // ØªØ³Ø¬ÙŠÙ„ Ø¹Ù…Ù„ÙŠØ© Ø§Ù„Ø¥Ø¨Ø·Ø§Ù„ ÙÙŠ Audit Log
    Log::info('ØªÙ… Ø¥Ø¨Ø·Ø§Ù„ Ø§Ù„ØªÙˆÙƒÙ†', [
        'user_id'    => $user->id,
        'token_info' => $tokenData,
        'ip'         => request()->ip(),
        'user_agent' => request()->userAgent(),
        'timestamp'  => now()->toIso8601String(),
    ]);
}
```

### Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø¹Ø¯Ù… Ù‚Ø¨ÙˆÙ„ Ø§Ù„ØªÙˆÙƒÙ† Ø§Ù„Ù…Ù„ØºÙŠ

ÙŠØ¬Ø¨ Ø§Ø®ØªØ¨Ø§Ø± Ø£Ù† Ø§Ù„ØªÙˆÙƒÙ† Ø§Ù„Ù…Ù„ØºÙŠ Ù„Ù… ÙŠØ¹Ø¯ ØµØ§Ù„Ø­Ø§Ù‹:

```php
// tests/Feature/LogoutTest.php
public function test_deleted_token_is_rejected()
{
    $user  = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    // ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬
    $this->withToken($token)
         ->postJson('/api/v1/auth/logout')
         ->assertOk();

    // Ù…Ø­Ø§ÙˆÙ„Ø© Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„ØªÙˆÙƒÙ† Ù†ÙØ³Ù‡
    $this->withToken($token)
         ->getJson('/api/v1/user/profile')
         ->assertStatus(401);
}

public function test_other_tokens_still_work()
{
    $user    = User::factory()->create();
    $token1  = JWTAuth::fromUser($user);
    $token2  = JWTAuth::fromUser($user);

    // ØªØ³Ø¬ÙŠÙ„ Ø®Ø±ÙˆØ¬ Ù…Ù† Ø§Ù„Ø¬Ù‡Ø§Ø² Ø§Ù„Ø£ÙˆÙ„ ÙÙ‚Ø·
    $this->withToken($token1)
         ->postJson('/api/v1/auth/logout')
         ->assertOk();

    // Ø§Ù„Ø¬Ù‡Ø§Ø² Ø§Ù„Ø«Ø§Ù†ÙŠ Ù„Ø§ ÙŠØ²Ø§Ù„ ÙŠØ¹Ù…Ù„
    $this->withToken($token2)
         ->getJson('/api/v1/user/profile')
         ->assertOk();
}
```

## 2. ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬ Ù…Ù† Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø£Ø¬Ù‡Ø²Ø© (Logout All Devices)

### Audit Logging Ø´Ø§Ù…Ù„

```php
// ÙÙŠ AuthService
public function logoutFromAllDevices(User $user): int
{
    $tokens = $user->tokens()->get();

    $logoutEvent = LogoutAudit::create([
        'user_id'      => $user->id,
        'action'       => 'logout_all',
        'device_count' => $tokens->count(),
        'ip_address'   => request()->ip(),
        'user_agent'   => request()->userAgent(),
        'metadata'     => json_encode([
            'token_names' => $tokens->pluck('name')->toArray(),
            'token_ids'   => $tokens->pluck('id')->toArray(),
        ]),
    ]);

    // JWT: Ø¥Ø¨Ø·Ø§Ù„ Ø¬Ù…ÙŠØ¹ Ø§Ù„ØªÙˆÙƒÙ†Ø§Øª Ø¨Ø²ÙŠØ§Ø¯Ø© Ø±Ù‚Ù… Ø¥ØµØ¯Ø§Ø± Ø§Ù„ØªÙˆÙƒÙ†;

    // Ø¥Ø¨Ø·Ø§Ù„ FCM token Ø£ÙŠØ¶Ø§Ù‹ Ù„Ù…Ù†Ø¹ Ø§Ù„Ø¥Ø´Ø¹Ø§Ø±Ø§Øª Ù„Ù„Ø¬Ù„Ø³Ø§Øª Ø§Ù„Ù…ÙŠØªØ©
    $user->update(['fcm_token' => null]);

    return $tokens->count();
}
```

### Ù†Ù…ÙˆØ°Ø¬ Audit

```php
<?php
// app/Models/LogoutAudit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogoutAudit extends Model
{
    protected $table = 'logout_audits';

    protected $fillable = [
        'user_id',
        'action',        // 'logout' | 'logout_all' | 'force_logout' | 'password_change_logout'
        'device_count',
        'ip_address',
        'user_agent',
        'fingerprint',
        'metadata',
        'logged_out_at',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'logged_out_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc')->limit(50);
    }
}
```

### Ø§Ù„ØªØ±Ø­ÙŠÙ„ (Migration)

```php
// database/migrations/xxxx_xx_xx_create_logout_audits_table.php

Schema::create('logout_audits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('action');                    // logout, logout_all, force_logout
    $table->integer('device_count')->default(0);
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->string('fingerprint', 64)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('logged_out_at')->nullable();
    $table->timestamps();

    $table->index('user_id');
    $table->index('created_at');
});
```

## 3. Ù…Ù†Ø¹ Ø§Ø®ØªØ·Ø§Ù Ø§Ù„Ø¬Ù„Ø³Ø© (Session Hijacking Prevention)

### Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª ÙˆÙ‚Ø§Ø¦ÙŠØ© Ø¹Ù†Ø¯ logout

```php
<?php
// app/Http/Middleware/SessionSecurityMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SessionSecurityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Ø¥Ø¶Ø§ÙØ© Ù‡ÙŠØ¯Ø±Ø² Ø£Ù…Ù†ÙŠØ© Ù„Ù„Ø§Ø³ØªØ¬Ø§Ø¨Ø©
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->is('api/v1/auth/logout*')) {
            $response->headers->set('Clear-Site-Data', '"cache", "cookies", "storage"');
        }

        return $response;
    }
}
```

### Ø¥Ø¨Ø·Ø§Ù„ Ø§Ù„ØªÙˆÙƒÙ† Ø¹Ù†Ø¯ ØªØºÙŠÙŠØ± ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ±

```php
// ÙÙŠ AuthService
public function changePassword(User $user, string $newPassword): void
{
    $user->update([
        'password' => bcrypt($newPassword),
    ]);

    // Ø¥Ø¨Ø·Ø§Ù„ Ø¬Ù…ÙŠØ¹ Ø§Ù„ØªÙˆÙƒÙ† â€” Ø¥Ø¬Ø¨Ø§Ø± Ø¹Ù„Ù‰ Ø¥Ø¹Ø§Ø¯Ø© ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø¯Ø®ÙˆÙ„
    $deviceCount = $user->tokens()->count();
    // JWT: Ø¥Ø¨Ø·Ø§Ù„ Ø¬Ù…ÙŠØ¹ Ø§Ù„ØªÙˆÙƒÙ†Ø§Øª Ø¨Ø²ÙŠØ§Ø¯Ø© Ø±Ù‚Ù… Ø¥ØµØ¯Ø§Ø± Ø§Ù„ØªÙˆÙƒÙ†;

    LogoutAudit::create([
        'user_id'      => $user->id,
        'action'       => 'password_change_logout',
        'device_count' => $deviceCount,
        'ip_address'   => request()->ip(),
        'user_agent'   => request()->userAgent(),
    ]);

    Log::warning('ØªÙ… ØªØºÙŠÙŠØ± ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ± ÙˆØ¥Ø¨Ø·Ø§Ù„ Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø¬Ù„Ø³Ø§Øª', [
        'user_id' => $user->id,
        'devices' => $deviceCount,
    ]);
}
```

## 4. ØªØ³Ø¬ÙŠÙ„ IP Ùˆ Agent Ù„ÙƒÙ„ Ø­Ø¯Ø« Ø®Ø±ÙˆØ¬

```php
// ÙÙŠ LogoutService
public function logLogoutEvent(User $user, string $action): LogoutAudit
{
    $request = request();

    return LogoutAudit::create([
        'user_id'    => $user->id,
        'action'     => $action,
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'fingerprint' => sha1(
            $request->userAgent() . '|' . $request->ip()
        ),
        'logged_out_at' => now(),
        'metadata'   => [
            'method'      => $request->method(),
            'endpoint'    => $request->path(),
            'device'      => $this->parseDevice($request->userAgent()),
            'browser'     => $this->parseBrowser($request->userAgent()),
            'os'          => $this->parseOs($request->userAgent()),
            'accept_language' => $request->header('Accept-Language'),
        ],
    ]);
}

private function parseDevice(string $ua): string
{
    if (preg_match('/Android/i', $ua)) return 'Android';
    if (preg_match('/iPhone|iPad/i', $ua)) return 'iOS';
    if (preg_match('/Windows/i', $ua)) return 'Windows';
    if (preg_match('/Mac/i', $ua)) return 'macOS';
    if (preg_match('/Linux/i', $ua)) return 'Linux';
    return 'ØºÙŠØ± Ù…Ø¹Ø±ÙˆÙ';
}
```

## 5. Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ØªØ­Ù‚Ù‚ Ø§Ù„Ø£Ù…Ù†ÙŠ Ø§Ù„ÙƒØ§Ù…Ù„Ø©

| # | Ø§Ù„Ø¨Ù†Ø¯ | Ø§Ù„Ø­Ø§Ù„Ø© | Ø§Ù„ØªÙØµÙŠÙ„ |
|---|-------|--------|---------|
| 1 | Ø­Ø°Ù Ø§Ù„ØªÙˆÙƒÙ† Ø§Ù„Ø­Ø§Ù„ÙŠ | âœ… | ÙŠØªÙ… Ø­Ø°Ù Ø§Ù„Ø³Ø¬Ù„ Ù…Ù† jwt_blacklist |
| 2 | Ø¹Ø¯Ù… Ù‚Ø¨ÙˆÙ„ Ø§Ù„ØªÙˆÙƒÙ† Ø§Ù„Ù…Ù„ØºÙŠ | âœ… | JWT ÙŠØªØ­Ù‚Ù‚ Ù…Ù† ÙˆØ¬ÙˆØ¯ Ø§Ù„ØªÙˆÙƒÙ† ÙÙŠ DB |
| 3 | Ø¥Ø¨Ø·Ø§Ù„ Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø¬Ù„Ø³Ø§Øª (Ø§Ø®ØªÙŠØ§Ø±ÙŠ) | âœ… | logoutAll() â€” Ø­Ø°Ù Ø¬Ù…ÙŠØ¹ tokens |
| 4 | Logout Ù…Ù† Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø£Ø¬Ù‡Ø²Ø© | âœ… | Ù…Ø¹ Ø¥Ø¨Ø·Ø§Ù„ FCM tokens |
| 5 | HTTPS (Ù„Ù„Ø¥Ù†ØªØ§Ø¬) | â³ | ÙŠÙ…Ù†Ø¹ Ø§Ø¹ØªØ±Ø§Ø¶ Ø§Ù„ØªÙˆÙƒÙ† |
| 6 | ØªØ³Ø¬ÙŠÙ„ Audit Ù„ÙƒÙ„ Ø®Ø±ÙˆØ¬ | âœ… | Ù†Ù…ÙˆØ°Ø¬ LogoutAudit Ù…Ø¹ IP Ùˆ Agent |
| 7 | Ø¥Ø´Ø¹Ø§Ø± Ø§Ù„Ø¬Ù‡Ø§Ø² Ø§Ù„Ø¬Ø¯ÙŠØ¯ | âœ… | FCM + Email Ø¹Ù†Ø¯ Ø¬Ù‡Ø§Ø² ØºÙŠØ± Ù…Ø¹Ø±ÙˆÙ |
| 8 | Ø¥Ø¨Ø·Ø§Ù„ Ø§Ù„ØªÙˆÙƒÙ†Ø§Øª Ø¹Ù†Ø¯ ØªØºÙŠÙŠØ± ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ± | âœ… | Ø¥Ø¬Ø¨Ø§Ø± Ø¹Ù„Ù‰ Ø¥Ø¹Ø§Ø¯Ø© ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø¯Ø®ÙˆÙ„ |
| 9 | Ù…Ù†Ø¹ Ø§Ø®ØªØ·Ø§Ù Ø§Ù„Ø¬Ù„Ø³Ø© Ø¹Ø¨Ø± Clear-Site-Data | âœ… | Ù‡ÙŠØ¯Ø±Ø² Ø£Ù…Ù†ÙŠØ© Ù…Ø¶Ù…Ù†Ø© |
| 10 | ØªØ¬Ø§Ù‡Ù„ Ø§Ù„ØªÙˆÙƒÙ† Ø§Ù„Ù…Ù†ØªÙ‡ÙŠ Ø¨ØµÙ…Øª | âœ… | Ø¨Ø¯ÙˆÙ† Ø±Ø³Ø§Ù„Ø© Ø®Ø·Ø£ Ù„Ù„Ù…Ù‡Ø§Ø¬Ù… |

## 6. Ø§Ù„Ø­Ø§Ù„Ø§Øª Ø§Ù„Ø·Ø±ÙÙŠØ© (Edge Cases)

| Ø§Ù„Ù…Ø´ÙƒÙ„Ø© | Ø§Ù„Ù…Ø¹Ø§Ù„Ø¬Ø© |
|---------|----------|
| Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø£Ø±Ø³Ù„ Ø·Ù„Ø¨ Ø®Ø±ÙˆØ¬ Ø¨Ø¯ÙˆÙ† ØªÙˆÙƒÙ† | Ø±Ø³Ø§Ù„Ø© 401 Ø¨Ø¯ÙˆÙ† ØªÙØ§ØµÙŠÙ„ Ø¹Ù† Ø³Ø¨Ø¨ Ø§Ù„ÙØ´Ù„ |
| Ø§Ù„ØªÙˆÙƒÙ† Ù…Ø­Ø°ÙˆÙ Ù…Ø³Ø¨Ù‚Ø§Ù‹ (ÙƒØ±Ø± Ø§Ù„Ø·Ù„Ø¨) | Ø§Ø³ØªØ¬Ø§Ø¨Ø© 200 Ù…Ø¹ ØªØ¬Ø§Ù‡Ù„ â€” Idempotent |
| Ø®Ø±ÙˆØ¬ Ù…Ù† Ø¬Ù‡Ø§Ø² Ù…Ø³Ø±ÙˆÙ‚ Ø«Ù… ØªØºÙŠÙŠØ± ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ± | Ø¥Ø¨Ø·Ø§Ù„ Ø¬Ù…ÙŠØ¹ Ø§Ù„ØªÙˆÙƒÙ†Ø§Øª â€” Ø§Ù„Ø¬Ù‡Ø§Ø² Ø§Ù„Ù…Ø³Ø±ÙˆÙ‚ ÙŠÙÙ‚Ø¯ Ø§Ù„ÙˆØµÙˆÙ„ |
| Ù…Ø³ØªØ®Ø¯Ù… Ù…Ø¹ 50+ Ø¬Ù‡Ø§Ø² Ù†Ø´Ø· | logoutAll ÙŠØ­Ø°ÙÙ‡Ø§ Ø¯ÙØ¹Ø© ÙˆØ§Ø­Ø¯Ø© Ù…Ø¹ ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø¹Ø¯Ø¯ |
| Ù…Ø­Ø§ÙˆÙ„Ø© Ø§Ø³ØªØ®Ø¯Ø§Ù… API Ø¨Ø¹Ø¯ logout Ù…Ø¨Ø§Ø´Ø±Ø© | 401 â€” Ø§Ù„ØªØ­Ù‚Ù‚ ÙÙŠ Auth Guard |
| Ù…Ù‡Ø§Ø¬Ù… ÙŠØ­Ø§ÙˆÙ„ Ø¥Ø¹Ø§Ø¯Ø© Ø§Ø³ØªØ®Ø¯Ø§Ù… token Ù‚Ø¯ÙŠÙ… | JWT ÙŠØ±ÙØ¶ â€” Ø§Ù„ØªÙˆÙ‚ÙŠØ¹ ØºÙŠØ± ØµØ­ÙŠØ­ Ø£Ùˆ Ø§Ù„ØªÙˆÙƒÙ† Ù…Ù†ØªÙ‡ÙŠ |
| ØªØ³Ø¬ÙŠÙ„ Ø®Ø±ÙˆØ¬ Ù…ØªØ²Ø§Ù…Ù† Ù…Ù† Ø¬Ù‡Ø§Ø²ÙŠÙ† | Ø£ÙˆÙ„ Ø·Ù„Ø¨ ÙŠÙ†Ø¬Ø­ØŒ Ø§Ù„Ø«Ø§Ù†ÙŠ ÙŠØªØ¬Ø§Ù‡Ù„ â€” ÙƒÙ„ Ø¹Ù…Ù„ÙŠØ© Ø°Ø±ÙŠØ© |

## 7. Ø§Ù„ØªÙˆØµÙŠØ§Øª Ù„Ù„Ø¥Ù†ØªØ§Ø¬

```php
// config/jwt.php â€” ØªÙˆØµÙŠØ§Øª
return [
    'stateful' => explode(',', env('JWT_TTL')),

    'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],

    'ttl' => 1440, // 24 Ø³Ø§Ø¹Ø© Ø¨Ø§Ù„Ø¯Ù‚Ø§Ø¦Ù‚

    'token_prefix' => env('JWT_SECRET', ''),

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ]
    ],
];
```
