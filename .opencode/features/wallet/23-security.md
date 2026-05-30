# Wallet Security

## Authentication & Authorization

### PIN Security
```php
// PIN creation during wallet setup
public function createPin(User $user, string $plainPin): void
{
    // Requirements: 6 digits, cannot be sequential or repeated (123456, 111111)
    $this->validatePinStrength($plainPin);

    // Hash: bcrypt cost=12 with pepper
    $pepper = config('beza.security.pin_pepper');
    $hashed = Hash::make($plainPin . $pepper, ['rounds' => 12]);

    // Store: SHA-256 hashed in DB (one-way, never plaintext)
    UserPin::updateOrCreate(
        ['user_id' => $user->id],
        ['pin_hash' => hash('sha256', $hashed)]
    );
}

// PIN verification
public function verifyPin(User $user, string $plainPin): bool
{
    $stored = UserPin::where('user_id', $user->id)->firstOrFail();
    $pepper = config('beza.security.pin_pepper');
    return hash_equals(
        $stored->pin_hash,
        hash('sha256', Hash::make($plainPin . $pepper, ['rounds' => 12]))
    );
}
```

### Device Binding
```php
// Every transfer is bound to device + IP
public function authorizeTransfer(User $user, Device $device, string $ip): bool
{
    // Check 1: Device is known for this user
    if (!$device->isTrustedForUser($user)) {
        // Step-up: require biometric + SMS OTP
        return $this->stepUpAuth($user, 'sms_otp');
    }

    // Check 2: IP is not suspicious (VPN, Tor, blacklisted)
    if ($this->ipReputation->isSuspicious($ip)) {
        return $this->stepUpAuth($user, 'sms_otp');
    }

    // Check 3: Behavioral pattern matches (time, location, velocity)
    $risk = $this->behaviorAnalyzer->score($user, $device, $ip);
    if ($risk > 70) {
        return false; // Block
    }
    if ($risk > 40) {
        return $this->stepUpAuth($user, 'biometric');
    }

    return true;
}
```

## Sensitive Actions
```
Actions requiring step-up authentication:
  - Send money > 500,000 SYP
  - Cash-out > 500,000 SYP
  - Change PIN
  - Add new device
  - Change recovery phone
  - Wallet closure
  - KYC level change
  - Daily limit increase
```

## Fraud Prevention Rules
```
Rule 1: Velocity Check — No more than 5 transfers to the same recipient in 1 hour
Rule 2: Amount Check — No more than 3x average daily amount in single transfer
Rule 3: New Device — First transaction from new device limited to 50,000 SYP
Rule 4: Geographic Anomaly — Transfer from location >500km from last login requires step-up
Rule 5: Night Activity — 23:00-05:00 transactions above 100,000 flagged for review
Rule 6: Round Amounts — Transfers of exact rounded amounts (100,000, 500,000) checked against known patterns
Rule 7: Rapid Registration — User registered < 24h ago limited to 20,000 SYP send
Rule 8: Recipient Velocity — New wallet receiving >5 transfers in 1 hour flagged
```
