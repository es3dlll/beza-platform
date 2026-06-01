# 09 - Ø³ÙŠØ±ÙØ³ Ù„ÙŠØ± Ø§Ù„Ø¹Ù…Ù„ÙŠØ© â€” AuthService (Login)

```php
<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Exceptions\AccountLockedException;
use App\Exceptions\AccountSuspendedException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * ØªØ³Ø¬ÙŠÙ„ Ø¯Ø®ÙˆÙ„ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù…
     *
     * @throws InvalidCredentialsException
     * @throws AccountSuspendedException
     * @throws AccountLockedException
     */
    public function login(
        string  $phone,
        string  $password,
        ?string $deviceId = null,
        ?string $ip = null,
    ): array {

        // â”€â”€â”€ 1. Ø§Ù„Ø¨Ø­Ø« Ø¹Ù† Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… â”€â”€â”€
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $this->logFailedAttempt($phone);
            throw new InvalidCredentialsException();
        }

        // â”€â”€â”€ 2. Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ù‚ÙÙ„ Ø§Ù„Ø­Ø³Ø§Ø¨ â”€â”€â”€
        if ($this->isAccountLocked($user)) {
            throw new AccountLockedException(
                $this->getLockRemainingMinutes($user)
            );
        }

        // â”€â”€â”€ 3. Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ± â”€â”€â”€
        if (!Hash::check($password, $user->password)) {
            $this->incrementFailedAttempts($user);
            $this->logFailedAttempt($phone);
            throw new InvalidCredentialsException();
        }

        // â”€â”€â”€ 4. Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø­Ø§Ù„Ø© Ø§Ù„Ø­Ø³Ø§Ø¨ â”€â”€â”€
        if ($user->isSuspended()) {
            throw new AccountSuspendedException();
        }

        if ($user->isBlocked()) {
            throw new AccountSuspendedException('Ø­Ø³Ø§Ø¨Ùƒ Ù…Ø­Ø¸ÙˆØ± Ù†Ù‡Ø§Ø¦ÙŠØ§Ù‹');
        }

        // â”€â”€â”€ 5. ØªØµÙÙŠØ© Ø§Ù„Ù…Ø­Ø§ÙˆÙ„Ø§Øª Ø§Ù„ÙØ§Ø´Ù„Ø© (Ù†Ø¬Ø§Ø­) â”€â”€â”€
        $this->clearFailedAttempts($user);

        // â”€â”€â”€ 6. ØªØ­Ø¯ÙŠØ« Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø¯Ø®ÙˆÙ„ â”€â”€â”€
        $user->updateLoginMetadata($ip ?? request()->ip(), $deviceId);

        // â”€â”€â”€ 7. ØªÙ†Ø¸ÙŠÙ Ø§Ù„ØªÙˆÙƒÙ†Ø§Øª Ø§Ù„Ù‚Ø¯ÙŠÙ…Ø© â”€â”€â”€
        $user->cleanupOldTokens();

        // â”€â”€â”€ 8. Ø¥Ù†Ø´Ø§Ø¡ ØªÙˆÙƒÙ† Ø¬Ø¯ÙŠØ¯ â”€â”€â”€
        $token = JWTAuth::fromUser($user);

        return [
            'user'  => $user->fresh(),
            'token' => $token,
        ];
    }

    // === Ø¥Ø¯Ø§Ø±Ø© Ø§Ù„Ù…Ø­Ø§ÙˆÙ„Ø§Øª Ø§Ù„ÙØ§Ø´Ù„Ø© ===

    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 15; // Ø¯Ù‚ÙŠÙ‚Ø©

    private function failedAttemptsKey(User $user): string
    {
        return 'login_attempts_' . $user->id;
    }

    private function isAccountLocked(User $user): bool
    {
        $key = $this->failedAttemptsKey($user);
        $data = Cache::get($key);

        return $data && $data['attempts'] >= self::MAX_ATTEMPTS;
    }

    private function getLockRemainingMinutes(User $user): int
    {
        $key = $this->failedAttemptsKey($user);
        $data = Cache::get($key);

        if (!$data) return 0;

        return max(0, self::LOCKOUT_DURATION - (now()->diffInMinutes($data['locked_at'])));
    }

    private function incrementFailedAttempts(User $user): void
    {
        $key = $this->failedAttemptsKey($user);
        $data = Cache::get($key, ['attempts' => 0, 'locked_at' => null]);

        $data['attempts']++;
        $data['locked_at'] = $data['attempts'] >= self::MAX_ATTEMPTS
            ? now()
            : ($data['locked_at'] ?? now());

        Cache::put($key, $data, now()->addMinutes(self::LOCKOUT_DURATION));
    }

    private function clearFailedAttempts(User $user): void
    {
        Cache::forget($this->failedAttemptsKey($user));
    }

    private function logFailedAttempt(string $phone): void
    {
        Log::warning('Ù…Ø­Ø§ÙˆÙ„Ø© ØªØ³Ø¬ÙŠÙ„ Ø¯Ø®ÙˆÙ„ ÙØ§Ø´Ù„Ø©', [
            'phone' => $phone,
            'ip'    => request()->ip(),
            'time'  => now(),
        ]);
    }
}
```

## ØªØ¯ÙÙ‚ AuthService::login()

```
1. Find user by phone
2. Check account lock (5 attempts?)
3. Hash::check(password)
4. Check status (suspended?)
5. Clear failed attempts
6. Update last_login_at, ip, device_id
7. Delete old tokens
8. Create new JWT token
9. Return [user, token]
```
