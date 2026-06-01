# 20 - Ø£Ù…Ø§Ù† Ø§Ù„Ø¹Ù…Ù„ÙŠØ© Ø®Ø·ÙˆØ© Ø¨Ø®Ø·ÙˆØ© (Security Audit)

## 1. Authentication

```php
// âœ… JWT Token Ù…Ø·Ù„ÙˆØ¨ Ù„ÙƒÙ„ Ø·Ù„Ø¨
Route::middleware('auth:api')->group(function () {
    Route::get('/wallet/balance', [BalanceController::class, 'index']);
});

// âŒ ÙŠÙ…ÙƒÙ† Ù„Ø£ÙŠ Ø´Ø®Øµ Ø±Ø¤ÙŠØ© Ø§Ù„Ø±ØµÙŠØ¯ Ø¨Ø¯ÙˆÙ† ØªÙˆØ«ÙŠÙ‚
```

## 2. IDOR (Insecure Direct Object Reference)

```php
// âœ… ØµØ­ÙŠØ­: Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… ÙŠØ±Ù‰ Ø±ØµÙŠØ¯Ù‡ ÙÙ‚Ø·
$user = $request->user(); // Ù…Ù† Auth Token â€” Ù„Ø§ ÙŠÙ…ÙƒÙ† ØªØ²ÙˆÙŠØ±Ù‡

// âŒ Ø®Ø·Ø£: Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… ÙŠØ³ØªØ·ÙŠØ¹ ØªØ­Ø¯ÙŠØ¯ user_id
$user = User::find($request->input('user_id'));
```

## 3. Rate Limiting

```php
// routes/api.php
Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
    Route::get('/wallet/balance', [BalanceController::class, 'index']);
});
```

| Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯ | Ø§Ù„Ù‚ÙŠÙ…Ø© | Ø§Ù„Ø³Ø¨Ø¨ |
|---------|--------|-------|
| max_attempts | 60 | ÙƒØ§ÙÙ Ù„ØªØ·Ø¨ÙŠÙ‚ ÙŠØ³ØªØ¹Ù„Ù… Ø§Ù„Ø±ØµÙŠØ¯ ÙƒÙ„ Ø«Ø§Ù†ÙŠØ© |
| decay_minutes | 1 | 60 Ø·Ù„Ø¨ ÙÙŠ Ø§Ù„Ø¯Ù‚ÙŠÙ‚Ø© Ø­Ø¯ Ù…Ø¹Ù‚ÙˆÙ„ |

## 4. SQL Injection

```php
// âœ… ØµØ­ÙŠØ­: Eloquent ÙŠØ­Ù…ÙŠ Ù…Ù† Injection
Wallet::where('user_id', $userId)->get();

// âŒ Ø®Ø·Ø£: Ø§Ø³ØªØ®Ø¯Ø§Ù… raw SQL
DB::select("SELECT * FROM wallets WHERE user_id = {$userId}");
```

## 5. Cache Poisoning

```php
// âŒ Ø®Ø·Ø£: ØªØ®Ø²ÙŠÙ† Ø¨ÙŠØ§Ù†Ø§Øª Ø®Ø§Ø·Ø¦Ø© ÙÙŠ Cache
Cache::put($cacheKey, $maliciousData, 3600);

// âœ… ØµØ­ÙŠØ­: Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ù‚Ø¨Ù„ Ø§Ù„ØªØ®Ø²ÙŠÙ†
$wallets = $this->walletService->getUserWallets($user->id);
if (empty($wallets)) {
    throw new WalletsNotFoundException();
}
Cache::put($cacheKey, $wallets, 30);
```

## 6. Mass Assignment

Ø¹Ø±Ø¶ Ø§Ù„Ø±ØµÙŠØ¯ Ù‡Ùˆ GET â€” Ù„Ø§ Mass Assignment Ù…Ù…ÙƒÙ†.

## 7. CSRF Protection

```php
// âœ… Ù„Ø§ Ø­Ø§Ø¬Ø© â€” API ÙŠØ³ØªØ®Ø¯Ù… Bearer Token (JWT)
// API stateless Ù„Ø§ ÙŠØ­ØªØ§Ø¬ CSRF
```

## 8. XSS Prevention

```javascript
// âœ… React: dangerouslySetInnerHTML ØºÙŠØ± Ù…Ø³ØªØ®Ø¯Ù…
// âœ… Flutter: Text widget ÙŠÙ‡Ø±Ø¨ HTML ØªÙ„Ù‚Ø§Ø¦ÙŠØ§Ù‹
```

## 9. Logging & Audit

```php
// ØªØ³Ø¬ÙŠÙ„ ÙƒÙ„ Ø·Ù„Ø¨ Ø±ØµÙŠØ¯ (Ø§Ø®ØªÙŠØ§Ø±ÙŠ â€” Ù„Ù„ØªØ¯Ù‚ÙŠÙ‚ Ø§Ù„Ø£Ù…Ù†ÙŠ)
Log::info('Balance requested', [
    'user_id' => $user->id,
    'ip'      => $request->ip(),
]);

// Ù„ÙƒÙ† ÙŠÙ…ÙƒÙ† Ø£Ù† ÙŠÙƒÙˆÙ† Ø«Ù‚ÙŠÙ„Ø§Ù‹ Ø¹Ù„Ù‰ Logs â€” ÙŠÙØ¶Ù„ ØªØ¹Ø·ÙŠÙ„Ù‡ Ø£Ùˆ Ø¬Ø¹Ù„Ù‡ DEBUG
```

## 10. Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø­Ø³Ø§Ø³Ø© ÙÙŠ Ø§Ù„Ø§Ø³ØªØ¬Ø§Ø¨Ø©

```php
// âœ… Ù„Ø§ ÙŠØªÙ… Ø¥Ø±Ø¬Ø§Ø¹ Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø­Ø³Ø§Ø³Ø©
// wallet_number: Ø¢Ù…Ù† (ÙŠØ³ØªØ®Ø¯Ù… Ù„Ù„ØªØ­ÙˆÙŠÙ„Ø§Øª)
// balance: ØºÙŠØ± Ø­Ø³Ø§Ø³ (Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… ÙŠØ±Ù‰ Ø±ØµÙŠØ¯Ù‡)

// âŒ Ù„Ø§ ÙŠØªÙ… Ø¥Ø±Ø¬Ø§Ø¹:
// - user_id
// - is_active
// - updated_at
```

## 11. Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ØªØ­Ù‚Ù‚ Ø§Ù„Ø£Ù…Ù†ÙŠ (Security Checklist)

| # | Ø§Ù„Ø¨Ù†Ø¯ | Ø§Ù„Ø­Ø§Ù„Ø© |
|---|-------|--------|
| 1 | JWT Authentication | âœ… |
| 2 | IDOR Ù…Ø­Ù…ÙŠ (user Ù…Ù† Token) | âœ… |
| 3 | Rate Limiting (60/Ø¯Ù‚ÙŠÙ‚Ø©) | âœ… |
| 4 | Parameterized SQL (Eloquent) | âœ… |
| 5 | Cache Sanitization | âœ… |
| 6 | No sensitive data in response | âœ… |
| 7 | HTTPS (Ù„Ù„Ø¥Ù†ØªØ§Ø¬) | â³ (ÙŠØªØ·Ù„Ø¨ Ø´Ù‡Ø§Ø¯Ø© SSL) |
| 8 | Audit logging | âœ… (DEBUG level) |
| 9 | CSRF Protection (ØºÙŠØ± Ù…Ø·Ù„ÙˆØ¨) | âœ… |
| 10 | XSS Prevention | âœ… (React/Flutter) |
