# Authentication Patterns

> Single source of truth for auth patterns across ALL Beza Platform features.

## JWT Structure

### Access Token
```json
{
  "typ": "JWT",
  "alg": "RS256"
}
{
  "sub": "user_uuid",
  "role": "user",
  "permissions": ["wallet:read", "wallet:transfer"],
  "kyc_level": 2,
  "device_id": "device_fingerprint",
  "session_id": "sess_uuid",
  "iat": 1717000000,
  "exp": 1717003600,
  "jti": "unique_token_id"
}
```

- **Algorithm**: RS256 (RSA 2048-bit key pair)
- **Expiry**: Access token 60 min, Refresh token 7 days
- **Issuer**: `beza-platform`
- **Audience**: `beza-api`
- **Private key rotation**: Every 90 days, with 24h overlap for grace period

### Refresh Token Flow
```
POST /api/v1/auth/refresh
Body: { refresh_token: "..." }
Response: { access_token, refresh_token, expires_in }
```

Refresh tokens are **rotation-based**: each use invalidates the previous one and issues a new pair. If a compromised refresh token is used after the user's valid one was already rotated, the session is terminated entirely (token theft detection).

## PIN Hashing

- **Algorithm**: bcrypt
- **Cost factor**: 12 (≈250ms per hash on modern hardware)
- **Pepper**: 32-byte HMAC key stored in AWS Secrets Manager, rotated every 90 days
- **Process**:
  1. PIN is concatenated with a per-user 16-byte salt (random, stored in `user_salts` table)
  2. `HMAC-SHA256(pepper, salt || pin)` produces the peppered value
  3. `bcrypt(cost=12, peppered_value)` produces the final hash stored in `users.pin_hash`

```php
// Laravel example
use Illuminate\Support\Facades\Hash;

function hashPin(string $pin, string $userSalt, string $pepper): string {
    $peppered = hash_hmac('sha256', $userSalt . $pin, $pepper);
    return Hash::make($peppered, ['rounds' => 12]);
}

function verifyPin(string $pin, string $userSalt, string $pepper, string $storedHash): bool {
    $peppered = hash_hmac('sha256', $userSalt . $pin, $pepper);
    return Hash::check($peppered, $storedHash);
}
```

## Device Binding

Each session is bound to a device fingerprint computed on the client:

```
device_fingerprint = HMAC-SHA256(
    device_secret,
    device_id || platform || os_version || app_version
)
```

- **device_id**: UUID v4 generated on first app launch, stored in Keychain (iOS) / EncryptedSharedPreferences (Android)
- **device_secret**: 256-bit random key generated during first unlock, stored in secure enclave
- On login, the fingerprint is registered to the session
- On critical operations (transfers, KYC changes), the fingerprint is re-verified

### Device Trust Score
```php
enum DeviceTrustLevel: string {
    case NEW = 'new';           // First seen, MFA required
    case TRUSTED = 'trusted';   // Seen >7 days, no suspicious activity
    case HIGH = 'high';         // Biometrically verified + trusted >30 days
    case COMPROMISED = 'compromised'; // Flagged by risk engine
}
```

## Session Management

### Session Token Storage
| Component | Storage | Encryption |
|-----------|---------|------------|
| Access Token | Memory only (mobile) / HTTP-only cookie (web) | N/A (JWT signed) |
| Refresh Token | Keychain (iOS) / EncryptedSharedPreferences (Android) | AES-256-GCM with device key |
| Session ID | Server-side Redis | N/A |

### Session Termination Events
- User logout (explicit)
- Refresh token rotation theft detected
- Password/PIN change
- KYC level change
- Admin force-logout
- 7 days of inactivity (configurable per tenant)
- Device untrusted

### Concurrent Session Limit
- **Standard users**: 5 sessions max
- **Agents**: 3 sessions max
- **Admin roles**: 2 sessions max
- Excess sessions revoke oldest first

## MFA Flows

### Available Methods
| Method | Security Level | User Friction | Fallback |
|--------|---------------|---------------|----------|
| SMS OTP | Medium | Low | Backup codes |
| TOTP (Google Auth) | High | Medium | Backup codes |
| Email OTP | Low | Low | SMS OTP |

### When MFA Is Required
1. Login from new device (User-Agent + IP not seen in 30 days)
2. Password/PIN change
3. Registration of new device
4. Transfer above daily limit threshold
5. KYC upgrade attempt
6. Beneficiary addition
7. Wallet closure

### MFA Challenge Flow
```
1. Client sends primary auth (password/PIN)
2. Server responds 200 with session, but sets mfa_required: true
3. Client requests challenge: POST /api/v1/auth/mfa/challenge
4. Server sends OTP to registered method (SMS/TOTP/Email)
5. Client submits: POST /api/v1/auth/mfa/verify { code, session_id }
6. Server validates code, marks session as mfa_verified: true
```

### Backup Codes
- Generated on MFA enrollment: 10 codes, each 16 chars (`XXXX-XXXX-XXXX-XXXX`)
- Stored as SHA-256 hashes server-side
- Each code is single-use
- When <2 codes remain, prompt user to regenerate
- Regeneration invalidates all previous codes

### Rate Limiting
| Action | Limit | Window |
|--------|-------|--------|
| Login attempts | 5 | 30 minutes |
| MFA challenge requests | 3 | 15 minutes |
| MFA verify attempts | 5 | 15 minutes |
| PIN change attempts | 3 | 60 minutes |
| Password reset | 3 | 24 hours |

## OAuth2 / Social Login (Future)
- OAuth2 with PKCE for third-party integrations
- Google Sign-In and Apple Sign-In for user convenience
- User must link a phone number before first social login completes
