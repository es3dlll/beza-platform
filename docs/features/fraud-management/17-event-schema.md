# Event Schema — Fraud Management

## Event Overview

FraudEngine emits and consumes events through the Laravel event system. Events are the primary integration mechanism between FraudEngine and other Beza modules.

## Event Catalog

### 1. TransactionInitiated (Consumed by FraudEngine)

**Source:** All financial modules (Wallet, Agent, Remittance, Merchant, Bills, Payroll)

**Purpose:** Triggers fraud screening for every financial transaction.

```json
{
  "event_name": "TransactionInitiated",
  "event_version": "1.0",
  "event_id": "evt_2xKp1ZsQ8mNvR4tY6wL9",
  "timestamp": "2025-03-14T15:42:30+03:00",
  "source_module": "wallet",
  "data": {
    "transaction_id": "txn_8hJ2kL4mN6pQ9rS1tU3v",
    "feature_source": "wallet",
    "amount": 150000.0,
    "currency": "SYP",
    "sender_id": "usr_a1B2c3D4e5F6g7H8i9J0",
    "recipient_id": "usr_k1L2m3N4o5P6q7R8s9T0",
    "transaction_type": "p2p_transfer",
    "context": {
      "device_fingerprint": "fp_abc123def456",
      "device_name": "Samsung Galaxy S23",
      "device_os": "Android 14",
      "ip_address": "10.0.0.1",
      "location": {
        "lat": 33.5138,
        "lon": 36.2765,
        "city": "Damascus",
        "region": "Damascus Governorate"
      },
      "network_operator": "Syriatel",
      "is_new_device": true,
      "user_agent": "BezaApp/2.4.1 (Android 14; Samsung S23)",
      "session_id": "sess_m9N0bV1cX2zL3k4J5h6G"
    },
    "sender_profile": {
      "account_age_days": 180,
      "kyc_level": 2,
      "avg_transaction_amount": 45000.0,
      "transaction_count_30d": 24,
      "total_volume_30d": 1080000.0,
      "risk_tier": "standard"
    },
    "recipient_profile": {
      "account_age_days": 90,
      "kyc_level": 1,
      "avg_transaction_amount": 35000.0,
      "trust_score": 65
    }
  }
}
```

### 2. FraudAlertRaised (Emitted by FraudEngine)

**Purpose:** Notifies operations team of a suspicious transaction requiring review.

```json
{
  "event_name": "FraudAlertRaised",
  "event_version": "1.0",
  "event_id": "evt_9xY3zA1bC5dE7fG8hI0j",
  "timestamp": "2025-03-14T15:42:30.150+03:00",
  "source_module": "fraud_engine",
  "data": {
    "alert_id": "alt_4mN6pQ9rS1tU3vW5xY7",
    "fraud_case_id": null,
    "transaction_id": "txn_8hJ2kL4mN6pQ9rS1tU3v",
    "priority": "P1",
    "risk_score": 78,
    "risk_level": "highly_suspicious",
    "decision": "review",
    "action_taken": "flagged_for_review",
    "rules_triggered": [
      {
        "rule_id": "DEV-001",
        "rule_name": "New Device Threshold",
        "score": 25,
        "action": "flag",
        "details": "Device Samsung Galaxy S23 new to user"
      },
      {
        "rule_id": "TAMT-001",
        "rule_name": "Transaction Amount Spike",
        "score": 20,
        "action": "slow",
        "details": "Amount 150K SYP is 3.3x user avg"
      },
      {
        "rule_id": "LOC-002",
        "rule_name": "New Location",
        "score": 15,
        "action": "flag",
        "details": "Transaction from Aleppo, user in Damascus"
      }
    ],
    "ml_score": 0.72,
    "ml_model_version": "v1.2.3",
    "processing_time_ms": 87
  }
}
```

### 3. FraudInvestigationStarted (Emitted by FraudEngine)

```json
{
  "event_name": "FraudInvestigationStarted",
  "event_version": "1.0",
  "event_id": "evt_2kL4mN6pQ9rS1tU3vW5x",
  "timestamp": "2025-03-14T15:48:00+03:00",
  "data": {
    "case_id": "FR-2025-05678",
    "transaction_id": "txn_8hJ2kL4mN6pQ9rS1tU3v",
    "fraud_type": "account_takeover",
    "priority": "P0",
    "status": "under_investigation",
    "amount_at_risk": 500000.0,
    "currency": "SYP",
    "victim_user_id": "usr_a1B2c3D4e5F6g7H8i9J0",
    "suspect_user_id": "usr_k1L2m3N4o5P6q7R8s9T0",
    "assigned_to": "ops_sarah",
    "sla_deadline": "2025-03-14T16:03:00+03:00",
    "description": "Unauthorized transfer attempt. User confirmed via phone she did not initiate. PIN likely compromised via phishing.",
    "opened_by": "system"
  }
}
```

### 4. FraudConfirmed (Emitted by FraudEngine)

```json
{
  "event_name": "FraudConfirmed",
  "event_version": "1.0",
  "event_id": "evt_5mN6pQ9rS1tU3vW5xY7z",
  "timestamp": "2025-03-14T19:30:00+03:00",
  "data": {
    "case_id": "FR-2025-05678",
    "fraud_type": "account_takeover",
    "confirmed_fraud_type": "phishing_credential_theft",
    "amount_lost": 0.0,
    "amount_recovered": 500000.0,
    "currency": "SYP",
    "victim_user_id": "usr_a1B2c3D4e5F6g7H8i9J0",
    "suspect_user_id": "usr_k1L2m3N4o5P6q7R8s9T0",
    "confirmed_by": "ops_sarah",
    "cbs_notified": true,
    "sar_filed": true,
    "sar_id": "SAR-2025-00123"
  }
}
```

### 5. FraudFalsePositive (Emitted by FraudEngine)

```json
{
  "event_name": "FraudFalsePositive",
  "event_version": "1.0",
  "event_id": "evt_8pQ9rS1tU3vW5xY7zA2b",
  "timestamp": "2025-03-14T15:43:00+03:00",
  "data": {
    "case_id": null,
    "transaction_id": "txn_8hJ2kL4mN6pQ9rS1tU3v",
    "original_risk_score": 78,
    "original_decision": "review",
    "false_positive_reason": "user_verified_identity",
    "verification_method": "pin_match",
    "resolved_by": "system_auto",
    "resolution_time_ms": 12000,
    "feedback_to_model": true,
    "user_notified": true,
    "user_satisfaction_score": 4
  }
}
```

### 6. FraudModelRetrained (Emitted by FraudEngine)

```json
{
  "event_name": "FraudModelRetrained",
  "event_version": "1.0",
  "event_id": "evt_3tU3vW5xY7zA2bC4dE6f",
  "timestamp": "2025-03-15T03:00:00+03:00",
  "data": {
    "model_id": "mdl_gbt_v1.2.4",
    "previous_model_id": "mdl_gbt_v1.2.3",
    "model_type": "gradient_boosted_trees",
    "training_status": "success",
    "training_data_start": "2024-12-15",
    "training_data_end": "2025-03-14",
    "training_samples": 2450000,
    "feature_count": 218,
    "metrics": {
      "auc_roc": 0.942,
      "precision": 0.845,
      "recall": 0.782,
      "f1_score": 0.812,
      "log_loss": 0.089
    },
    "improvement": { "auc_roc_delta": 0.004, "f1_delta": 0.008 },
    "new_features_added": 5,
    "features_removed": 2,
    "auto_deployed": true,
    "duration_minutes": 47
  }
}
```

### 7. FraudCaseEscalated (Emitted by FraudEngine)

```json
{
  "event_name": "FraudCaseEscalated",
  "event_version": "1.0",
  "event_id": "evt_6wX5yZ7aB2cD4eF6gH8i",
  "timestamp": "2025-03-15T10:00:00+03:00",
  "data": {
    "case_id": "FR-2025-05678",
    "escalation_level": "cbs",
    "escalation_reason": "amount_exceeds_threshold",
    "amount_involved": 500000.0,
    "currency": "SYP",
    "cbs_reference": "CBS-NOT-2025-0042",
    "sar_id": "SAR-2025-00123",
    "escalated_by": "compliance_mahmoud",
    "escalation_notes": "Amount exceeds 1M SYP threshold when combining linked transactions. CBS notified per AML Law 31/2010 Article 5."
  }
}
```

## Event Consumption

| Other Modules       | Consumes                                             | Action                                   |
| ------------------- | ---------------------------------------------------- | ---------------------------------------- |
| Wallet Module       | FraudConfirmed, FraudFalsePositive                   | Release/hold funds, update wallet status |
| Agent Module        | FraudAlertRaised, FraudConfirmed                     | Freeze agent account, notify field ops   |
| Remittance Module   | FraudAlertRaised (remittance)                        | Hold remittance, notify sender           |
| Compliance Module   | FraudConfirmed, FraudCaseEscalated                   | Generate SAR, file with CBS              |
| Notification Module | FraudAlertRaised, FraudConfirmed, FraudFalsePositive | Send push/SMS/email                      |
| Analytics Module    | All fraud events                                     | Update fraud metrics, dashboards         |

## Event Store

All fraud events stored in `fraud_event_store` table for 10 years (AML compliance):

```sql
CREATE TABLE fraud_event_store (
    id UUID PRIMARY KEY,
    event_name VARCHAR(100),
    event_version VARCHAR(10),
    event_id VARCHAR(50) UNIQUE,
    aggregate_type VARCHAR(30),
    aggregate_id VARCHAR(50),
    data JSONB,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```
