# States — Fraud Management Feature

## Transaction Risk States

Every transaction passing through Beza's fraud engine exists in one of the following risk states:

### State Definitions

```
┌─────────────────────────────────────────────────────────────────────┐
│                    TRANSACTION RISK STATES                          │
│                                                                     │
│  ┌──────────┐                                                       │
│  │   SAFE   │ ◄── Risk score < 40. Transaction approved instantly. │
│  └────┬─────┘    User unaware of fraud screening.                   │
│       │                                                             │
│  ┌────▼─────┐                                                       │
│  │SUSPICIOUS│ ◄── Risk score 40-79. Requires verification.         │
│  └────┬─────┘    Transaction slowed; user asked to verify.          │
│       │                                                             │
│  ┌────▼─────┐                                                       │
│  │  BLOCKED │ ◄── Risk score ≥ 80. Transaction prevented.          │
│  └────┬─────┘    User notified; account may be frozen.             │
│       │                                                             │
│        ├──────────────────────────────┐                            │
│        ▼                              ▼                             │
│  ┌────────────┐              ┌─────────────────┐                   │
│  │CONFIRMED   │              │ FALSE POSITIVE  │                   │
│  │ FRAUD      │              │                 │                   │
│  └─────┬──────┘              └────────┬────────┘                   │
│        │                              │                             │
│  ┌─────▼──────┐              ┌────────▼────────┐                  │
│  │ UNDER      │              │    CLOSED       │                   │
│  │INVESTIGATION│             │ (no further     │                   │
│  └─────┬──────┘              │  action needed) │                   │
│        │                     └─────────────────┘                   │
│  ┌─────▼──────┐                                                    │
│  │  RESOLVED  │                                                    │
│  │ (recovered)│                                                    │
│  └────────────┘                                                    │
└─────────────────────────────────────────────────────────────────────┘
```

### Detailed State Table

| State | Risk Score | Decision | User Facing | Action | Duration |
|-------|-----------|----------|-------------|--------|----------|
| **safe** | 0–39 | Approve | No (invisible) | Transaction completes normally | Instant |
| **suspicious** | 40–59 | Verify | Yes (soft) | Request PIN/OTP verification | < 30s |
| **highly_suspicious** | 60–79 | Manual Review | Yes (warning) | Held for ops team review | < 4h |
| **blocked** | 80–100 | Block | Yes (alert) | Transaction prevented, user notified | Permanent unless appeal |
| **confirmed_fraud** | N/A | Freeze | Yes (freeze) | Account frozen, investigation opens | Until resolved |
| **false_positive** | N/A | Release | Yes (apology) | Transaction approved, user notified | Instant on decision |
| **under_investigation** | N/A | Hold | Yes (freeze) | Case opened, ops team investigating | < SLA target |
| **escalated** | N/A | Escalate | Yes (notified) | Referred to CBS / law enforcement | Until response |
| **recovered** | N/A | Resolve | Yes (refund) | Funds returned to victim | On recovery |
| **closed** | N/A | Closed | Yes (final) | Case closed, no further action | Final state |

## Application States

### Fraud Screening Pipeline States

```
┌────────────┐    ┌────────────┐    ┌────────────┐    ┌────────────┐
│ TRANSACTION│───▶│  FEATURE   │───▶│   RULE     │───▶│    ML      │
│  SUBMITTED │    │ EXTRACTION │    │  ENGINE    │    │  SCORING   │
└────────────┘    └────────────┘    └────────────┘    └────────────┘
                                                        │
                                                        ▼
                                                 ┌────────────┐
                                                 │  DECISION  │
                                                 │   ENGINE   │
                                                 └────────────┘
                                                        │
                                    ┌───────────────────┼───────────────────┐
                                    ▼                   ▼                   ▼
                             ┌────────────┐     ┌────────────┐     ┌────────────┐
                             │  APPROVE   │     │   VERIFY   │     │   BLOCK    │
                             │ (safe)     │     │(suspicious)│     │ (blocked)  │
                             └────────────┘     └────────────┘     └────────────┘
```

### Fraud Review Pipeline States

```
┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐
│  ALERT   │──▶│  TRIAGE  │──▶│ INVESTI- │──▶│  DECISION│──▶│RESOLUTION│
│ GENERATED│   │          │   │ GATE     │   │          │   │          │
└──────────┘   └──────────┘   └──────────┘   └──────────┘   └──────────┘
   ↑                ↑              ↑              ↑              ↑
 Auto by       Ops lead       Investigator   Senior Ops     Completion
 fraud engine  reviews queue  deep dives     makes final    + reporting
                              into case      determination
```

### Fraud Escalation States

```
┌──────────────────────────────────────────────────────────────────┐
│                     ESCALATION PATH                              │
│                                                                  │
│  P0/P1 Alert ──▶ Ops Lead Review ──▶ [Resolved at Level 1]      │
│       │                                                            │
│       └──▶ Level 2 (Fraud Manager) ──▶ [Resolved at Level 2]      │
│               │                                                    │
│               └──▶ Level 3 (CBS Reporting) ──▶ [SAR Filed]        │
│                       │                                            │
│                       └──▶ Law Enforcement ──▶ [Legal Action]      │
│                                                                  │
│  Each escalation level has SLA:                                  │
│  Level 1: Immediate (15 min)                                     │
│  Level 2: Within 1 hour                                          │
│  Level 3: Within 24 hours (CBS mandatory)                        │
└──────────────────────────────────────────────────────────────────┘
```

## State Transitions

### Per Transaction

```
safe ────▶ suspicious ────▶ verified (back to safe)
                            └──▶ blocked ──▶ confirmed_fraud
                                       └──▶ false_positive (back to safe)
```

### Per Case

```
alert ──▶ under_investigation ──▶ confirmed_fraud ──▶ reported (CBS)
                                       │                  └──▶ recovered
                                       │                  └──▶ closed (loss)
                                       │
                                       └──▶ false_positive ──▶ closed
                                       │
                                       └──▶ escalated ──▶ law_enforcement
                                                         └──▶ cbs_sar_filed
```

## Syria-Specific State Considerations

| Consideration | Impact | Implementation |
|---------------|--------|----------------|
| Network latency (Syriatel/MTN) | States may transition slowly | Async state updates with timeout |
| Agent POS offline | Transaction state queued | Local state → sync when online |
| Multiple SIM users | Legitimate reason for SIM "swap" | "suspicious" not "blocked" for SIM state |
| Displacement mobility | Location changes are legitimate | "suspicious" with lower weight for displaced regions |
| Informal transactions | Difficult to classify | "suspicious" with context-aware rules |

## State Persistence

| Data | Storage | Retention |
|------|---------|-----------|
| Transaction risk state | PostgreSQL (transactions table) | 10 years (AML) |
| Fraud case state | PostgreSQL (cases table) | 10 years (AML) |
| State transition log | PostgreSQL (case_events table) | 10 years (AML) |
| ML scoring state | PostgreSQL (ml_scores table) | 90 days (model training) |
| In-flight screening state | Redis (temporary) | 5 minutes or until decision |

## State Machine Implementation

```php
// FraudCase state machine transitions (Laravel state machine)
$transitions = [
    'alert' => ['under_investigation', 'false_positive'],
    'under_investigation' => ['confirmed_fraud', 'false_positive', 'escalated'],
    'confirmed_fraud' => ['reported_cbs', 'closed_with_loss'],
    'false_positive' => ['closed'],
    'escalated' => ['law_enforcement', 'cbs_sar_filed'],
    'reported_cbs' => ['recovered', 'closed_with_loss'],
    'recovered' => ['closed'],
    'cbs_sar_filed' => ['closed'],
    'law_enforcement' => ['closed'],
];
```
