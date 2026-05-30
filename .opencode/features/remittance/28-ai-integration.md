# Remittance AI Integration

## AI Use Cases

### 1. Fraud Detection (Real-time)

#### Velocity Analysis
```
Input: Remittance transaction data + device fingerprint + user behavior
Model: XGBoost ensemble (inference < 50ms)
Output: Risk score (0-100) + reason codes

Features (120+):
  Sender features:
    - Time since last remittance
    - Number of remittances in last 1h/6h/24h
    - Average remittance amount (30 days)
    - Standard deviation of remittance amounts
    - Ratio of current amount to average
    - Number of unique beneficiaries (7d/30d)
    - Device change frequency
    - IP country mismatch with registered country
    - Time of day of transaction
    - Login-to-send time delta

  Recipient features:
    - New recipient (never received before)
    - Recipient account age
    - Number of senders sending to this recipient (24h)
    - Recipient KYC level
    - Recipient wallet velocity (incoming txns per hour)

  Corridor features:
    - Corridor historical fraud rate
    - Deviation from normal corridor patterns
    - FX rate movement since last transaction

Integration:
  - Synchronous: Every remittance calls fraud service before CFE
  - Threshold: Score > 75 → block, 40-75 → step-up auth, < 40 → allow
  - Feedback loop: Manual review results retrain model daily
```

#### New Recipient Pattern Detection
```
Model: Isolation Forest (unsupervised anomaly detection)
Purpose: Detect when a sender sends to a new recipient with unusual patterns

Features:
  - Amount to new recipient vs sender's historical average
  - Time of day for first transfer to new recipient
  - Device used for first transfer to new recipient
  - Geographic distance between sender and new recipient (phone country code)
  
Detection:
  - If sender has 10+ transfers to same beneficiary over 6 months
  - Then suddenly sends to a NEW beneficiary with:
    - Amount > 2x historical average
    - Different time of day (e.g., 3 AM)
    - Different device
  → Score: 65+ → step-up auth required
  → Score: 85+ → block and manual review
```

#### Unusual Amount Pattern Detection
```
Model: Statistical outlier detection (Z-score + IQR)
Purpose: Detect amounts that deviate significantly from sender's normal behavior

Pattern 1: Round Amount Structuring
  - Sender normally sends €200-250
  - Suddenly sends €1,000, €950, €980 (below $1K threshold)
  → Flag as potential structuring → AML review

Pattern 2: Amount Inflation
  - Sender normally sends €200/month for 12 months
  - Suddenly sends €5,000
  → Flag for source of funds check → EDD

Pattern 3: Rapid Escalation
  - First transfer: €100
  - Second transfer (1 day later): €500
  - Third transfer (same day): €1,000
  → Flag as potential account takeover → step-up auth

Implementation:
  - Z-score > 3: flag for review
  - Combined velocity + amount anomaly: auto-block
  - Historical baseline: rolling 90-day window
```

### 2. Diaspora Behavior Modeling for Personalized Limits

#### Dynamic Limit Model
```
Input: User transaction history (90 days), KYC level, corridor history
Model: Gradient Boosting Regressor
Output: Dynamic per-user per-corridor limits

Features:
  - Account age (days)
  - Total remittance volume (all time)
  - Average monthly remittance volume
  - Remittance frequency (avg days between transfers)
  - Beneficiary count (unique)
  - Beneficiary relationship diversity
  - Failed transfer count
  - Chargeback/dispute count
  - KYC level
  - Device trust score
  - Login consistency (same device, same location)
  - Corridor history (how many different corridors used)
  - Recurring transfer adoption (yes/no)
  - Source of funds declared

Output:
  - Recommended daily limit (e.g., €3,000 instead of standard €2,000)
  - Recommended monthly limit (e.g., €30,000 instead of €20,000)
  - Risk tier: low / medium / high / very high

Personalization Examples:
  - Khalid (Berlin, 18 months on Beza, 24 transfers, €7,200 total, clean history):
    → Standard: €2,000/day → AI recommends: €3,500/day (1.75x)
  
  - New user (1 week, first transfer €500):
    → Standard: €2,000/day → AI recommends: €200/day (restricted)
  
  - Fatima (Stockholm, recurring user, 12 recurring transfers executed):
    → Standard: €1,500/day → AI recommends: €2,500/day (proven track record)
  
  - Flagged user (1 dispute, 1 failed transfer, irregular amounts):
    → Standard: €1,500/day → AI recommends: €500/day (restricted)
```

#### Corridor Risk Scoring
```
Model: Logistic Regression per corridor
Purpose: Assign risk scores to corridors for dynamic pricing and limits

Features per corridor:
  - Total volume (30 days)
  - Fraud rate (30 days)
  - Chargeback rate
  - Average remittance size
  - Regulatory environment score (1-10)
  - Correspondent bank reliability
  - FX volatility (30-day standard deviation)

Output:
  - Corridor risk score (1-100)
  - Recommended FX spread adjustment
  - Recommended limit multiplier

Example Corridor Risk Scores:
  EUR_DE->SYP:    12/100 (low risk)  → 1.0x standard spread
  USD_US->SYP:    25/100 (low risk)  → 1.0x standard spread
  TRY_TR->SYP:    55/100 (medium)    → 1.3x standard spread
  USD_AE->SYP:    35/100 (low)       → 1.1x standard spread
```

### 3. Smart Remittance Notifications

```
Model: Rule-based + ML ranking
Input: User behavior patterns, corridor features, time of day
Output: Prioritized notifications

High Priority:
  - Remittance delivered to recipient → immediate push
  - Recurring transfer failed → immediate push + SMS
  - FX rate significantly better than usual → push (if user sends regularly)
  - Beneficiary not on Beza → reminder to invite them

Medium Priority:
  - Monthly remittance summary → once per month
  - New corridor available (e.g., "يمكنك الآن الإرسال من بريطانيا") → once
  - Beneficiary anniversary (1 year of sending) → celebratory

Low Priority:
  - FX rate of the day → daily (if user has notifications enabled)
  - Referral program: invite friend, get €5 off next transfer
  - Loyalty points earned

Suppressed:
  - User hasn't sent in 90+ days → suppress all non-critical
  - User has sent > 3 times this week → reduce frequency
  - User has explicitly disabled certain notification types

Integration:
  - Notification worker checks user preferences + behavior
  - Rate-limited: max 3 push notifications per hour
  - SMS: critical only (delivery confirmations, failures)
```

### 4. FX Rate Prediction (For Treasury)

```
Model: LSTM time series (for treasury hedging decisions)
Input: Historical FX rates (SYP/EUR, SYP/USD), 90 days
Output: Predicted rate range for next 24h/7d

Use Case:
  - Treasury decides when to hedge EUR→SYP conversions
  - If model predicts SYP strengthening: delay conversion
  - If model predicts SYP weakening: convert immediately
  - Not real-time user-facing — internal treasury tool

Not used for:
  - User-facing rate quotes (always market-based)
  - Rate lock pricing
```

### 5. Recurring Transfer Amount Recommendation

```
Model: Simple statistical model (median + trend)
Purpose: Suggest optimal recurring amount based on historical behavior

When user creates recurring:
  - Look at average monthly remittance to this beneficiary: €250
  - Look at trend (increasing/decreasing): stable
  - Recommend: €250/month

When user's behavior changes:
  - User has been sending €300 instead of €200 for 3 months
  - Suggest: "هل ترغب في تحديث التحويل الدوري إلى 300 يورو؟"
  - If accepted: update recurring amount
```

## ML Model Deployment

### Fraud Model Pipeline
```
Training (Daily, 01:00 AM UTC):
  1. Extract: 90 days of remittances + outcomes (legit vs confirmed fraud)
  2. Feature engineering: 120+ features (amount, velocity, device, beneficiary, corridor)
  3. Train: XGBoost with balanced class weights
  4. Evaluate: Precision > 95%, Recall > 90% on holdout set
  5. Export: ONNX format
  6. Deploy: Canary for 10% traffic → monitor 1h → full rollout

Inference (Real-time):
  1. Pre-process: Transform raw remittance data → feature vector
  2. Load model: ONNX Runtime session
  3. Predict: single remittance scored
  4. Post-process: Apply rule overrides (sanctions, blacklist, corridors)
  5. Return: score + decision + reason codes
```

## AI Service API

```http
POST /internal/ai/remittance-fraud-score
Content-Type: application/json

{
  "remittance": {
    "amount": 50000,
    "source_currency": "USD",
    "target_currency": "SYP",
    "type": "diaspora",
    "corridor": "USD_US->SYP"
  },
  "sender": {
    "id": 42,
    "kyc_level": 2,
    "account_age_days": 180,
    "country": "DE",
    "avg_remittance_amount": 25000,
    "avg_remittance_currency": "EUR",
    "daily_remittance_count": 1,
    "monthly_remittance_count": 2,
    "total_remittances": 24,
    "total_volume": 720000,
    "unique_beneficiaries": 2,
    "failed_transfer_count": 0,
    "dispute_count": 0,
    "recurring_user": true
  },
  "device": {
    "id": "device_abc",
    "is_trusted": true,
    "rooted": false,
    "first_seen_days": 170,
    "ip_country": "DE",
    "is_vpn": false,
    "ip_blacklisted": false
  },
  "recipient": {
    "id": 92,
    "kyc_level": 1,
    "account_age_days": 365,
    "received_from_sender_count": 22,
    "received_from_sender_total": 55000000,
    "incoming_velocity_1h": 0,
    "incoming_velocity_24h": 1
  },
  "context": {
    "hour": 10,
    "day_of_week": 3,
    "time_since_last_login_min": 2,
    "time_since_last_remittance_hours": 720
  }
}
```

```json
{
  "score": 8,
  "decision": "allow",
  "risk_level": "low",
  "reasons": [
    "regular_pattern",
    "known_beneficiary",
    "trusted_device",
    "consistent_amount"
  ],
  "model_version": "remittance-fraud-v3.1",
  "inference_time_ms": 15
}
```

```json
// High-risk example response
{
  "score": 82,
  "decision": "step_up",
  "risk_level": "high",
  "reasons": [
    "first_transfer_to_beneficiary",
    "amount_3x_above_average",
    "new_device",
    "time_anomaly_0300_local"
  ],
  "required_actions": ["biometric", "sms_otp", "source_of_funds"],
  "model_version": "remittance-fraud-v3.1",
  "inference_time_ms": 18
}
```
