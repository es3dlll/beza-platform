# 20 - Ø£Ù…Ø§Ù† Ø§Ù„Ø¹Ù…Ù„ÙŠØ© Ø®Ø·ÙˆØ© Ø¨Ø®Ø·ÙˆØ© (Security Audit)

## 1. Race Condition (TOCTOU)

```php
// âŒ Ø®Ø·Ø£
$deal = Deal::find($id);
if ($deal->current_amount + $amount <= $deal->target_amount) {
    $deal->increment('current_amount', $amount);
}

// âœ… ØµØ­ÙŠØ­: Ø´Ø±Ø· ÙÙŠ Ù†ÙØ³ UPDATE
DB::update('UPDATE deals SET current_amount = current_amount + ?
            WHERE id = ? AND current_amount + ? <= target_amount',
            [$amount, $id, $amount]);
```

## 2. IDOR

```php
// âœ… Ø§Ù„Ù…Ø³ØªØ«Ù…Ø± Ù‡Ùˆ user Ø§Ù„Ù…ØµØ§Ø¯Ù‚ ÙÙ‚Ø·
$user = $request->user(); // Ù…Ù† JWT

// Ù„Ø§ ÙŠÙ…ÙƒÙ† ØªØ²ÙˆÙŠØ± investor_id
```

## 3. SQL Injection

âœ… Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø§Ø³ØªØ¹Ù„Ø§Ù…Ø§Øª ØªØ³ØªØ®Ø¯Ù… Parameter Binding

## 4. Rate Limiting

```php
// 20 Ø§Ø³ØªØ«Ù…Ø§Ø± ÙÙŠ Ø§Ù„Ø¯Ù‚ÙŠÙ‚Ø©
Route::middleware('throttle:20,1')->post('/deals/{deal}/invest', ...);
```

## 5. Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ØªØ­Ù‚Ù‚

| # | Ø§Ù„Ø¨Ù†Ø¯ | Ø§Ù„Ø­Ø§Ù„Ø© |
|---|-------|--------|
| 1 | Atomic DB update (Ù…Ù†Ø¹ over-invest) | âœ… |
| 2 | Parameterized queries | âœ… |
| 3 | Authentication | âœ… JWT |
| 4 | Authorization | âœ… Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… ÙÙ‚Ø· |
| 5 | Rate limiting | âœ… |
| 6 | Input validation | âœ… |
| 7 | Race condition protection | âœ… FOR UPDATE |
| 8 | Audit logging | âœ… |
