# Platform Event Catalog

> All events follow CloudEvents 1.0 spec. Syria context — currency defaults to SYP, regulatory retention may override.

## Event Schema

```json
{
  "specversion": "1.0",
  "id": "ulid",
  "source": "/beza/{domain}/{version}",
  "type": "com.beza.{domain}.{action}",
  "time": "ISO8601",
  "tenant_id": "string",
  "data": {}
}
```

---

## Wallet Domain

### MoneyHeld
- **Producer:** CFE (Core Financial Engine)
- **Consumers:** Wallet Module, Fraud Detection, Ledger
- **Trigger:** Transfer initiated — hold placed on sender account
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.wallet.money.held",
  "data": {
    "hold_id": "string",
    "account_id": "string",
    "amount": "integer",
    "currency": "string",
    "reason": "string",
    "expires_at": "ISO8601"
  }
}
```

### MoneyReleased
- **Producer:** CFE
- **Consumers:** Wallet Module, Fraud Detection, Notification
- **Trigger:** Transfer completed (hold released) OR hold expired
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.wallet.money.released",
  "data": {
    "hold_id": "string",
    "account_id": "string",
    "amount": "integer",
    "currency": "string",
    "reason": "string",
    "release_type": "settled|expired|cancelled"
  }
}
```

### MoneyPosted
- **Producer:** CFE
- **Consumers:** Wallet Module, Ledger, Notification
- **Trigger:** Funds credited to recipient account
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.wallet.money.posted",
  "data": {
    "transfer_id": "string",
    "from_account_id": "string",
    "to_account_id": "string",
    "amount": "integer",
    "currency": "string",
    "fee": "integer",
    "posted_at": "ISO8601"
  }
}
```

### BalanceUpdated
- **Producer:** Wallet Module
- **Consumers:** App (real-time), Notification, Analytics
- **Trigger:** Any balance-affecting event
- **Priority:** Medium
- **Retention:** 30 days
- **Idempotent:** No
- **Schema:**
```json
{
  "type": "com.beza.wallet.balance.updated",
  "data": {
    "account_id": "string",
    "available_balance": "integer",
    "ledger_balance": "integer",
    "change_amount": "integer",
    "change_type": "credit|debit",
    "reason": "string"
  }
}
```

---

## FX Domain

### RateLocked
- **Producer:** FX Engine
- **Consumers:** Remittance Module, Notification
- **Trigger:** User confirms rate for a remittance
- **Priority:** High
- **Retention:** 30 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.fx.rate.locked",
  "data": {
    "rate_id": "string",
    "from_currency": "string",
    "to_currency": "string",
    "rate": "number",
    "amount": "integer",
    "expires_at": "ISO8601",
    "corridor": "string"
  }
}
```

### RateExpired
- **Producer:** FX Engine
- **Consumers:** Remittance Module, Notification
- **Trigger:** Locked rate TTL exceeded
- **Priority:** Medium
- **Retention:** 30 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.fx.rate.expired",
  "data": {
    "rate_id": "string",
    "reason": "ttl_exceeded|user_inactive",
    "locked_at": "ISO8601",
    "expired_at": "ISO8601"
  }
}
```

### ConversionCompleted
- **Producer:** FX Engine
- **Consumers:** Remittance Module, Wallet Module, Ledger
- **Trigger:** Currency conversion successfully processed
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.fx.conversion.completed",
  "data": {
    "conversion_id": "string",
    "rate_id": "string",
    "from_currency": "string",
    "to_currency": "string",
    "from_amount": "integer",
    "to_amount": "integer",
    "rate_applied": "number",
    "fee": "integer"
  }
}
```

### RateAnomalyDetected
- **Producer:** FX Engine (monitoring)
- **Consumers:** Compliance, Ops Dashboard
- **Trigger:** Rate deviates from market by > threshold
- **Priority:** Critical
- **Retention:** 365 days
- **Idempotent:** No
- **Schema:**
```json
{
  "type": "com.beza.fx.rate.anomaly.detected",
  "data": {
    "rate_id": "string",
    "corridor": "string",
    "platform_rate": "number",
    "market_rate": "number",
    "deviation_pct": "number",
    "threshold_pct": "number",
    "detected_at": "ISO8601"
  }
}
```

---

## Remittance Domain

### RemittanceInitiated
- **Producer:** Remittance API
- **Consumers:** FX Engine, Compliance, Wallet Module, Notification
- **Trigger:** User submits remittance request
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.remittance.initiated",
  "data": {
    "remittance_id": "string",
    "sender_id": "string",
    "recipient_name": "string",
    "recipient_country": "string",
    "amount_send": "integer",
    "amount_receive": "integer",
    "from_currency": "string",
    "to_currency": "string",
    "rate_locked": "number",
    "corridor": "string",
    "channel": "wallet|bank|cash"
  }
}
```

### RemittanceCompleted
- **Producer:** Remittance Service
- **Consumers:** Wallet Module, Notification, Analytics
- **Trigger:** Recipient funds available
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.remittance.completed",
  "data": {
    "remittance_id": "string",
    "completed_at": "ISO8601",
    "amount_disbursed": "integer",
    "disbursement_channel": "string",
    "partner_txn_id": "string"
  }
}
```

### RemittanceFailed
- **Producer:** Remittance Service
- **Consumers:** Wallet Module, Notification, Ops
- **Trigger:** Irrecoverable failure in processing
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.remittance.failed",
  "data": {
    "remittance_id": "string",
    "stage": "fx|aml|disbursement|partner",
    "reason_code": "string",
    "reason_detail": "string",
    "amount_refunded": "integer",
    "failed_at": "ISO8601"
  }
}
```

### CorridorRateApplied
- **Producer:** FX Engine
- **Consumers:** Remittance Module, Analytics
- **Trigger:** Corridor-specific rate set/updated
- **Priority:** Low
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.remittance.corridor.rate.applied",
  "data": {
    "corridor": "string",
    "from_currency": "string",
    "to_currency": "string",
    "buy_rate": "number",
    "sell_rate": "number",
    "mid_market_rate": "number",
    "spread_pct": "number",
    "effective_from": "ISO8601",
    "effective_until": "ISO8601"
  }
}
```

---

## Agent Domain

### AgentCashIn
- **Producer:** Agent App / API
- **Consumers:** Wallet Module, Ledger, Notification
- **Trigger:** Customer deposits cash at agent
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.agent.cash.in",
  "data": {
    "txn_id": "string",
    "agent_id": "string",
    "customer_id": "string",
    "amount": "integer",
    "currency": "string",
    "balance_before": "integer",
    "balance_after": "integer",
    "commission": "integer",
    "location": "object"
  }
}
```

### AgentCashOut
- **Producer:** Agent App / API
- **Consumers:** Wallet Module, Ledger, Notification
- **Trigger:** Customer withdraws cash at agent
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.agent.cash.out",
  "data": {
    "txn_id": "string",
    "agent_id": "string",
    "customer_id": "string",
    "amount": "integer",
    "currency": "string",
    "balance_before": "integer",
    "balance_after": "integer",
    "commission": "integer",
    "location": "object"
  }
}
```

### AgentFloatLow
- **Producer:** Agent Module (monitor)
- **Consumers:** Ops Dashboard, Notification
- **Trigger:** Agent float drops below configured threshold
- **Priority:** Medium
- **Retention:** 30 days
- **Idempotent:** No
- **Schema:**
```json
{
  "type": "com.beza.agent.float.low",
  "data": {
    "agent_id": "string",
    "current_float": "integer",
    "threshold": "integer",
    "currency": "string",
    "alert_level": "warning|critical"
  }
}
```

### AgentSuspended
- **Producer:** Agent Module / Compliance
- **Consumers:** Notification, Ops Dashboard
- **Trigger:** Agent KYC expired / suspicious activity / compliance hold
- **Priority:** High
- **Retention:** 365 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.agent.suspended",
  "data": {
    "agent_id": "string",
    "reason_code": "string",
    "reason_detail": "string",
    "suspended_by": "string",
    "suspended_at": "ISO8601",
    "expected_review_by": "ISO8601"
  }
}
```

---

## Merchant Domain

### MerchantPayment
- **Producer:** Merchant API / POS
- **Consumers:** Wallet Module, Ledger, Notification
- **Trigger:** Customer pays merchant via QR / NFC / USSD
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.merchant.payment",
  "data": {
    "payment_id": "string",
    "merchant_id": "string",
    "customer_id": "string",
    "amount": "integer",
    "currency": "string",
    "fee": "integer",
    "commission": "integer",
    "payment_method": "qr|nfc|ussd",
    "pos_id": "string",
    "location": "object"
  }
}
```

### MerchantSettled
- **Producer:** Settlement Engine
- **Consumers:** Merchant Module, Notification, Ledger
- **Trigger:** Merchant batch settlement completed
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.merchant.settled",
  "data": {
    "settlement_id": "string",
    "merchant_id": "string",
    "period_start": "ISO8601",
    "period_end": "ISO8601",
    "gross_amount": "integer",
    "fee_amount": "integer",
    "commission_amount": "integer",
    "net_amount": "integer",
    "txn_count": "integer",
    "disbursed_at": "ISO8601"
  }
}
```

---

## Settlement Domain

### SettlementBatchStarted
- **Producer:** Settlement Engine
- **Consumers:** Ledger, Notification, Ops
- **Trigger:** End-of-day settlement cycle begins
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.settlement.batch.started",
  "data": {
    "batch_id": "string",
    "batch_date": "YYYY-MM-DD",
    "entity_count": "integer",
    "estimated_volume": "integer"
  }
}
```

### SettlementBatchCompleted
- **Producer:** Settlement Engine
- **Consumers:** Ledger, Notification, Ops
- **Trigger:** Settlement cycle finished successfully
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.settlement.batch.completed",
  "data": {
    "batch_id": "string",
    "total_settled": "integer",
    "total_fees": "integer",
    "total_agents": "integer",
    "total_merchants": "integer",
    "completed_at": "ISO8601"
  }
}
```

### SettlementBatchFailed
- **Producer:** Settlement Engine
- **Consumers:** Ops, Notification
- **Trigger:** Settlement cycle fails (e.g. CFE down, reconciliation mismatch)
- **Priority:** Critical
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.settlement.batch.failed",
  "data": {
    "batch_id": "string",
    "stage": "collect|net|reconcile|disburse",
    "reason_code": "string",
    "reason_detail": "string",
    "retry_count": "integer",
    "max_retries": "integer"
  }
}
```

### ReconciliationMatched
- **Producer:** Settlement Engine
- **Consumers:** Ledger, Ops
- **Trigger:** Internal vs external records match
- **Priority:** Medium
- **Retention:** 365 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.settlement.reconciliation.matched",
  "data": {
    "reconciliation_id": "string",
    "batch_id": "string",
    "internal_total": "integer",
    "external_total": "integer",
    "variance": "integer",
    "txn_count": "integer",
    "matched_at": "ISO8601"
  }
}
```

### ReconciliationException
- **Producer:** Settlement Engine
- **Consumers:** Ops, Compliance
- **Trigger:** Internal vs external records mismatch
- **Priority:** High
- **Retention:** 365 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.settlement.reconciliation.exception",
  "data": {
    "reconciliation_id": "string",
    "batch_id": "string",
    "internal_total": "integer",
    "external_total": "integer",
    "variance": "integer",
    "variance_pct": "number",
    "unmatched_txns": ["string"],
    "flagged_at": "ISO8601"
  }
}
```

---

## Savings Domain

### SavingsGoalCreated
- **Producer:** Savings API
- **Consumers:** Notification, Analytics
- **Trigger:** User creates a savings goal
- **Priority:** Low
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.savings.goal.created",
  "data": {
    "goal_id": "string",
    "user_id": "string",
    "name": "string",
    "target_amount": "integer",
    "currency": "string",
    "target_date": "ISO8601",
    "auto_save_enabled": "boolean"
  }
}
```

### SavingsGoalCompleted
- **Producer:** Savings Engine
- **Consumers:** Notification, Analytics, Wallet
- **Trigger:** Goal target amount reached
- **Priority:** Medium
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.savings.goal.completed",
  "data": {
    "goal_id": "string",
    "user_id": "string",
    "name": "string",
    "target_amount": "integer",
    "total_saved": "integer",
    "completed_at": "ISO8601",
    "days_early": "integer"
  }
}
```

### AutoSaveExecuted
- **Producer:** Savings Scheduler
- **Consumers:** Wallet Module, Notification
- **Trigger:** Scheduled auto-save deduction
- **Priority:** Medium
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.savings.auto.save.executed",
  "data": {
    "goal_id": "string",
    "user_id": "string",
    "amount": "integer",
    "frequency": "daily|weekly|monthly",
    "balance_before": "integer",
    "balance_after": "integer",
    "executed_at": "ISO8601"
  }
}
```

### RoundUpExecuted
- **Producer:** Savings Engine
- **Consumers:** Wallet Module, Notification
- **Trigger:** Transaction round-up transferred to savings
- **Priority:** Low
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.savings.roundup.executed",
  "data": {
    "goal_id": "string",
    "user_id": "string",
    "source_txn_id": "string",
    "roundup_amount": "integer",
    "original_amount": "integer",
    "rounded_amount": "integer",
    "executed_at": "ISO8601"
  }
}
```

---

## Cards Domain

### CardCreated
- **Producer:** Cards Service
- **Consumers:** Notification, Wallet Module
- **Trigger:** Virtual/physical card issued
- **Priority:** Medium
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.cards.created",
  "data": {
    "card_id": "string",
    "user_id": "string",
    "card_type": "virtual|physical",
    "card_network": "mastercard|visa",
    "last_four": "string",
    "expires_at": "ISO8601",
    "created_at": "ISO8601"
  }
}
```

### CardTransaction
- **Producer:** Cards Service / Processor
- **Consumers:** Wallet Module, Fraud Detection, Notification
- **Trigger:** Card authorisation / clearing / settlement
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.cards.transaction",
  "data": {
    "card_id": "string",
    "txn_id": "string",
    "type": "authorisation|clearing|settlement|refund",
    "amount": "integer",
    "currency": "string",
    "merchant_name": "string",
    "merchant_category": "string",
    "status": "approved|declined|pending",
    "decline_reason": "string",
    "pos_entry_mode": "chip|contactless|ecom"
  }
}
```

### CardFrozen
- **Producer:** Cards Service / User Action
- **Consumers:** Notification, Fraud Detection
- **Trigger:** User freezes card / fraud detected
- **Priority:** High
- **Retention:** 90 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.cards.frozen",
  "data": {
    "card_id": "string",
    "user_id": "string",
    "reason": "user_initiated|fraud_suspected|stolen|lost",
    "frozen_by": "user|system",
    "frozen_at": "ISO8601"
  }
}
```

### CardFraudAlert
- **Producer:** Fraud Engine
- **Consumers:** Notification, Compliance, Cards Service
- **Trigger:** Suspicious card activity detected
- **Priority:** Critical
- **Retention:** 365 days
- **Idempotent:** No
- **Schema:**
```json
{
  "type": "com.beza.cards.fraud.alert",
  "data": {
    "card_id": "string",
    "txn_id": "string",
    "risk_score": "number",
    "rule_ids": ["string"],
    "trigger_reasons": ["string"],
    "merchant": "string",
    "amount": "integer",
    "currency": "string",
    "location": "object"
  }
}
```

---

## Compliance Domain

### KYCPending
- **Producer:** KYC Service
- **Consumers:** Compliance Dashboard, Notification
- **Trigger:** KYC documents submitted, pending review
- **Priority:** Medium
- **Retention:** 365 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.compliance.kyc.pending",
  "data": {
    "user_id": "string",
    "application_id": "string",
    "documents": ["string"],
    "submitted_at": "ISO8601",
    "review_type": "automatic|manual",
    "expected_review_by": "ISO8601"
  }
}
```

### KYCApproved
- **Producer:** KYC Service
- **Consumers:** Wallet Module, Notification
- **Trigger:** KYC verified and approved
- **Priority:** Medium
- **Retention:** 365 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.compliance.kyc.approved",
  "data": {
    "user_id": "string",
    "application_id": "string",
    "tier": "tier1|tier2|tier3",
    "daily_limit": "integer",
    "monthly_limit": "integer",
    "approved_at": "ISO8601"
  }
}
```

### KYCRejected
- **Producer:** KYC Service
- **Consumers:** Notification, Compliance Dashboard
- **Trigger:** KYC documents rejected
- **Priority:** Medium
- **Retention:** 365 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.compliance.kyc.rejected",
  "data": {
    "user_id": "string",
    "application_id": "string",
    "rejection_reason": "string",
    "document_issues": ["string"],
    "rejected_at": "ISO8601",
    "can_resubmit": "boolean",
    "resubmit_instructions": "string"
  }
}
```

### AMLRuleTriggered
- **Producer:** AML Engine
- **Consumers:** Compliance Dashboard, Transaction Service
- **Trigger:** Transaction matches AML rule
- **Priority:** Critical
- **Retention:** 365 days
- **Idempotent:** No
- **Schema:**
```json
{
  "type": "com.beza.compliance.aml.rule.triggered",
  "data": {
    "alert_id": "string",
    "txn_id": "string",
    "user_id": "string",
    "rule_id": "string",
    "rule_name": "string",
    "severity": "low|medium|high|critical",
    "risk_score": "number",
    "amount": "integer",
    "currency": "string",
    "country": "string",
    "triggered_at": "ISO8601"
  }
}
```

### SanctionsHit
- **Producer:** Screening Engine
- **Consumers:** Compliance Dashboard, Transaction Service
- **Trigger:** Name/entity matches sanctions list
- **Priority:** Critical
- **Retention:** 365 days (or regulatory minimum)
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.compliance.sanctions.hit",
  "data": {
    "alert_id": "string",
    "txn_id": "string",
    "user_id": "string",
    "matched_entity": "string",
    "sanctions_list": "string",
    "match_type": "exact|partial|fuzzy",
    "match_score": "number",
    "matched_field": "string",
    "list_country": "string",
    "action_taken": "blocked|flagged_for_review"
  }
}
```

---

## System Domain

### ServiceHealthChanged
- **Producer:** Health Monitor
- **Consumers:** Ops Dashboard, Alerting
- **Trigger:** Service health status changes
- **Priority:** High
- **Retention:** 30 days
- **Idempotent:** No
- **Schema:**
```json
{
  "type": "com.beza.system.service.health.changed",
  "data": {
    "service_name": "string",
    "status": "healthy|degraded|down",
    "previous_status": "string",
    "latency_ms": "integer",
    "error_rate": "number",
    "region": "string",
    "detected_at": "ISO8601"
  }
}
```

### QueueDepthAlert
- **Producer:** Queue Monitor
- **Consumers:** Ops Dashboard, Auto-scaler
- **Trigger:** Message queue depth exceeds threshold
- **Priority:** Medium
- **Retention:** 7 days
- **Idempotent:** No
- **Schema:**
```json
{
  "type": "com.beza.system.queue.depth.alert",
  "data": {
    "queue_name": "string",
    "current_depth": "integer",
    "threshold": "integer",
    "oldest_message_age_ms": "integer",
    "consumer_count": "integer",
    "alerted_at": "ISO8601"
  }
}
```

### DatabaseReplicaLag
- **Producer:** DB Monitor
- **Consumers:** Ops Dashboard, Auto-failover
- **Trigger:** Replica lag exceeds threshold
- **Priority:** High
- **Retention:** 7 days
- **Idempotent:** No
- **Schema:**
```json
{
  "type": "com.beza.system.database.replica.lag",
  "data": {
    "instance": "string",
    "replica": "string",
    "lag_seconds": "integer",
    "threshold_seconds": "integer",
    "region": "string",
    "detected_at": "ISO8601"
  }
}
```

### BackupCompleted
- **Producer:** Backup Scheduler
- **Consumers:** Ops Dashboard
- **Trigger:** Scheduled backup finished
- **Priority:** Low
- **Retention:** 30 days
- **Idempotent:** Yes
- **Schema:**
```json
{
  "type": "com.beza.system.backup.completed",
  "data": {
    "backup_id": "string",
    "database": "string",
    "size_bytes": "integer",
    "duration_seconds": "integer",
    "type": "full|incremental|snapshot",
    "status": "success|partial",
    "completed_at": "ISO8601",
    "location": "string"
  }
}
```

---

> **Total: 36 events** across 10 domains.
