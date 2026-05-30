# ML Model — Fraud Detection Engine

## Model Architecture

Beza uses a hybrid ensemble approach combining Gradient Boosted Trees (LightGBM) for tabular feature learning and a Deep Learning model (Transformer) for sequential/behavioral pattern detection.

```
┌─────────────────────────────────────────────────────────────────────┐
│                      ENSEMBLE MODEL ARCHITECTURE                    │
│                                                                     │
│  ┌─────────────────────────────┐   ┌─────────────────────────────┐ │
│  │    MODEL A: LightGBM         │   │    MODEL B: Deep Learning   │ │
│  │    (Gradient Boosted Trees)  │   │    (Transformer)            │ │
│  │                              │   │                              │ │
│  │  Input: 200+ engineered      │   │  Input: User transaction    │ │
│  │  features (tabular)          │   │  sequence (last 50 txns)   │ │
│  │                              │   │                              │ │
│  │  Architecture: Gradient      │   │  Architecture: 4-layer      │ │
│  │  Boosting, 500 estimators,   │   │  Transformer encoder with   │ │
│  │  max_depth=8, learning_rate  │   │  positional encoding,       │ │
│  │  =0.05, subsample=0.8        │   │  d_model=128, n_heads=8    │ │
│  │                              │   │                              │ │
│  │  Output: fraud_prob (0-1)   │   │  Output: sequence_anomaly   │ │
│  │                              │   │  score (0-1)               │ │
│  └────────────┬────────────────┘   └─────────────┬───────────────┘ │
│               │                                   │                 │
│               └───────────────┬───────────────────┘                 │
│                               ▼                                     │
│                    ┌─────────────────────┐                          │
│                    │  META-LEARNER        │                          │
│                    │  (Logistic Regression│                          │
│                    │   on Model A + B     │                          │
│                    │   outputs)           │                          │
│                    └──────────┬──────────┘                          │
│                               ▼                                     │
│                    ┌─────────────────────┐                          │
│                    │  FINAL PREDICTION    │                          │
│                    │  fraud probability   │                          │
│                    │  (0.0 – 1.0)        │                          │
│                    └─────────────────────┘                          │
│                                                                     │
│  Also in ensemble (for redundancy):                                │
│  • Model C: XGBoost (if LightGBM fails)                            │
│  • Model D: Isolation Forest (anomaly detection, ensemble final)   │
└─────────────────────────────────────────────────────────────────────┘
```

## Model Training Pipeline

### Training Schedule

| Aspect | Configuration |
|--------|---------------|
| Frequency | Daily, 03:00 Syria time (00:00 UTC) |
| Training window | Last 90 days of transaction data |
| Validation window | Last 7 days (time-series split, not random) |
| Test window | Last 24h (out-of-time validation) |
| Features | 218 engineered features |
| Training samples | ~2.5M transactions/day (at scale) |
| Positive class | Confirmed fraud + fraud alerts (imbalanced: ~0.2%) |

### Training Pipeline Steps

```
┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐
│ 1. DATA  │──▶│ 2. FEAT- │──▶│ 3. MODEL │──▶│ 4. VALI- │──▶│ 5. DEPLOY│
│ EXTRACT  │   │ URE ENG │   │ TRAIN    │   │ DATION   │   │          │
└──────────┘   └──────────┘   └──────────┘   └──────────┘   └──────────┘
    │              │              │              │              │
    ▼              ▼              ▼              ▼              ▼
SQL query     200+        LightGBM +   Holdout:      ONNX export
from fraud    features    Transformer  7-day gap     to production
decisions +   computed    training      Val metrics:  Shadow deploy
events table  in Python                 AUC, F1,     24h monitor
                                        precision,
                                        recall
```

### Feature Engineering

**200+ features across categories:**

| Category | Count | Examples |
|----------|-------|---------|
| Amount features | 25 | txn amount, ratio to avg, z-score, rolling min/max/std, growth rate |
| Time features | 15 | hour of day, day of week, days since last txn, time since account creation |
| Velocity features | 30 | txns in last 5min/30min/1h/24h, total amount in time windows, unique recipients in 24h |
| Device features | 20 | device age, new device flag, device count on account, emulator detection |
| Location features | 20 | distance from home, new city flag, region change, agent distance |
| Network features | 15 | IP change frequency, carrier change, VPN/proxy detection |
| Behavioral features | 30 | typical transaction time, typical amounts, typical recipients, sequence patterns |
| Identity features | 15 | KYC level, account age, failed login count, password reset flag |
| Agent features | 20 | agent trust score, agent float ratio, agent dispute rate, agent distance |
| Graph features | 10 | shared device count, shared IP count, connection to flagged accounts |
| Contextual features | 18 | product type, transaction channel, corridor risk (remittance), amount |
| **Total** | **218** | |

**Syria-specific features:**

```python
# Syria-specific engineered features
features = {
    # Amount features with SYP-specific handling
    'amount_syp': raw_amount,  # No decimals in SYP
    'amount_ratio_to_user_avg': amount / user_avg_amount,
    'amount_z_score': (amount - user_avg_amount) / user_std_amount,
    'amount_is_round': amount in [50000, 100000, 250000, 500000, 1000000],
    'amount_market_rate_ratio': amount / daily_syp_usd_rate,  # SYP volatility
    
    # Displacement-aware location features
    'distance_from_home_km': haversine(current_loc, user_home_loc),
    'is_conflict_zone_origin': user_home_region in displaced_regions,
    'location_change_is_plausible': check_plausible_movement(current_loc, user_history),
    
    # SIM and carrier features
    'sim_changed_recently_hours': hours_since_sim_change,
    'carrier_changed': is_different_carrier(user_usual_carrier, current_carrier),
    'is_dual_sim_user': user_has_multiple_sims,
    
    # Agent features
    'agent_trust_score': agent_90d_trust_score,
    'agent_float_ratio': agent_current_float / agent_expected_float,
    'agent_dispute_rate_30d': agent_disputes / agent_transactions_30d,
    'agent_distance_from_home_km': haversine(user_home, agent_location),
    
    # Behavioral features
    'hour_of_day': transaction_hour,
    'is_business_hours': 8 <= transaction_hour <= 20,
    'is_friday': is_friday,  # Friday is holiday
    'is_eid_season': is_within_eid_period,
    'days_since_last_txn': days_since_previous_transaction,
    'txn_type_mismatch': transaction_type != user_most_common_type,
}
```

### Handling Class Imbalance

Fraud is rare (target < 0.1% fraud rate). Training data is highly imbalanced:

| Technique | Application |
|-----------|-------------|
| Weighted loss function | Class weight: fraud=100, legitimate=1 |
| Oversampling (SMOTE) | Synthetic fraud samples in training |
| Undersampling legitimate | Random 10% of legitimate transactions |
| Stratified sampling | Maintain fraud ratio in validation set |
| Threshold tuning | Decision threshold tuned for precision @ desired recall |

### Feature Importance Monitoring

Top features by importance (example from Syria-trained model):

| Rank | Feature | Importance | Notes |
|------|---------|------------|-------|
| 1 | velocity_30min_txn_count | 0.142 | Strongest single predictor |
| 2 | amount_z_score | 0.098 | Amount vs user baseline |
| 3 | device_new_flag | 0.087 | New device indicator |
| 4 | agent_trust_score | 0.076 | Agent risk score |
| 5 | sim_change_hours_ago | 0.065 | Recent SIM swap |
| 6 | recipient_velocity_1h | 0.058 | Mule detection |
| 7 | is_emulator | 0.052 | Emulator detection |
| 8 | distance_from_home_km | 0.048 | Location anomaly |
| 9 | failed_logins_24h | 0.041 | Account takeover signal |
| 10 | days_since_last_txn | 0.034 | Irregular usage |

## Inference (Real-time Scoring)

### ONNX Runtime

```
┌─────────────────────────────────────────────────────────────────────┐
│                      REAL-TIME SCORING                              │
│                                                                     │
│  Transaction Event                                                  │
│       │                                                             │
│       ▼                                                             │
│  ┌──────────────────────┐                                           │
│  │ FeatureVector        │ ← PHP: extract 218 features from event   │
│  │ (associative array)  │                                           │
│  └──────────┬───────────┘                                           │
│             │                                                       │
│             ▼                                                       │
│  ┌──────────────────────┐                                           │
│  │ ONNXScorer           │ ← PHP FFI to ONNX Runtime                │
│  │                      │                                           │
│  │  • Loads model from  │                                           │
│  │    S3/storage        │                                           │
│  │  • Feature vector →  │                                           │
│  │    ONNX tensor       │                                           │
│  │  • Inference < 30ms │                                           │
│  │  • Returns prob     │                                           │
│  └──────────┬───────────┘                                           │
│             │                                                       │
│             ▼                                                       │
│  ┌──────────────────────┐                                           │
│  │ fraud_prob (0-1)    │ → fed into Scoring Engine                 │
│  └──────────────────────┘                                           │
└─────────────────────────────────────────────────────────────────────┘
```

### Inference Performance Targets

| Metric | Target | Measurement |
|--------|--------|-------------|
| P50 inference time | < 15ms | Per transaction |
| P99 inference time | < 50ms | Per transaction |
| Model load time | < 100ms | On cold start |
| Memory per inference | < 10MB | Batch of 1 |
| Throughput (single node) | > 1000 txns/sec | Concurrent |

## Feedback Loop

### False Positive → Model Improvement

```
User Appeal ✓ → Confirmed False Positive
                    │
                    ▼
          ┌────────────────────┐
          │ Feedback Queue      │
          │   (Redis stream)    │
          └─────────┬──────────┘
                    │
                    ▼
          ┌────────────────────┐
          │ Label Store        │  ← Updates label for transaction
          │ (fraud_decision    │     in training database
          │  table)            │
          └─────────┬──────────┘
                    │
                    ▼
          ┌────────────────────┐
          │ Next Daily Train   │  ← Included in next training batch
          │ includes corrected │
          │ labels             │
          └────────────────────┘
```

### Retraining Trigger Conditions

| Condition | Action |
|-----------|--------|
| Daily scheduled (03:00) | Standard retraining |
| AUC drops below 0.85 | Emergency retraining immediately |
| FP rate > 5% for 2h | Trigger retraining with corrected labels |
| New fraud pattern identified | Retrain with added features |
| Model version older than 7 days | Force retrain (feature drift) |

## Model Versioning

```
Model Version Format: v{major}.{minor}.{patch}-{date}

Example: v1.2.4-20250314

v1 = Major architecture (LightGBM + Transformer ensemble)
.2 = Feature set version
.4 = Training run number for this feature set
-20250314 = Training date

Storage:
- Each model version saved as ONNX file
- Metadata stored in fraud_ml_models table
- Previous 10 versions kept for rollback
- Shadow-deployed versions are v{major}.{minor}.{patch}-shadow
```

## Model Governance

| Requirement | Implementation |
|-------------|---------------|
| All model versions tracked | fraud_ml_models table with metrics |
| Explainability | SHAP values computed and stored for critical decisions |
| Fairness testing | AUC per region, per KYC level, per gender (if available) |
| Bias monitoring | False positive rate parity across demographic segments |
| Approval process | Model deploy requires data scientist + fraud manager |
| Rollback capability | One-click rollback to any of last 10 versions |
| Audit trail | All model changes logged with who, what, when |
