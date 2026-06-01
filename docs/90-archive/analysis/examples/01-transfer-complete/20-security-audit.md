# 20 - Ø£Ù…Ø§Ù† Ø§Ù„Ø¹Ù…Ù„ÙŠØ© Ø®Ø·ÙˆØ© Ø¨Ø®Ø·ÙˆØ© (Security Audit)

## 1. PIN â€” Ø§Ù„ØªØ®Ø²ÙŠÙ† ÙˆØ§Ù„ØªØ­Ù‚Ù‚

```php
// âŒ Ø®Ø·Ø£: ØªØ®Ø²ÙŠÙ† PIN ÙƒÙ†Øµ Ø¹Ø§Ø¯ÙŠ
$user->pin_code = '1234';

// âœ… ØµØ­ÙŠØ­: ØªØ®Ø²ÙŠÙ† Ù…Ø´ÙØ±
$user->pin_code = Hash::make($request->pin);

// Ø§Ù„ØªØ­Ù‚Ù‚
if (!Hash::check($pin, $user->pin_code)) {
    throw new InvalidPinException();
}
```

| Ø§Ù„Ù…Ù…Ø§Ø±Ø³Ø© | Ø§Ù„Ø­Ø§Ù„Ø© |
|----------|--------|
| Ø§Ø³ØªØ®Ø¯Ø§Ù… Bcrypt/Argon2 | âœ… `Hash::make()` ÙŠØ³ØªØ®Ø¯Ù… Bcrypt Ø¨Ù‚ÙˆØ© 10 |
| ØªØ®Ø²ÙŠÙ† PIN ÙÙŠ Ø¬Ø¯ÙˆÙ„ Ù…Ù†ÙØµÙ„ | âŒ Ù„Ø§ Ø­Ø§Ø¬Ø© â€” users.pin_code Ø¢Ù…Ù† |
| Salt ØªÙ„Ù‚Ø§Ø¦ÙŠ | âœ… Laravel ÙŠØ¶ÙŠÙ Salt Ù„ÙƒÙ„ Hash |
| Ø¹Ø¯Ù… Ø¥Ø±Ø¬Ø§Ø¹ PIN ÙÙŠ Ø£ÙŠ API | âœ… `$hidden = ['pin_code']` |

## 2. IDOR (Insecure Direct Object Reference)

```php
// âŒ Ø®Ø·Ø£: Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… ÙŠØ³ØªØ·ÙŠØ¹ ØªØ­Ø¯ÙŠØ¯ Ø£ÙŠ user_id
$toUser = User::find($request->input('user_id'));

// âœ… ØµØ­ÙŠØ­: Ø§Ù„Ø¨Ø­Ø« Ø¨Ø±Ù‚Ù… Ø§Ù„Ù‡Ø§ØªÙ ÙÙ‚Ø· (Ù„Ø§ ÙŠÙ…ÙƒÙ† ØªØ®Ù…ÙŠÙ†Ù‡ Ø¨Ø³Ù‡ÙˆÙ„Ø©)
$toUser = User::where('phone', $request->input('to_phone'))->first();

// âœ… Ø£ÙŠØ¶Ø§Ù‹: Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø£Ù† Ø§Ù„Ù…Ø±Ø³Ù„ Ù‡Ùˆ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø§Ù„Ù…ØµØ§Ø¯Ù‚ ÙÙ‚Ø·
$fromUser = $request->user(); // Ù…Ù† Auth Token â€” Ù„Ø§ ÙŠÙ…ÙƒÙ† ØªØ²ÙˆÙŠØ±Ù‡
```

## 3. SQL Injection

```php
// âŒ Ø®Ø·Ø£: Ø§Ø³ØªØ®Ø¯Ø§Ù… interpolation
DB::statement("UPDATE wallets SET balance = balance - {$amount} WHERE id = {$id}");

// âœ… ØµØ­ÙŠØ­: Parameter binding
DB::update('UPDATE wallets SET balance = balance - ? WHERE id = ?', [$amount, $walletId]);

// âœ… ØµØ­ÙŠØ­: Eloquent
Wallet::where('id', $walletId)->lockForUpdate()->first();
```

## 4. Rate Limiting

```php
// routes/api.php
Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
    Route::post('/transfer', [TransferController::class, 'transfer']);
});
```

| Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯ | Ø§Ù„Ù‚ÙŠÙ…Ø© | Ø§Ù„Ø³Ø¨Ø¨ |
|---------|--------|-------|
| max_attempts | 30 | Ù…Ù†Ø¹ Brute Force Ø¹Ù„Ù‰ PIN |
| decay_minutes | 1 | 30 Ù…Ø­Ø§ÙˆÙ„Ø© ÙÙŠ Ø§Ù„Ø¯Ù‚ÙŠÙ‚Ø© ÙƒØ§ÙÙŠØ© Ù„Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø¹Ø§Ø¯ÙŠ |
| ØªØ¬Ø§ÙˆØ² | 429 Too Many Requests | |

## 5. CSRF Protection

```php
// âŒ Ù„Ø§ Ø­Ø§Ø¬Ø© â€” API ÙŠØ³ØªØ®Ø¯Ù… Bearer Token (JWT)
// API stateless Ù„Ø§ ÙŠØ­ØªØ§Ø¬ CSRF
// routes/api.php Ù„Ø§ ÙŠÙ…Ø± Ø¹Ø¨Ø± CSRF middleware
```

## 6. XSS Prevention

```javascript
// âœ… React: dangerouslySetInnerHTML ØºÙŠØ± Ù…Ø³ØªØ®Ø¯Ù… â€” Ø§ÙØªØ±Ø§Ø¶ÙŠØ§Ù‹ Ø¢Ù…Ù†
// âœ… Flutter: Text widget ÙŠÙ‡Ø±Ø¨ HTML ØªÙ„Ù‚Ø§Ø¦ÙŠØ§Ù‹
```

```php
// âœ… Laravel: {{ }} ÙÙŠ Blade ØªÙ‡Ø±Ø¨ HTML
// âœ… API: JSON Ù„Ø§ ÙŠÙ†ÙØ° HTML
```

## 7. Mass Assignment

```php
// âŒ Ø®Ø·Ø£: Ø§Ù„Ø³Ù…Ø§Ø­ Ø¨ÙƒÙ„ Ø§Ù„Ø­Ù‚ÙˆÙ„
Transaction::create($request->all());

// âœ… ØµØ­ÙŠØ­: ØªØ­Ø¯ÙŠØ¯ Ø§Ù„Ø­Ù‚ÙˆÙ„ Ø§Ù„Ù…Ø³Ù…ÙˆØ­Ø©
Transaction::create([
    'from_wallet_id'  => $fromWallet->id,
    'to_wallet_id'    => $toWallet->id,
    'amount'          => $amount,
    'amount_in_usd'   => $amountInUsd,
    'type'            => 'transfer',
    'status'          => 'completed',
    'reference_number'=> Transaction::generateReferenceNumber(),
    'fee'             => 0.00,
    'completed_at'    => now(),
]);
```

## 8. Race Condition (TOCTOU)

```php
// âŒ Ø®Ø·Ø£: Time-of-check to Time-of-use
$balance = $wallet->balance;            // T1: Ù‚Ø±Ø£
// ...                                   // T2: Ù…Ø¹Ø§Ù…Ù„Ø© Ø£Ø®Ø±Ù‰ ØªØºÙŠØ± Ø§Ù„Ø±ØµÙŠØ¯
if ($balance >= $amount) {               // T3: ØªØ­Ù‚Ù‚ â€” Ù‚Ø¯ÙŠÙ…!
    $wallet->decrement('balance', $amount); // T4: Ø®ØµÙ… â€” Ø®Ø·Ø£!
}

// âœ… ØµØ­ÙŠØ­: WHERE balance >= amount ÙÙŠ Ù†ÙØ³ Ø§Ø³ØªØ¹Ù„Ø§Ù… Ø§Ù„ØªØ­Ø¯ÙŠØ«
DB::update(
    'UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ?',
    [$amount, $walletId, $amount]
);
// Ø¥Ø°Ø§ ÙƒØ§Ù†Øª 0 rows Ù…ØªØ£Ø«Ø±Ø© â†’ Ø§Ù„Ø±ØµÙŠØ¯ ØºÙŠØ± ÙƒØ§ÙÙ
```

## 9. Authentication

```php
// âœ… JWT Token
// ÙƒÙ„ Ø·Ù„Ø¨ ÙŠØªØ·Ù„Ø¨ Bearer Token ØµØ­ÙŠØ­
// Token ÙŠØªÙ… ØªÙˆÙ‚ÙŠØ¹Ù‡ Ø¹Ù†Ø¯ ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø¯Ø®ÙˆÙ„ ÙˆØ§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„ØªÙˆÙ‚ÙŠØ¹ ÙˆØ§Ù„ØªØ§Ø±ÙŠØ® Ù„ÙƒÙ„ Ø·Ù„Ø¨

// Middleware: auth:api
Route::middleware('auth:api')->group(function () {
    Route::post('/transfer', ...);
});
```

## 10. Authorization (Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø¹Ø§Ø¯ÙŠ vs Admin)

```php
// TransferService ÙŠØªØ­Ù‚Ù‚ Ø¶Ù…Ù†ÙŠØ§Ù‹:
// - Ø§Ù„Ù…Ø±Ø³Ù„ = Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø§Ù„Ù…ØµØ§Ø¯Ù‚
// - Ù„Ø§ ÙŠÙ…ÙƒÙ† Ù„Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø¹Ø§Ø¯ÙŠ ØªØ­ÙˆÙŠÙ„ Ù…Ù† Ù…Ø­ÙØ¸Ø© Ø´Ø®Øµ Ø¢Ø®Ø±
```

## 11. Logging & Audit

```php
// ØªØ³Ø¬ÙŠÙ„ ÙƒÙ„ Ù…Ø­Ø§ÙˆÙ„Ø© ØªØ­ÙˆÙŠÙ„
Log::info('Transfer attempt', [
    'from_user' => $fromUser->id,
    'to_user'   => $toUser->id,
    'amount'    => $amount,
    'currency'  => $currency,
    'ip'        => request()->ip(),
    'user_agent'=> request()->userAgent(),
]);

// ØªØ³Ø¬ÙŠÙ„ Ø­Ø§Ù„Ø§Øª Ø§Ù„ÙØ´Ù„
Log::warning('ÙØ´Ù„ ØªØ­ÙˆÙŠÙ„ â€” PIN ØºÙŠØ± ØµØ­ÙŠØ­', [
    'user_id' => $fromUser->id,
    'ip'      => request()->ip(),
]);
```

## 12. Input Validation Defense In Depth

| Ø§Ù„Ù…Ø³ØªÙˆÙ‰ | Ø§Ù„ØªÙ‚Ù†ÙŠØ© | ØªØ­Ù…ÙŠ Ù…Ù† |
|----------|---------|---------|
| 1. Client | Form validation (JS/Dart) | UX â€” ØªØ­Ø³ÙŠÙ† ØªØ¬Ø±Ø¨Ø© Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… |
| 2. Network | HTTPS | Man-in-the-middle |
| 3. Laravel | Form Request | Injection, Wrong types |
| 4. Service | Business rules | Invalid operations |
| 5. DB | Constraints + FK | Data corruption |
| 6. MySQL | Column types + CHECK | Overflow, wrong data |

## 13. HTTPS (Ù„Ù„Ø¥Ù†ØªØ§Ø¬)

```nginx
# Nginx â€” Ø§Ù„Ø¥Ù†ØªØ§Ø¬ ÙÙ‚Ø·
server {
    listen 443 ssl;
    ssl_certificate /etc/ssl/certs/beza.crt;
    ssl_certificate_key /etc/ssl/private/beza.key;

    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}
```

## 14. Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ØªØ­Ù‚Ù‚ Ø§Ù„Ø£Ù…Ù†ÙŠ (Security Checklist)

| # | Ø§Ù„Ø¨Ù†Ø¯ | Ø§Ù„Ø­Ø§Ù„Ø© |
|---|-------|--------|
| 1 | PIN Ù…Ø´ÙØ± (Bcrypt) | âœ… |
| 2 | Ø¬Ù…ÙŠØ¹ Ø§Ù„Ù…Ø¯Ø®Ù„Ø§Øª Ù…ÙˆØ«Ù‚Ø© | âœ… |
| 3 | Parameterized SQL | âœ… |
| 4 | Rate Limiting (30/Ø¯Ù‚ÙŠÙ‚Ø©) | âœ… |
| 5 | Authentication (JWT) | âœ… |
| 6 | IDOR Ù…Ø­Ù…ÙŠ (Ø¨Ø­Ø« Ø¨Ø§Ù„Ù‡Ø§ØªÙ) | âœ… |
| 7 | Atomic DB (FOR UPDATE) | âœ… |
| 8 | No sensitive data in response | âœ… |
| 9 | Audit logging | âœ… |
| 10 | Mass assignment protection | âœ… |
| 11 | HTTPS (Ù„Ù„Ø¥Ù†ØªØ§Ø¬) | â³ (ÙŠØªØ·Ù„Ø¨ Ø´Ù‡Ø§Ø¯Ø© SSL) |
| 12 | 2FA Ù„Ù„Ù…Ø¨Ø§Ù„Øº > 1000 USD | âœ… |
| 13 | PIN brute-force lockout (Ù‚ÙÙ„ Ø¨Ø¹Ø¯ 5 Ù…Ø­Ø§ÙˆÙ„Ø§Øª) | âœ… |
| 14 | Device fingerprinting | ðŸ“‹ Ø§Ù„Ø¥ØµØ¯Ø§Ø± 2.0 |
| 15 | Webhook signature | ðŸ“‹ Ø§Ù„Ø¥ØµØ¯Ø§Ø± 2.0 |
