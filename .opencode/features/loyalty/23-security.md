# Loyalty Security

## Authentication & Authorization

### Points Redemption PIN
```php
public function verifyRedemptionPin(User $user, string $plainPin): bool
{
    // Uses same PIN as wallet transactions
    return $this->pinService->verify($user, $plainPin);
}

// Step-up: Redemption over 25,000 points requires biometric + PIN
public function authorizeLargeRedemption(User $user, int $points, Device $device): bool
{
    if ($points >= 25000) {
        return $this->stepUpAuth($user, 'biometric', $device);
    }
    return true;
}
```

### Merchant Campaign Access Control
```php
// Only verified merchants can create campaigns
public function authorizeCampaignAccess(User $user): void
{
    throw_unless(
        $user->isMerchant() && $user->merchant->isVerified(),
        new MerchantAccessDeniedException()
    );
}

// Campaign owner-only operations
public function authorizeCampaignOwner(MerchantCampaign $campaign, User $user): void
{
    throw_unless(
        $campaign->merchant_id === $user->merchant->id,
        new AuthorizationException()
    );
}
```

## Sensitive Operations
```
Operations requiring PIN verification:
  - Points redemption (any amount)
  - Campaign fund withdrawal
  - Tier override (admin only, requires 2FA)

Operations requiring step-up auth:
  - Redemption > 25,000 points (> 25,000 SYP value)
  - Campaign budget > 1,000,000 SYP
  - Refund redeemed points
```

## Fraud Prevention Rules
```
Rule L-1: Rapid Redemption
  - > 3 redemptions in 10 minutes
  - Action: Block redemptions for 1 hour, notify user

Rule L-2: Unusual Redemption Pattern
  - Redemption amount exactly equals balance (draining)
  - Action: Flag for manual review

Rule L-3: Campaign Abuse
  - Same user redeeming merchant campaign > 5×/day
  - Action: Limit to 5× per user per campaign per day

Rule L-4: Referral Fraud
  - Same IP creating multiple accounts for referral points
  - Action: Flag accounts, deny referral bonus, investigate

Rule L-5: Points Farming
  - Rapid small transactions cycling between two accounts
  - Action: Flag both accounts, manual review
```

## Data Protection
```
Sensitive Data:
  - Points balances: encrypted at rest (AES-256)
  - Coupon codes: SHA-256 hashed in URL tracking
  - Redemption history: access limited to user + support
  - Campaign budgets: visible only to merchant owner

Privacy:
  - Points earned/activity not shared between users
  - Merchant sees only aggregate campaign stats, not individual users
  - Tier status is public (user can choose to share)
```
