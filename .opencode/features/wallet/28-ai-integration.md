# Wallet AI Integration

## AI Use Cases

### 1. Fraud Detection (Real-time)
```
Input: Transaction data + device fingerprint + user behavior
Model: XGBoost ensemble (inference < 50ms)
Output: Risk score (0-100) + reason codes
Integration:
  - Synchronous: Every transfer calls fraud service before CFE
  - Threshold: Score > 70 → block, 40-70 → step-up auth
  - Feedback loop: Manual review results retrain model daily
```

### 2. Spending Insights (Daily)
```
Input: 90 days of transaction history
Model: Lightweight clustering (transaction categorization)
Output: 
  - Category breakdown (Food, Transport, Bills, etc.)
  - Monthly spending trend
  - Top spending categories
  - Unusual spending alert
Integration:
  - Batch job: Runs daily at 02:00
  - Results cached in Redis: TTL 24h
  - Displayed on wallet insights tab
```

### 3. Personalized Limits (Dynamic)
```
Input: User transaction history (90 days), KYC level, behavior patterns
Model: Simple statistical model (mean + 3σ)
Output: Dynamic per-transaction limit
  - Conservative user: Standard KYC limits apply
  - Established user (90d history, clean): 2x standard limit
  - High-risk user: 0.5x standard limit
Integration:
  - Recalculated daily via cron job
  - Stored in wallet_dynamic_limits table
  - Overrides static limits when more permissive (only up)
```

### 4. Smart Notifications (Push)
```
Input: User behavior patterns, time of day, location
Model: Rule-based + simple ML ranking
Output: Prioritized notifications
  - High priority: Large incoming transfer, bill due today
  - Medium: Savings goal progress, spending insight
  - Low: Promotional
  - Suppressed: Irrelevant notifications (user hasn't opened in 30d)
Integration:
  - Notification worker checks user preferences + behavior
  - Rate-limited: max 2 push notifications per hour
```

### 5. Agent Recommendation
```
Input: User location, time of day, past agent visits
Model: Distance-based + popularity scoring
Output: Top 3 nearest agents with:
  - Distance, operating status, estimated wait time
  - User rating, available services (cash-in/cash-out)
Integration:
  - Server-side: Location query → filter by distance + status
  - ML: Sort by combined score (distance × rating × availability)
```

## ML Model Deployment

### Fraud Model Pipeline
```
Training (Daily, 01:00 AM):
  1. Extract: 90 days of transactions + outcomes (legit vs fraud)
  2. Feature engineering: 120+ features (amount, velocity, device, etc.)
  3. Train: XGBoost with balanced class weights
  4. Evaluate: Precision > 95%, Recall > 90% on holdout set
  5. Export: ONNX format
  6. Deploy: Canary for 10% traffic → monitor 1h → full rollout

Inference (Real-time):
  1. Pre-process: Transform raw data → feature vector
  2. Load model: ONNX Runtime session
  3. Predict: single transaction scored
  4. Post-process: Apply rule overrides (sanctions, blacklist)
  5. Return: score + decision + reason codes
```

## AI Service API
```http
POST /internal/ai/fraud-score
Content-Type: application/json

{
  "transaction": {
    "amount": 2500000,
    "currency": "SYP",
    "type": "send"
  },
  "sender": {
    "id": 42,
    "kyc_level": 1,
    "account_age_days": 45,
    "avg_transaction_amount": 35000,
    "daily_transaction_count": 8,
    "monthly_transaction_count": 45
  },
  "device": {
    "id": "device_abc",
    "is_trusted": true,
    "rooted": false,
    "first_seen_days": 40,
    "ip_country": "SY",
    "is_vpn": false
  },
  "recipient": {
    "id": 87,
    "kyc_level": 1,
    "account_age_days": 120,
    "received_from_sender_count": 12,
    "received_from_sender_total": 350000
  },
  "context": {
    "hour": 10,
    "day_of_week": 3,
    "distance_from_last_txn_km": 2.5
  }
}
```

```json
{
  "score": 15,
  "decision": "allow",
  "risk_level": "low",
  "reasons": [
    "regular_pattern",
    "known_recipient",
    "trusted_device"
  ],
  "model_version": "fraud-v2.3",
  "inference_time_ms": 12
}
```
