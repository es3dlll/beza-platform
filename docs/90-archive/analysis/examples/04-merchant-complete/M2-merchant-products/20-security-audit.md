# 20 - ØªØ¯Ù‚ÙŠÙ‚ Ø§Ù„Ø£Ù…Ø§Ù† (Security Audit) - Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„ØªØ§Ø¬Ø± (Merchant Products)

## Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„Ù…Ù„ÙƒÙŠØ© (Ownership Verification)
```php
// ÙƒÙ„ Ù…Ù†ØªØ¬ ÙŠØªØ­Ù‚Ù‚ Ù…Ù† merchant_id ÙŠØªØ¨Ø¹ Ù„Ù„ØªØ§Ø¬Ø± Ø§Ù„Ù…ØµØ§Ø¯Ù‚
$product = MerchantProduct::where('merchant_id', $merchantId)->findOrFail($id);
```

Ù‡Ø°Ø§ ÙŠÙ…Ù†Ø¹ Ø£ÙŠ ØªØ§Ø¬Ø± Ù…Ù† Ø§Ù„ÙˆØµÙˆÙ„ Ø¥Ù„Ù‰ Ù…Ù†ØªØ¬Ø§Øª ØªØ§Ø¬Ø± Ø¢Ø®Ø±. ÙƒÙ„ Ø·Ù„Ø¨ ÙŠØªØ¶Ù…Ù† middleware ÙŠØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„Ù…ØµØ§Ø¯Ù‚Ø© Ø£ÙˆÙ„Ø§Ù‹.

## Ø­Ù…Ø§ÙŠØ© Ø±ÙØ¹ Ø§Ù„ØµÙˆØ± (Image Upload Protection)
```php
'mimes:jpg,jpeg,png,webp'  // Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„ØµÙŠØºØ©
'max:2048'                  // Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„Ø­Ø¬Ù… (2MB)
```

## ÙƒØ§Ù…Ù„ Ø¯Ø§Ù„Ø© Ø§Ù„Ø­Ø°Ù Ù…Ø¹ Ø§Ù„ØªØ­Ù‚Ù‚ Ø§Ù„Ø£Ù…Ù†ÙŠ
```php
public function destroy(Request $request, $id): JsonResponse
{
    $merchant = $request->user()->merchant;

    // Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„Ù…Ù„ÙƒÙŠØ© - Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø­Ø°Ù Ù…Ù†ØªØ¬ Ù„Ø§ ÙŠØªØ¨Ø¹ Ù„Ù„ØªØ§Ø¬Ø±
    $product = MerchantProduct::where('merchant_id', $merchant->id)
        ->findOrFail($id);

    // Ø­Ø°Ù Ø§Ù„ØµÙˆØ± Ù…Ù† Ø§Ù„ØªØ®Ø²ÙŠÙ†
    foreach ($product->images as $image) {
        Storage::disk('public')->delete($image->image_path);
    }

    // Ø­Ø°Ù Ø§Ù„Ù…Ù†ØªØ¬
    $product->delete();

    return response()->json([
        'success' => true,
        'message' => 'ØªÙ… Ø­Ø°Ù Ø§Ù„Ù…Ù†ØªØ¬'
    ]);
}
```

## Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ØªØ­Ù‚Ù‚ Ø§Ù„Ø£Ù…Ù†ÙŠØ© (Security Checklist)
| # | Ø§Ù„Ø¨Ù†Ø¯ | Ø§Ù„Ø­Ø§Ù„Ø© | Ø´Ø±Ø­ |
|---|-------|--------|------|
| 1 | Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„Ù…Ù„ÙƒÙŠØ© | âœ… | ÙƒÙ„ Ø·Ù„Ø¨ ÙŠØªØ­Ù‚Ù‚ Ù…Ù† merchant_id |
| 2 | Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ù†ÙˆØ¹ Ø§Ù„Ù…Ù„ÙØ§Øª | âœ… | mimes:jpg,jpeg,png,webp |
| 3 | ØªØ­Ø¯ÙŠØ¯ Ø­Ø¬Ù… Ø§Ù„Ù…Ù„ÙØ§Øª | âœ… | max 2MB Ù„ÙƒÙ„ ØµÙˆØ±Ø© |
| 4 | Mass assignment | âœ… | fillable ÙŠØ­Ù…ÙŠ Ø§Ù„Ø­Ù‚ÙˆÙ„ |
| 5 | Authentication | âœ… | JWT + auth middleware |
| 6 | SQL injection | âœ… | Eloquent ORM + parameterized queries |
| 7 | Rate Limiting (API) | âœ… | throttle:60,1 middleware |
| 8 | Input validation | âœ… | FormRequest Ù…Ø¹ Ù‚ÙˆØ§Ø¹Ø¯ ØªØ­Ù‚Ù‚ ÙƒØ§Ù…Ù„Ø© |

## ØªÙˆØµÙŠØ§Øª Ø¥Ø¶Ø§ÙÙŠØ©
- Ø§Ø³ØªØ®Ø¯Ø§Ù… Laravel Policies Ù„Ù…Ø²ÙŠØ¯ Ù…Ù† Ø§Ù„ØªØ­ÙƒÙ… ÙÙŠ Ø§Ù„ØµÙ„Ø§Ø­ÙŠØ§Øª Ø¹Ù„Ù‰ Ù…Ø³ØªÙˆÙ‰ Ø§Ù„Ù€ Model
- ØªÙØ¹ÙŠÙ„ HTTPS ÙÙŠ Ø§Ù„Ø¥Ù†ØªØ§Ø¬ Ù„ØªØ´ÙÙŠØ±æ•°æ®ä¼ è¾“
- ØªØ´ÙÙŠØ± Ø§Ù„ØµÙˆØ± Ø§Ù„Ù…Ø®Ø²Ù†Ø© ÙÙŠ Ø­Ø§Ù„ ÙƒØ§Ù†Øª Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø­Ø³Ø§Ø³Ø©
- Ø¥Ø¶Ø§ÙØ© logging Ù„Ø¬Ù…ÙŠØ¹ Ø¹Ù…Ù„ÙŠØ§Øª Ø§Ù„Ø­Ø°Ù ÙˆØ§Ù„ØªØ¹Ø¯ÙŠÙ„ Ù„Ù„ØªØ¯Ù‚ÙŠÙ‚
- Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø®Ø§Ø¯Ù… Ù…Ù„ÙØ§Øª Ù…Ù†ÙØµÙ„ (S3/Cloud) Ù„ØªØ®Ø²ÙŠÙ† Ø§Ù„ØµÙˆØ± ÙÙŠ Ø§Ù„Ø¥Ù†ØªØ§Ø¬

```php
// Ù…Ø«Ø§Ù„ Ø¹Ù„Ù‰ Laravel Policy Ù„Ù„Ù…Ù†ØªØ¬
public function update(User $user, MerchantProduct $product): bool
{
    return $user->merchant->id === $product->merchant_id;
}
```

Ù‡Ø°Ù‡ Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª ØªØ¶Ù…Ù† Ø­Ù…Ø§ÙŠØ© ÙƒØ§Ù…Ù„Ø© Ù„Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„ØªØ§Ø¬Ø± Ù…Ù† Ø§Ù„ÙˆØµÙˆÙ„ ØºÙŠØ± Ø§Ù„Ù…ØµØ±Ø­ Ø¨Ù‡ ÙˆÙ…Ù† Ø§Ù„Ù‡Ø¬Ù…Ø§Øª Ø§Ù„Ø´Ø§Ø¦Ø¹Ø©.
