# Reconciliation Engine — Independent Sub-System within CFE

## Architecture

```
Incoming → Transaction Capture → Matching Engine → Exception Queue → Resolution
               ↓                       ↓                    ↓              ↓
        Source Data (Beza txns)    Match Rules        Unmatched items   Manual review
        External Data (bank files)  (exact/fuzzy)     → auto-retry      → force match
```

### Components

1. **Transaction Capture**: Ingests Beza transaction streams (Kafka topic `beza.txns.processed`) and external bank settlement files (SFTP drop from CBS, BBS, Bemo, SIIB, Commercial Bank of Syria).
2. **Matching Engine**: Applies configurable rule sets — exact match, fuzzy match (amount tolerance, date tolerance), and orphan detection.
3. **Exception Queue**: Items that fail matching are queued with priority scores. Auto-retry runs every 5 min for up to 3 attempts before escalating.
4. **Resolution Console**: UI for finance ops team to manually review, force-match, or reject unmatched items.

### Data Flow

- Beza internal transactions → PostgreSQL `recon.incoming_txns` via Debezium CDC
- Bank files → parsed from MT940/CSV → `recon.bank_statements`
- Matching runs on schedule → results written to `recon.match_results`
- Exceptions → `recon.exceptions` with status enum: `OPEN | AUTO_RETRY | ESCALATED | RESOLVED | REJECTED`

## Reconciliation Types

### 1. Internal Reconciliation (Beza ↔ CFE Postings)

| Field                  | Detail                                                                       |
| ---------------------- | ---------------------------------------------------------------------------- |
| **Schedule**           | Every 15 minutes, 24/7                                                       |
| **Source A**           | Beza transaction ledger (`ledger.transactions`)                              |
| **Source B**           | CFE general ledger postings (`cfe.general_ledger`)                           |
| **Matching Criteria**  | transaction_id, amount (0 SYP tolerance), currency, timestamp ± 60s          |
| **Volume**             | ~8,000–12,000 txns per run                                                   |
| **Exception Handling** | Mismatches auto-retried at next cycle (×3), then escalated to finance ops    |
| **Reporting**          | Dashboard on Grafana `recon/internal` — match rate %, exception count, aging |
| **SLA**                | 99.5% match rate within 30 min of txn posting                                |

### 2. External Reconciliation (Beza ↔ Bank Settlement Files)

| Field                  | Detail                                                                |
| ---------------------- | --------------------------------------------------------------------- |
| **Schedule**           | Daily at 08:00, 14:00, 20:00 (aligned with CBS settlement windows)    |
| **Source A**           | Beza settlement batch (`settlement.batches` where status = `SETTLED`) |
| **Source B**           | Bank MT940 statements from BBS, Bemo SIIB, Commercial Bank of Syria   |
| **Matching Criteria**  | batch_id, net_amount ± 100 SYP, value_date = T or T+1                 |
| **Volume**             | ~150–300 batches per run                                              |
| **Exception Handling** | Unmatched by T+1 10:00 → auto-create CBS breach notification ticket   |
| **Reporting**          | PDF report generated for CBS monthly audit — `recon/external-report`  |
| **SLA**                | 100% of batches matched within T+1 under CBS Decree 2023/45           |

### 3. Agent Reconciliation (Agent Float ↔ System)

| Field                  | Detail                                                                      |
| ---------------------- | --------------------------------------------------------------------------- |
| **Schedule**           | Daily at 07:00 (before agent network opens)                                 |
| **Source A**           | Agent float ledger (`agent.float_balances`)                                 |
| **Source B**           | Physical cash declaration from agent mobile app (`agent.cash_declarations`) |
| **Matching Criteria**  | agent_id, float_amount ± 5,000 SYP, declaration timestamp within 24h        |
| **Volume**             | ~3,500 agents across Syria                                                  |
| **Exception Handling** | Discrepancy >5,000 SYP → agent flagged; >50,000 SYP → field audit triggered |
| **Reporting**          | Agent float gap report emailed to regional supervisors                      |
| **SLA**                | 95% of agents reconciled within 24h                                         |

### 4. Merchant Reconciliation (MDR ↔ Settlements)

| Field                  | Detail                                                                             |
| ---------------------- | ---------------------------------------------------------------------------------- |
| **Schedule**           | Daily at 06:00                                                                     |
| **Source A**           | MDR calculations (`billing.mdr_calculations`)                                      |
| **Source B**           | Merchant settlement amounts (`settlement.merchant_payouts`)                        |
| **Matching Criteria**  | merchant_id, settlement_cycle, mdr_amount ± 50 SYP                                 |
| **Volume**             | ~800 merchants                                                                     |
| **Exception Handling** | Mismatch >50 SYP → auto-recalculate MDR; still mismatched → ticket to merchant ops |
| **Reporting**          | Monthly MDR reconciliation for partner audit                                       |
| **SLA**                | 99% merchant match rate within T+1                                                 |

### 5. Biller Reconciliation (Payments ↔ Biller Confirmations)

| Field                  | Detail                                                                                                    |
| ---------------------- | --------------------------------------------------------------------------------------------------------- |
| **Schedule**           | Hourly (every :00)                                                                                        |
| **Source A**           | Beza bill payment ledger (`bill_payments.completed`)                                                      |
| **Source B**           | Biller confirmation files (CSV/API callback from Sawa, SyriaTel, MTN, Damascus Water, Public Electricity) |
| **Matching Criteria**  | biller_txn_id, amount ± 0 SYP, confirmation within 2h                                                     |
| **Volume**             | ~500–1,500 per hour                                                                                       |
| **Exception Handling** | Unconfirmed after 2h → auto-refund to customer; biller dispute → manual queue                             |
| **Reporting**          | Hourly biller reconciliation report to biller relations team                                              |
| **SLA**                | 99.9% of payments confirmed within 1h per CBS oversight requirements                                      |

## Matching Rules

| Rule                 | Tolerance | Frequency | Action on Mismatch      |
| -------------------- | --------- | --------- | ----------------------- |
| Exact match          | 0 SYP     | Every txn | Auto-pass               |
| Amount tolerance     | ±100 SYP  | Every txn | Auto-pass with memo     |
| Date tolerance       | T+1       | Daily     | Flag for review         |
| Missing external     | N/A       | Daily     | Create exception ticket |
| Duplicate detection  | N/A       | Every txn | Flag, hold settlement   |
| Currency mismatch    | N/A       | Every txn | Hard fail, escalate     |
| Beneficiary mismatch | N/A       | Every txn | Flag for AML review     |

## Exception Handling Pipeline

```
Exception Created (queue: recon.exceptions)
    ↓
Priority Scoring (amount, age, type)
    ↓
Auto-Retry × 3 (every 5 min)
    ↓
If still unmatched → Escalate to Finance Ops
    ↓
Manual Review in Recon Console
    ↓
Action: Force Match | Reject | Request Investigation
    ↓
Audit Trail written to recon.audit_log
```

### SLA Tiers for Exception Resolution

| Priority | Definition                                   | Resolution SLA    |
| -------- | -------------------------------------------- | ----------------- |
| P0       | Amount > 10M SYP or CBS audit item           | 2 hours           |
| P1       | Amount 1M–10M SYP or agent float discrepancy | 4 hours           |
| P2       | Amount 100K–1M SYP                           | 24 hours          |
| P3       | Amount < 100K SYP                            | 72 hours          |
| P4       | Informational / memo-only                    | Next business day |

## CBS Compliance Requirements

- All reconciliation logs retained minimum 10 years per Syrian Banking Law 2015
- Monthly reconciliation reports submitted to CBS Supervision Department by 5th of following month
- Any unreconciled item > 1M SYP outstanding > 48h must be reported to CBS
- External audit of reconciliation engine by CBS-approved auditor annually
- System must maintain chain of custody for all manual overrides (force-match, reject)
- CBS decree 2024/12 mandates real-time settlement monitoring — reconciliation engine feeds CBK-SRM (Central Bank of Syria Settlement & Risk Monitoring) API

## Monitoring & Alerting

| Metric                      | Alert Threshold | Channel                            |
| --------------------------- | --------------- | ---------------------------------- |
| Match rate < 95%            | Any run         | PagerDuty + Slack #recon-alerts    |
| Exception queue > 100 items | Any time        | PagerDuty + Slack                  |
| Unreconciled > 48h          | Any item        | Email to CFO + Head of Finance Ops |
| CBS report deadline missed  | 5th of month    | Email to CEO + Compliance          |
| Bank file not received      | By 09:00 daily  | PagerDuty + Slack #bank-files      |

## Database Schema (Core Tables)

```sql
-- Reconciliation schema
CREATE SCHEMA IF NOT EXISTS recon;

-- Incoming transactions to be matched
CREATE TABLE recon.incoming_transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    source VARCHAR(50) NOT NULL, -- 'beza' | 'bank_mt940' | 'agent' | 'merchant' | 'biller'
    source_id VARCHAR(255) NOT NULL,
    transaction_ref VARCHAR(255),
    amount NUMERIC(18, 3) NOT NULL,
    currency CHAR(3) DEFAULT 'SYP',
    transaction_date TIMESTAMPTZ NOT NULL,
    value_date DATE,
    counterparty VARCHAR(255),
    raw_payload JSONB,
    ingested_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(source, source_id)
);

-- Match results
CREATE TABLE recon.match_results (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    run_id UUID NOT NULL REFERENCES recon.recon_runs(id),
    source_a_id UUID NOT NULL REFERENCES recon.incoming_transactions(id),
    source_b_id UUID REFERENCES recon.incoming_transactions(id),
    match_type VARCHAR(50) NOT NULL, -- 'exact' | 'fuzzy_amount' | 'fuzzy_date' | 'manual'
    confidence_score NUMERIC(5, 2),
    mismatch_reason TEXT,
    matched_at TIMESTAMPTZ DEFAULT NOW()
);

-- Exception queue
CREATE TABLE recon.exceptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    transaction_id UUID NOT NULL REFERENCES recon.incoming_transactions(id),
    exception_type VARCHAR(50) NOT NULL,
    priority VARCHAR(5) NOT NULL, -- 'P0'..'P4'
    status VARCHAR(20) DEFAULT 'OPEN',
    retry_count INT DEFAULT 0,
    last_retry_at TIMESTAMPTZ,
    escalated_at TIMESTAMPTZ,
    resolved_by VARCHAR(255),
    resolved_at TIMESTAMPTZ,
    resolution_notes TEXT,
    audit_trail JSONB DEFAULT '[]'
);

-- Audit log for manual actions
CREATE TABLE recon.audit_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    exception_id UUID REFERENCES recon.exceptions(id),
    action VARCHAR(50) NOT NULL,
    performed_by VARCHAR(255) NOT NULL,
    performed_at TIMESTAMPTZ DEFAULT NOW(),
    reason TEXT,
    previous_state JSONB,
    new_state JSONB
);
```

## Dashboards

| Dashboard               | URL                                              | Purpose                                       |
| ----------------------- | ------------------------------------------------ | --------------------------------------------- |
| Reconciliation Overview | `https://grafana.beza-sy.com/d/recon-overview`   | Match rates, exception counts, SLA compliance |
| Internal Recon          | `https://grafana.beza-sy.com/d/recon-internal`   | CFE posting match details                     |
| External Recon          | `https://grafana.beza-sy.com/d/recon-external`   | Bank settlement match status                  |
| Agent Recon             | `https://grafana.beza-sy.com/d/recon-agent`      | Agent float reconciliation                    |
| Exception Queue         | `https://grafana.beza-sy.com/d/recon-exceptions` | Real-time exception monitoring                |
