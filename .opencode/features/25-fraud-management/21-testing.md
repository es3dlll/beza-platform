# Testing Strategy — Fraud Management

## Testing Overview

Fraud detection systems require rigorous testing across multiple dimensions: functional accuracy, performance under load, resistance to adversarial manipulation, and consistency over time.

## Testing Pyramid

```
          ╱╲
         ╱  ╲          Manual Red Team Tests (quarterly)
        ╱    ╲
       ╱──────╲       End-to-End Business Scenario Tests
      ╱        ╲
     ╱──────────╲     Integration Tests (feature modules + fraud)
    ╱            ╲
   ╱──────────────╲   ML Model Validation (holdout, cross-val)
  ╱                ╲
 ╱──────────────────╲ Fraud Rule Unit Tests + Scoring Tests
╱────────────────────╲
╱──────────────────────╲  Component Tests (rule, feature, action)
╱────────────────────────╲
╱──────────────────────────╲  Unit Tests (individual rule logic)
```

## Test Categories

### 1. Unit Tests — Individual Rules

Each fraud rule is a PHP class implementing `FraudRule` interface. Every rule must have:

```php
// Example test for AmountSpikeRule
class AmountSpikeRuleTest extends TestCase
{
    /** @test */
    public function it_triggers_when_amount_exceeds_three_sigma()
    {
        $rule = new AmountSpikeRule();
        $event = FraudEventFactory::make([
            'amount' => 150000,     // 3.3x user avg of 45000
            'user_avg_amount' => 45000,
            'user_std_amount' => 15000,
        ]);
        
        $result = $rule->evaluate($event, new FeatureVector($event));
        
        $this->assertTrue($result->triggered);
        $this->assertGreaterThan(0, $result->score);
        $this->assertEquals('slow', $result->action);
    }
    
    /** @test */
    public function it_does_not_trigger_for_normal_amounts()
    {
        $rule = new AmountSpikeRule();
        $event = FraudEventFactory::make([
            'amount' => 40000,      // Within normal range
            'user_avg_amount' => 45000,
            'user_std_amount' => 15000,
        ]);
        
        $result = $rule->evaluate($event, new FeatureVector($event));
        
        $this->assertFalse($result->triggered);
        $this->assertEquals(0, $result->score);
    }
    
    /** @test */
    public function it_returns_syria_specific_thresholds()
    {
        // Syria context: wide income variance means 3σ check
        // needs to be conservative for low-income users
        // but aggressive for high-income users
        $rule = new AmountSpikeRule();
        
        // Low-income user: 3x avg should trigger
        $lowIncome = FraudEventFactory::make([
            'amount' => 30000,
            'user_avg_amount' => 10000,
            'user_std_amount' => 3000,
        ]);
        $this->assertTrue($rule->evaluate($lowIncome, new FeatureVector($lowIncome))->triggered);
        
        // High-income user: 3x avg might be normal business
        $highIncome = FraudEventFactory::make([
            'amount' => 1500000,
            'user_avg_amount' => 500000,
            'user_std_amount' => 150000,
        ]);
        // Should check KYC level as well
        $this->assertFalse($rule->evaluate($highIncome, new FeatureVector($highIncome))->triggered);
    }
}
```

### 2. Scenario Testing — Known Fraud Patterns

Real fraud scenarios based on Syria-specific patterns:

```
Syria Fraud Test Scenarios:
┌────────────────────────────────────────────────────────────────┐
│ Scenario 1: Mule Account — New User, High Velocity             │
│ User registered 2 hours ago                                    │
│ Receives 500,000 SYP from 5 different accounts in 30 minutes  │
│ Attempts to cash out via agent                                 │
│ Expected: VEL-003 + DEV-005 triggered. Decision: BLOCK        │
├────────────────────────────────────────────────────────────────┤
│ Scenario 2: SIM Swap + Remittance Intercept                   │
│ Recipient SIM changed 1 hour ago                              │
│ Diaspora sender sends 300 EUR                                 │
│ Recipient has not changed location (still in Damascus)        │
│ Expected: SIM-001 triggered. Decision: REVIEW                  │
├────────────────────────────────────────────────────────────────┤
│ Scenario 3: Agent Float Theft                                 │
│ Agent recorded 200,000 SYP cash-in (fake)                     │
│ Agent float decreased by 180,000 SYP                          │
│ No customer present at agent location (geo check)             │
│ Expected: AGT-012 triggered. Decision: BLOCK                  │
├────────────────────────────────────────────────────────────────┤
│ Scenario 4: Social Engineering — User Shares OTP              │
│ User typically transacts 08:00-20:00                          │
│ Transaction at 03:00 from new device                           │
│ Amount is exactly 49,999 SYP (just below threshold)           │
│ Expected: DEV-001, TIM-001 triggered. Decision: REVIEW         │
├────────────────────────────────────────────────────────────────┤
│ Scenario 5: Account Takeover — Phishing Link                  │
│ User clicked phishing link 15 min ago                         │
│ Login from new device in different city                       │
│ Attempt to send max wallet balance (850,000 SYP)              │
│ Expected: ATO-001, TAMT-001, LOC-002 triggered. Decision: BLOCK│
├────────────────────────────────────────────────────────────────┤
│ Scenario 6: Merchant Collusion                                │
│ Merchant and same customer transact 10 times in 1 hour       │
│ Each transaction is exactly 50,000 SYP                        │
│ Customer and merchant share same device fingerprint           │
│ Expected: MER-001 (velocity) + DEV-007 (shared device). BLOCK │
├────────────────────────────────────────────────────────────────┤
│ Scenario 7: Fake Cash-In (Agent)                              │
│ Agent records cash-in but no cash deposited                   │
│ Agent balance doesn't change                                  │
│ Pattern: 5 "cash-ins" in 15 min to same recipient            │
│ Expected: AGT-015 (no cash movement). Decision: BLOCK         │
├────────────────────────────────────────────────────────────────┤
│ Scenario 8: False Positive — Legitimate Large Transfer        │
│ User is business owner (KYC Level 3)                          │
│ Monthly transfer to supplier — 2M SYP                         │
│ Same device, same location as usual                           │
│ Expected: Score should be LOW despite amount. Decision: APPROVE│
└────────────────────────────────────────────────────────────────┘
```

### 3. A/B Testing Rules

Before rules go fully active, they pass through:

```
Phase 1: Shadow Mode (24h)
- Rule evaluates but does NOT affect decisions
- Log: "Would have triggered: YES, Action: BLOCK, FP: YES"
- Metrics: hit rate, estimated FP rate, estimated fraud caught

Phase 2: Monitor Mode (48h)  
- Rule applies to 10% of transactions randomly
- Compare metrics: fraud rate with/without rule
- Measure: additional fraud caught, additional false positives

Phase 3: Gradual Rollout (48h)
- 25% → 50% → 75% → 100% over 48h
- Continuous monitoring: FP rate, fraud rate, decision time
- Auto-rollback if any metric exceeds threshold

Go/No-Go Gates:
┌────────────────────┬──────────────┐
│ Metric             │ Gate         │
├────────────────────┼──────────────┤
│ False positive rate│ < 3% at 25% │
│ Decision time      │ < +50ms avg │
│ Fraud rate change  │ Not > +0.1% │
│ User appeals       │ < 0.1% of   │
│                    │ affected    │
└────────────────────┴──────────────┘
```

### 4. ML Model Validation

```python
# Every model version must pass these tests:
def validate_model(model, validation_data):
    metrics = {
        'auc_roc': calculate_auc_roc(model, validation_data),
        'auc_pr': calculate_auc_pr(model, validation_data),
        'precision_at_80_recall': precision_at_recall(model, 0.8, validation_data),
        'f1_score': calculate_f1(model, validation_data),
        'log_loss': calculate_log_loss(model, validation_data),
    }
    
    # Acceptance criteria
    assert metrics['auc_roc'] > 0.90
    assert metrics['precision_at_80_recall'] > 0.70
    assert metrics['f1_score'] > 0.75
    assert metrics['log_loss'] < 0.15
    
    # Performance test
    inference_time = benchmark_inference(model, 10000)
    assert inference_time.p50 < 30ms
    assert inference_time.p99 < 70ms
    
    # Backward compatibility test
    old_model = load_model('previous_version')
    consistency = compare_predictions(model, old_model, validation_data)
    assert consistency['pearson_correlation'] > 0.90  # Not too different
    
    # Fairness test (Syria-specific)
    for region in ['Damascus', 'Aleppo', 'Homs', 'Coastal', 'Northeast']:
        regional_data = validation_data.filter(region=region)
        region_auc = calculate_auc_roc(model, regional_data)
        assert region_auc > 0.85, f"Model performs poorly in {region}"
```

### 5. Regression Testing on Historical Data

```
Regression Test Suite:
─────────────────────
Run daily against last 90 days of data:

1. Decision Consistency
   - Re-run all decisions with current rules + model
   - Compare to original decisions
   - Flag: > 5% divergence from original

2. Fraud Capture Rate
   - For confirmed fraud cases in history, verify:
     - Current rules would have caught them
     - Rules are not more permissive than original

3. Performance Regression
   - Measure decision time against baseline
   - Alert: > 20% increase in P50 or P99
```

### 6. Chaos Engineering

```
Fraud Chaos Tests:
─────────────────
Run in staging environment weekly:

1. ML Service Down
   - Kill ML scoring pod
   - Verify: rules-only fallback works
   - Verify: alert raised for ML failure
   - Verify: decision time still < 500ms

2. Database Connection Lost
   - Disconnect PostgreSQL
   - Verify: Redis cache handles reads
   - Verify: decisions still made (with reduced features)
   - Verify: queue for batch write

3. Latency Injection
   - Add 200ms latency to feature extraction
   - Verify: timeout handling works
   - Verify: fallback features used
   - Verify: transaction still screened (maybe with fewer features)

4. Massive Transaction Spike
   - Send 10x normal transaction volume
   - Verify: system scales (auto-scaling if applicable)
   - Verify: no timeouts > 1s
   - Verify: no decisions lost
```

### 7. False Positive Rate Monitoring

```
FP Rate Test Script (runs every 10 minutes):
───────────────────────────────────────────
1. Calculate rolling FP rate for last 1 hour
2. Compare to baseline (last 7 days same hour, same day of week)
3. If FP rate > baseline + 3σ → alert
4. If FP rate > 10% → auto-disable recent rule deploys
5. If FP rate > 20% → emergency: switch to "all review" mode

FP Rate per Segment:
- By product: wallet, agent, remittance, merchant, bills, payroll
- By region: Damascus, Aleppo, Homs, Coastal, Northeast, Rural
- By KYC level: 1, 2, 3
- By transaction size: <10K, 10K-100K, 100K-1M, >1M
- By hour of day
- By day of week

Target: No segment exceeds 5% FP rate
```

### 8. User Appeal Testing

```
Appeal Flow Tests:
────────────────
1. User submits appeal with valid ID → auto-resolved within 30s
2. User submits appeal with mismatched ID → escalated to manual
3. User submits appeal for same transaction twice → deduplicated
4. User submits appeal without transaction ID → guided to provide it
5. User submits appeal in Arabic → handled correctly (RTL support)
6. Appeal acknowledged via SMS → link opens correct transaction
```

### 9. Syria-Specific Testing Considerations

| Consideration | Testing Approach |
|---------------|-----------------|
| Arabic/RTL text | All UI tested in Arabic; proper RTL layout |
| SYP amounts | Amount-based rules tested with SYP values (no decimal places) |
| Syriatel/MTN networks | Latency tests simulating operator-specific delays |
| Multiple SIM users | Test that multiple SIMs on one account don't trigger false flags |
| IDP locations | Test that location changes from conflict zones don't auto-flag |
| Friday (holiday) | Test that reduced Friday volume doesn't skew velocity rules |
| Syrian holidays | Test pattern files for Ramadan, Eid, etc. |
| Power outages | Test offline queue behavior |

### 10. Test Data

Synthetic data generation approach:

```php
class FraudEventFactory
{
    public static function make(array $overrides = []): FraudEvent
    {
        return new FraudEvent(array_merge([
            'event_id' => Str::uuid(),
            'transaction_id' => 'txn_' . Str::random(20),
            'feature_source' => 'wallet',
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'currency' => 'SYP',
            'sender_id' => 'usr_' . Str::random(20),
            'recipient_id' => 'usr_' . Str::random(20),
            'context' => [
                'device_fingerprint' => 'fp_' . Str::random(12),
                'is_new_device' => fake()->boolean(20),
                'location' => [
                    'city' => fake()->randomElement(['Damascus', 'Aleppo', 'Homs']),
                ],
            ],
        ], $overrides));
    }
}
```
