# Encryption Standards

> Single source of truth for encryption across ALL Beza Platform features.

## Data Encryption at Rest

### AES-256-GCM for PII
All Personally Identifiable Information (PII) is encrypted at the database column level using **AES-256-GCM** (Authenticated Encryption with Associated Data).

**Algorithm**: AES-256-GCM
**Key size**: 256 bits
**IV size**: 96 bits (12 bytes), randomly generated per encryption
**Tag size**: 128 bits (16 bytes)
**Additional Authenticated Data (AAD)**: `tenant_id || table_name || column_name`

### Encrypted Columns

| Domain | Table | Encrypted Columns |
|--------|-------|-------------------|
| User | `users` | `full_name`, `email`, `phone` |
| User | `user_addresses` | `street`, `city`, `state`, `postal_code` |
| KYC | `kyc_documents` | `document_number`, `full_name_on_document` |
| KYC | `kyc_selfies` | `face_image_blob` |
| Wallet | `wallets` | `encrypted_balance` (for audit) |
| Settlement | `settlement_accounts` | `account_number`, `iban`, `bank_name` |
| Agent | `agent_floats` | `last_reconciliation_hash` |

### Encryption Key Hierarchy
```
Master Key (AWS KMS / HSM)
  └── Tenant Encryption Key (TEK) — 1 per tenant
       └── Column Encryption Keys (CEK) — 1 per encrypted column
            └── Data Encryption Keys (DEK) — 1 per field value (wrapped in storage)
```

- **Master Key**: Stored in AWS KMS (HSM-backed), rotated annually
- **TEK**: Encrypted by Master Key, stored in `tenant_config` table
- **CEK**: Encrypted by TEK, stored in `encryption_keys` table
- **DEK**: Generated per encryption operation, wrapped with CEK, stored alongside ciphertext

### Encryption/Decryption Flow
```php
function encryptField(string $plaintext, string $tenantId, string $table, string $column): string {
    $cek = getColumnKey($tenantId, $table, $column);
    $dek = random_bytes(32);           // Data Encryption Key
    $iv = random_bytes(12);            // Initialization Vector
    $aad = "$tenantId|$table|$column"; // AAD

    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $dek, OPENSSL_RAW_DATA, $iv, $tag, $aad, 16);
    $wrappedDek = aesWrap($dek, $cek); // AES Key Wrap (RFC 3394)

    // Packed: IV(12) + WRAPPED_DEK(40) + TAG(16) + CIPHERTEXT(variable)
    return base64_encode($iv . $wrappedDek . $tag . $ciphertext);
}

function decryptField(string $packed, string $tenantId, string $table, string $column): string {
    $cek = getColumnKey($tenantId, $table, $column);
    $data = base64_decode($packed);

    $iv = substr($data, 0, 12);
    $wrappedDek = substr($data, 12, 40);
    $tag = substr($data, 52, 16);
    $ciphertext = substr($data, 68);
    $aad = "$tenantId|$table|$column";

    $dek = aesUnwrap($wrappedDek, $cek);
    return openssl_decrypt($ciphertext, 'aes-256-gcm', $dek, OPENSSL_RAW_DATA, $iv, $tag, $aad);
}
```

### Search over Encrypted Data
- **Deterministic encryption** (AES-256-SIV) used ONLY for fields that must be searchable (email, phone)
- SIV mode preserves deterministic output for same plaintext+key combination
- Separate CEK used for deterministic encryption to limit exposure
- All other fields use randomized encryption (GCM)

## Data in Transit

### TLS 1.3
- **Minimum version**: TLS 1.2 (TLS 1.3 preferred)
- **Cipher suites** (TLS 1.3): `TLS_AES_256_GCM_SHA384`, `TLS_CHACHA20_POLY1305_SHA256`
- **Cipher suites** (TLS 1.2): `ECDHE-RSA-AES256-GCM-SHA384`
- **Certificate**: ECDSA P-384 or RSA 4096-bit, issued by public CA (LetsEncrypt / DigiCert)
- **HSTS**: `max-age=31536000; includeSubDomains; preload`
- **OCSP Stapling**: Enabled

### mTLS (Service-to-Service)
| Service Pair | Certificate Authority | Rotation |
|-------------|----------------------|----------|
| API Gateway → Wallet Service | Internal CA (Vault PKI) | 30 days |
| API Gateway → FX Service | Internal CA | 30 days |
| API Gateway → Remittance Service | Internal CA | 30 days |
| API Gateway → Notification Service | Internal CA | 30 days |
| API Gateway → CFE Connector | Internal CA | 30 days |
| Scheduler → All Services | Internal CA | 30 days |

mTLS configuration:
```
ServerAliveInterval: 60
Renegotiation: never
RequireClientCert: true
CRL: /etc/ssl/crl/internal-ca.crl
```

### Internal Communication
- All inter-service communication uses gRPC with mTLS
- REST fallback with TLS 1.3 if gRPC unavailable
- Service mesh (Istio) enforces mTLS at mesh level
- No plain HTTP internally — all traffic encrypted

## Key Management

### Key Rotation Schedule
| Key Type | Rotation Period | Grace Overlap | Notes |
|----------|----------------|---------------|-------|
| Master Key (KMS) | 365 days | 7 days | Manual rotation via AWS |
| Tenant Encryption Key | 180 days | 24h | Re-wraps CEKs |
| Column Encryption Key | 90 days | 24h | Double-writes during rotation |
| JWT Signing (Private) | 90 days | 24h | JWKS endpoint serves both keys |
| Pepper (HMAC) | 90 days | 48h | Old pepper used for verify only |
| mTLS Certificate | 30 days | 12h | Auto-renew via Vault |

### Key Rotation Process (CEK Example)
```
1. Generate new CEK version (v2)
2. Start writing NEW data with v2
3. Configure decrypt to try v2 first, then v1
4. Background job reads all v1-encrypted values, decrypt with v1, re-encrypt with v2
5. After all values migrated and grace period (24h) expires, retire v1
```

## Secrets Management

### Storage
- **AWS Secrets Manager**: API keys, database credentials, JWT private keys, pepper
- **AWS Parameter Store (SecureString)**: Non-critical config, feature flags
- **Vault**: Service mesh certificates, database dynamic credentials

### Secret Access Pattern
```php
class SecretsManager {
    public function getSecret(string $name, ?int $version = null): string {
        // 1. Check local cache (in-memory, TTL 300s)
        // 2. If miss, fetch from AWS Secrets Manager
        // 3. Cache in-memory with TTL
        // 4. Return decrypted value

        // Cache-aside pattern:
        // get from cache → if miss → fetch from store → set cache → return
    }
}
```

- Secrets are NEVER logged or exposed in error messages
- Secrets are NEVER hardcoded in config files or environment variables (outside local dev)
- Local development uses `.env` with dummy values, never production secrets

## Database Encryption

### Transparent Data Encryption (TDE)
- MySQL 8.0 TDE enabled for all databases
- Tablespace encryption using AES-256
- Key managed via MySQL keyring plugin (AWS KMS backend)

### Backup Encryption
- All database backups encrypted with AES-256-CBC
- Backup key stored in AWS KMS, different from operational keys
- Backup key rotated every 90 days
- Old backups remain accessible with their original key

## Audit Trail
- All encryption/decryption operations logged (without exposing the plaintext)
- Log fields: `action`, `table`, `column`, `key_version`, `user_id`, `correlation_id`, `result`
- Key access audit: every key retrieval logged with actor identity
- Annual key management review required by compliance
