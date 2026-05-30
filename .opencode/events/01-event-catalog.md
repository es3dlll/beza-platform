# Beza Platform — Event Catalog

> Version: 1.0 | Last Updated: 2026-05-29

## Event Catalog

| Event | Producer | Consumer(s) | Trigger | Type |
|-------|----------|-------------|---------|------|
| MoneyHeld | CFE | Wallet, Fraud | Transfer initiated | Command |
| MoneyReleased | CFE | Wallet, Settlement | Transfer completed/failed | Event |
| MoneyPosted | CFE | Ledger, Analytics | Journal entry created | Event |
| FXLocked | FX | Remittance, Wallet | Rate confirmed | Event |
| RemittanceCreated | Remittance | Notification, Compliance | Remittance initiated | Event |
| SettlementCompleted | Settlement | Merchant, Agent, Ledger | Batch processed | Event |
| AgentApproved | Agent | Notification, Onboarding | Agent approved | Event |
| FraudDetected | Fraud | Compliance, Auth | Suspicious activity | Event |
| UserRegistered | Auth | Analytics, Onboarding | New user signup | Event |
| KYCApproved | Compliance | Auth, Limits | KYC level upgrade | Event |

---

## Event Details

### MoneyHeld

| Field | Value |
|-------|-------|
| **Description** | Funds are held (earmarked) in sender's wallet pending transfer completion. The amount is reserved and cannot be spent until released or posted. |
| **Schema Version** | 1.0 |
| **Priority** | Critical |
| **Retention** | 7 years (regulatory) |
| **Schema** | `{"holdId": "uuid", "walletId": "uuid", "amount": "decimal", "currency": "string(3)", "reason": "string", "expiresAt": "datetime", "createdAt": "datetime"}` |

### MoneyReleased

| Field | Value |
|-------|-------|
| **Description** | Held funds are released from the sender's wallet. Occurs when a transfer completes (funds posted to recipient) or fails (hold expires or is reversed). |
| **Schema Version** | 1.0 |
| **Priority** | Critical |
| **Retention** | 7 years |
| **Schema** | `{"releaseId": "uuid", "holdId": "uuid", "walletId": "uuid", "amount": "decimal", "currency": "string(3)", "reason": "release|reversal|expiry", "createdAt": "datetime"}` |

### MoneyPosted

| Field | Value |
|-------|-------|
| **Description** | Journal entry has been committed to the ledger. This represents the final, immutable record of a financial transaction affecting one or more accounts. |
| **Schema Version** | 1.1 |
| **Priority** | Critical |
| **Retention** | 10 years (audit) |
| **Schema** | `{"postingId": "uuid", "journalId": "uuid", "entries": [{"accountId": "uuid", "debit": "decimal", "credit": "decimal", "currency": "string(3)"}], "effectiveDate": "date", "createdAt": "datetime"}` |

### FXLocked

| Field | Value |
|-------|-------|
| **Description** | A foreign exchange rate has been confirmed and locked for a specific transaction. The rate is guaranteed for a defined period. |
| **Schema Version** | 1.0 |
| **Priority** | High |
| **Retention** | 5 years |
| **Schema** | `{"lockId": "uuid", "quoteId": "uuid", "fromCurrency": "string(3)", "toCurrency": "string(3)", "rate": "decimal", "amount": "decimal", "expiresAt": "datetime", "createdAt": "datetime"}` |

### RemittanceCreated

| Field | Value |
|-------|-------|
| **Description** | A new cross-border remittance has been initiated from a diaspora sender to a recipient in Syria. Includes sender, recipient, amount, corridor, and purpose. |
| **Schema Version** | 1.0 |
| **Priority** | High |
| **Retention** | 7 years |
| **Schema** | `{"remittanceId": "uuid", "senderId": "uuid", "recipientName": "string", "recipientPhone": "string", "sendAmount": "decimal", "sendCurrency": "string(3)", "receiveAmount": "decimal", "receiveCurrency": "string(3)", "fxRate": "decimal", "corridor": "string", "purpose": "string", "createdAt": "datetime"}` |

### SettlementCompleted

| Field | Value |
|-------|-------|
| **Description** | A settlement batch has been fully processed. All transactions in the batch have been reconciled and funds transferred to the respective merchant/agent accounts. |
| **Schema Version** | 1.0 |
| **Priority** | High |
| **Retention** | 7 years |
| **Schema** | `{"batchId": "uuid", "entityId": "uuid", "type": "merchant|agent", "transactionCount": "integer", "totalAmount": "decimal", "currency": "string(3)", "settlementDate": "date", "status": "completed", "createdAt": "datetime"}` |

### AgentApproved

| Field | Value |
|-------|-------|
| **Description** | An agent application has been approved after verification. The agent is now active and can perform cash-in/cash-out and other agent services. |
| **Schema Version** | 1.0 |
| **Priority** | High |
| **Retention** | 7 years |
| **Schema** | `{"agentId": "uuid", "userId": "uuid", "businessName": "string", "businessType": "string", "location": {"lat": "decimal", "lng": "decimal", "address": "string"}, "approvedBy": "uuid", "approvedAt": "datetime"}` |

### FraudDetected

| Field | Value |
|-------|-------|
| **Description** | Fraud detection system has flagged a transaction or user behavior as suspicious. May trigger automatic blocking or manual review. |
| **Schema Version** | 1.1 |
| **Priority** | Critical |
| **Retention** | 5 years |
| **Schema** | `{"alertId": "uuid", "entityType": "transaction|user|device", "entityId": "uuid", "riskScore": "decimal(0-100)", "rulesTriggered": ["string"], "actionTaken": "block|review|monitor", "detectedAt": "datetime"}` |

### UserRegistered

| Field | Value |
|-------|-------|
| **Description** | A new user has completed registration on the platform. Includes basic profile, contact info, and device fingerprint. |
| **Schema Version** | 1.0 |
| **Priority** | Medium |
| **Retention** | 7 years |
| **Schema** | `{"userId": "uuid", "phone": "string", "email": "string", "fullName": "string", "deviceId": "string", "registrationMethod": "phone|email|google|apple", "referralCode": "string?", "createdAt": "datetime"}` |

### KYCApproved

| Field | Value |
|-------|-------|
| **Description** | User's KYC (Know Your Customer) verification has been approved at a specific tier. Higher tiers unlock increased transaction limits and additional features. |
| **Schema Version** | 1.0 |
| **Priority** | High |
| **Retention** | 7 years |
| **Schema** | `{"userId": "uuid", "previousLevel": "tier1|tier2|tier3", "newLevel": "tier1|tier2|tier3", "verificationMethod": "nfc|video|document", "documents": ["string"], "approvedBy": "system|uuid", "approvedAt": "datetime"}` |
