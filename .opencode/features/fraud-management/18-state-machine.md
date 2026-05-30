# State Machine — Fraud Case Management

## Overview

The fraud case state machine governs the lifecycle of every fraud investigation. It ensures consistent handling, clear audit trail, and proper escalation paths.

## State Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FRAUD CASE STATE MACHINE                            │
│                                                                             │
│                               ┌─────────┐                                  │
│                               │  ALERT  │                                  │
│                               └────┬────┘                                  │
│                                    │                                        │
│                           ┌────────┴────────┐                              │
│                           ▼                 ▼                              │
│                    ┌──────────────┐  ┌──────────────┐                      │
│                    │  FALSE       │  │  UNDER       │                      │
│                    │  POSITIVE    │  │ INVESTIGATION│                      │
│                    └──────┬───────┘  └──────┬───────┘                      │
│                           │                 │                              │
│                           │        ┌────────┼────────┐                    │
│                           │        ▼        ▼        ▼                    │
│                           │  ┌────────┐┌────────┐┌────────┐              │
│                           │  │CONFIRM-││ FALSE  ││ESCALATE│              │
│                           │  │ED FRAUD││POSITIVE││   -D   │              │
│                           │  └───┬────┘└────────┘└───┬────┘              │
│                           │      │                    │                    │
│                           │      ├────────┐     ┌────┼────┐              │
│                           │      ▼        ▼     ▼    ▼    ▼              │
│                           │  ┌──────┐┌────────┐┌───┐┌────┐┌───────────┐ │
│                           │  │CBS   ││CLOSED  ││CBs││LAW ││ CLOSED    │ │
│                           │  │REPORT││WITH   ││SAR││ENF.││           │ │
│                           │  │ -ED  ││LOSS   ││   ││    ││           │ │
│                           │  └──┬───┘└────────┘└───┘└───┘└───────────┘ │
│                           │     │                                         │
│                           │     ├────────┐                                │
│                           │     ▼        ▼                                │
│                           │  ┌──────┐┌────────┐                          │
│                           │  │RECOV-││ CLOSED │                          │
│                           │  │ -ERED││        │                          │
│                           │  └──┬───┘└────────┘                          │
│                           │     │                                         │
│                           │     ▼                                         │
│                           │  ┌────────┐                                   │
│                           │  │ CLOSED │                                   │
│                           │  └────────┘                                   │
└─────────────────────────────────────────────────────────────────────────────┘
```

## State Definitions

### alert
**Entry:** Fraud engine detects suspicious transaction (score ≥ 60)
**Exit:** Investigation starts OR marked false positive
**SLA:** P0: 15min, P1: 1h, P2: 4h to move to investigation
**Behavior:** Alert visible on dashboard, assigned to available fraud analyst

### under_investigation
**Entry:** Fraud analyst acknowledges alert and starts investigation
**Description:** Active investigation in progress. Analyst gathers evidence, reviews transaction history, contacts parties.
**SLA:** P0: 15min total case time, P1: 1h, P2: 4h
**Actions:**
- Add investigation notes
- Attach evidence (screenshots, logs, call recordings)
- View user/agent profile
- Run transaction graph analysis
- Contact user/agent

### confirmed_fraud
**Entry:** Analyst determines this IS fraud
**Validation required:** At least 2 pieces of evidence (e.g., user confirmation + device analysis)
**Actions:**
- Freeze all related accounts
- Block suspect devices
- Add to fraud watchlist
- Notify victim
- Begin recovery process

### false_positive
**Entry:** Analyst OR system (auto) determines this is NOT fraud
**Sub-types:**
- `user_verified`: User passed PIN/OTP verification
- `analyst_reviewed`: Manual review confirmed legitimate
- `auto_detected`: ML model confidence was high but incorrect
**Actions:**
- Release any holds on transaction
- Remove fraud flags from user profile
- Send apology if user was impacted
- Feed data back to ML retraining

### reported_cbs
**Entry:** Case value exceeds CBS reporting threshold (≥ 1M SYP or equivalent)
**Actions:**
- Auto-generate SAR in CBS format
- File with CBS AML Commission
- Record SAR reference number
- Update case with CBS response

### escalated
**Entry:** Case requires escalation beyond normal fraud team
**Escalation paths:**
- `cbs`: Regulatory reporting
- `law_enforcement`: Criminal referral
- `senior_management`: High-value or reputational risk

### recovered
**Entry:** Funds recovered from confirmed fraud
**Recovery methods:**
- Transaction cancelled in-flight (best outcome)
- Suspect account frozen and funds returned
- Insurance payout
- Legal recovery

### closed_with_loss
**Entry:** Confirmed fraud where funds were not recovered
**Required:** Loss amount documented for IFRS 9 provisioning

### closed
**Entry:** Final state for resolved cases
**Sub-types:**
- `resolved_positive`: False positive, user happy
- `resolved_recovered`: Funds recovered
- `resolved_loss`: Fraud confirmed, no recovery
- `resolved_escalated`: Handed off to CBS/law enforcement

## State Transitions

| From | To | Trigger | Required Conditions |
|------|----|---------|-------------------|
| alert | under_investigation | Analyst acknowledges | User assigned to case |
| alert | false_positive | Auto-verification | User passed PIN/OTP |
| alert | confirmed_fraud | Direct confirmation | Overwhelming evidence (e.g., user confirmed) |
| under_investigation | confirmed_fraud | Analyst decision | ≥ 2 evidence items |
| under_investigation | false_positive | Analyst decision | Evidence shows legitimate |
| under_investigation | escalated | Analyst/escalation | Amount > threshold OR reputational risk |
| confirmed_fraud | reported_cbs | Auto if amount > 1M SYP OR manual | Case confirmed |
| confirmed_fraud | recovered | Funds returned | Actual funds recovery |
| confirmed_fraud | closed_with_loss | No recovery possible | Investigation exhausted |
| confirmed_fraud | escalated | Analyst decision | CBS threshold OR criminal intent |
| false_positive | closed | Auto after verification | Verification complete |
| reported_cbs | recovered | Funds returned | Recovery after CBS notified |
| reported_cbs | closed_with_loss | No recovery | CBS notified, no recovery |
| reported_cbs | escalated | CBS requests escalation | Law enforcement referral |
| escalated | closed | Handoff complete | CBS/LE acknowledges receipt |
| recovered | closed | Recovery confirmed | Funds returned to victim |

## State Machine Implementation (Laravel)

```php
// Using a state machine pattern (e.g., spatie/laravel-model-states)
class FraudCaseState extends State
{
    abstract public function transitionToAlert(): void;
    abstract public function transitionToUnderInvestigation(): void;
    abstract public function transitionToConfirmedFraud(): void;
    abstract public function transitionToFalsePositive(): void;
    abstract public function transitionToReportedCbs(): void;
    abstract public function transitionToEscalated(): void;
    abstract public function transitionToRecovered(): void;
    abstract public function transitionToClosedWithLoss(): void;
    abstract public function transitionToClosed(): void;
}

class AlertState extends FraudCaseState
{
    public function transitionToUnderInvestigation(): void 
    { 
        $this->transition(new UnderInvestigationState($this->model));
    }
    public function transitionToFalsePositive(): void 
    { 
        $this->transition(new FalsePositiveState($this->model));
    }
    public function transitionToConfirmedFraud(): void 
    { 
        $this->transition(new ConfirmedFraudState($this->model));
    }
}

// In FraudCase model:
class FraudCase extends Model
{
    protected $casts = [
        'status' => FraudCaseState::class,
    ];
    
    public function assignAnalyst(string $userId): void
    {
        $this->status->transitionToUnderInvestigation();
        $this->assigned_to = $userId;
        $this->save();
        
        FraudInvestigationStarted::dispatch($this);
    }
}
```

## SLA Enforcement

| State | SLA Timer | Escalation Action |
|-------|-----------|-------------------|
| alert → under_investigation | P0: 15min, P1: 1h, P2: 4h | Escalate to fraud manager |
| under_investigation → decision | P0: 15min, P1: 1h, P2: 4h | Escalate to senior ops |
| confirmed_fraud → reported_cbs | 24h (regulatory) | Auto-escalate to compliance |
| Any state beyond SLA | Immediately | Notify next level + auto-escalate in system |

## Audit Trail

Every state transition is logged:

```sql
CREATE TABLE fraud_case_state_transitions (
    id UUID PRIMARY KEY,
    fraud_case_id UUID NOT NULL,
    from_state VARCHAR(30),
    to_state VARCHAR(30),
    transitioned_by UUID,
    transition_reason TEXT,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fraud_case_id) REFERENCES fraud_cases(id)
);
```

## Bulk State Operations

| Operation | States Affected | Automation |
|-----------|----------------|------------|
| False positive storm resolution | Multiple alert → false_positive | Batch transition with reason |
| Mass account freeze | Multiple confirmed_fraud | Batch freeze with case linking |
| CBS report generation | All confirmed_fraud → reported_cbs | Batch SAR generation |
| End-of-month cleanup | All stale under_investigation → closed | Force close with audit note |
