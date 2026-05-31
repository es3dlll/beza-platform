# Settlement Security

## Authentication & Authorization

### Access Control
| Role | Permissions | Who |
|------|-------------|-----|
| `settlement.viewer` | Read all batches, reports, exceptions | Compliance, Auditors |
| `settlement.operator` | View + process batches, resolve exceptions | Operations team |
| `settlement.supervisor` | Operator + approve/override, high-value exceptions | Ops lead |
| `settlement.admin` | Supervisor + configure accounts, cut-offs, tolerances | Tech lead |

### Multi-Factor Authentication (MFA)
- Required for all settlement operations
- TOTP-based (Google Authenticator) or hardware key (FIDO2)
- Session timeout: 15 minutes of inactivity

### Idempotency Keys
```http
POST /api/v1/settlement/batch/create
Idempotency-Key: 7a1c3e8e-5b2f-4d9a-8c6d-1f3e2a4b5c7d
```
- All mutation endpoints require idempotency keys
- Keys expire after 24 hours
- Duplicate requests return original response (idempotent)

## Data Security

### Encryption at Rest
```
Database: MySQL TDE (Transparent Data Encryption)
Settlement amounts: Encrypted column (AES-256-GCM)
Payment files: Server-side encryption (SSE-S3)
Backups: AES-256 encrypted
```

### Encryption in Transit
```
All API endpoints: TLS 1.3 minimum
Bank integrations: Mutual TLS (mTLS)
Internal service mesh: WireGuard tunnel
```

### Sensitive Data Handling
| Data Element | Classification | Handling |
|-------------|----------------|----------|
| IBAN/Bank account numbers | Confidential | Encrypted at rest, masked in logs |
| Settlement amounts | Internal | Visible in dashboard, encrypted in backup |
| Bank API keys/credentials | Secret | Vault (Hashicorp Vault), never in code |
| Bank confirmations | Internal | 7-year retention, access logged |
| Exception investigation notes | Confidential | Audited access, no bulk export |

## Audit Logging

### Mandatory Audit Events
```php
// All state changes are logged immutably
SettlementAuditLog::create([
    'batch_id' => $batch->id,
    'action' => 'batch_processed',
    'actor_id' => Auth::id() ?? 'system',
    'actor_type' => Auth::check() ? 'user' : 'system',
    'old_values' => json_encode(['status' => 'draft']),
    'new_values' => json_encode(['status' => 'processing']),
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

### Audit Retention
- Settlement audit logs: 7 years (regulatory requirement)
- Exception history: 7 years
- Payment order lifecycle: 7 years
- Bank confirmations: 7 years

## Fraud Prevention

### Controls
1. **Dual approval**: Batches > 10M SYP require supervisor approval
2. **Rate limiting**: 10 settlement API calls per minute per user
3. **Anomaly detection**: Flag batches with >20% amount deviation from historical average
4. **Bank account whitelist**: Only pre-configured accounts can receive settlement payments
5. **Manual override**: Any manual settlement requires written approval + 2FA

### Anti-Duplication
```php
class SettlementDeduplication
{
    public function isDuplicate(string $idempotencyKey): bool
    {
        return SettlementBatch::where('idempotency_key', $idempotencyKey)->exists();
    }

    public function checkDuplicateTransaction(string $transactionId): bool
    {
        return SettlementBatchItem::where('metadata->transaction_ids', 'LIKE', "%{$transactionId}%")->exists();
    }
}
```
