# 19 - حالات الحافة (Edge Cases)

## 1. Device Change

**Problem**: User gets a new phone and needs to transfer wallet enrollments.

**Solution**:
- New device generates a new key pair
- Old device tokens are automatically revoked on new enrollment
- User verifies via OTP before transferring enrollment
- DAN is regenerated for the new device

```php
public function transferToNewDevice(Card $card, string $oldDeviceId, string $newDeviceId): WalletEnrollment
{
    WalletEnrollment::where('card_id', $card->id)
        ->where('device_id', $oldDeviceId)
        ->where('status', 'active')
        ->update(['status' => 'revoked']);

    return $this->enroll($card, [
        'wallet_type' => 'apple_pay',
        'device_id' => $newDeviceId,
        'device_public_key' => $newPublicKey,
    ]);
}
```

## 2. Token Refresh (Expiry)

**Problem**: DAN tokens expire after 2 years or when card details change.

**Solution**:
- Check expiry 30 days before expiration
- Auto-refresh via cron job or on next wallet usage
- Notify user via push notification to re-authenticate

```php
// Artisan command: wallet:refresh-expiring-tokens
$expiring = WalletEnrollment::where('expires_at', '<', now()->addDays(30))
    ->where('status', 'active')
    ->get();

foreach ($expiring as $enrollment) {
    $enrollment->card->user->notify(new TokenExpiringNotification($enrollment));
}
```

## 3. Multiple Wallets on Same Card

**Problem**: User enrolls same card in both Apple Pay and Google Pay simultaneously.

**Solution**:
- Each wallet type creates a separate DAN
- Allow up to 5 active enrollments per card
- Enforce uniqueness using composite unique index `(card_id, wallet_type, device_id)`

## 4. Payment Network Downtime

**Problem**: Visa/Mastercard/Mada token service is unavailable during enrollment.

**Solution**:
- Queue enrollment and retry with backoff
- Cache DAN locally with pending status
- Fallback to in-person card usage (Chip/PIN)
- Implement circuit breaker for network calls

```php
public function enrollWithRetry(Card $card, array $data): WalletEnrollment
{
    try {
        return $this->enroll($card, $data);
    } catch (NetworkTimeoutException $e) {
        dispatch(new ProcessPendingEnrollment($card, $data))
            ->delay(now()->addMinutes(5));
        throw new EnrollmentQueuedException('سيتم إتمام الاشتراك خلال دقائق');
    }
}
```

## 5. Card Replacement (Reissue)

**Problem**: Card is reissued with new PAN, old DAN must be invalidated.

**Solution**:
- Detect card reissue via `updated_at` on cards table
- Notify payment networks to revoke old DANs
- User must re-enroll with new card
- Show pending re-enrollment prompt in app

## 6. Rooted/Jailbroken Devices

**Problem**: Security risk from compromised devices.

**Solution**:
- Validate device integrity via SDK attestation
- Block enrollment on rooted/jailbroken devices
- Flag suspicious enrollments for manual review

## Edge Case Summary

| # | Case | Solution | Level |
|---|------|----------|-------|
| 1 | Device change | OTP verification + DAN regeneration | Security |
| 2 | Token expiry | 30-day pre-expiry auto-refresh | Cron |
| 3 | Multiple wallets | Separate DAN per wallet type | Database |
| 4 | Network downtime | Queued retry + circuit breaker | Resilience |
| 5 | Card reissue | Old DAN revocation + re-enrollment prompt | Event |
| 6 | Rooted device | SDK attestation + block enrollment | Security |
