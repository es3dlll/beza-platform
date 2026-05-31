# Merchant AI Integration

## AI Use Cases

### 1. Merchant Fraud Detection (Real-time)
```
Input: Transaction data + merchant profile + customer behavior
Model: XGBoost ensemble (inference < 50ms)
Output: Risk score (0-100) + reason codes

Merchant-specific features:
  - Transaction velocity (txns per minute)
  - Amount deviation from merchant's average (z-score)
  - Customer diversity (unique payers per hour)
  - Refund rate (rolling 7-day)
  - Time of day distribution (unusual hours)
  - Geographic mismatch (payer location vs merchant location)
  - Device fingerprint clustering (same device, multiple "customers")
  - QR scan-to-payment ratio (scans without payment)
  - Payment link conversion rate (created vs paid)

Integration:
  - Synchronous: Every payment calls fraud service before CFE
  - Threshold: Score > 80 → block, 50-80 → step-up (OTP for customer)
  - Feedback loop: Manual review results retrain model daily
  - Alerts: Score > 90 immediately notifies compliance team
```

### 2. Dynamic MDR Pricing Based on Volume
```
Input: Merchant transaction history (90 days), monthly volume, chargeback rate
Model: Linear regression + rule engine
Output: Dynamic MDR rate adjustment (per merchant, monthly)

Pricing tiers based on monthly TPV:
  Tier 1 (< 100K SYP/mo): Standard rate (QR 1.5%, POS 2.0%)
  Tier 2 (100K-500K SYP/mo): Standard rate
  Tier 3 (500K-2M SYP/mo): -0.10% discount (QR 1.4%, POS 1.9%)
  Tier 4 (2M-10M SYP/mo): -0.25% discount (QR 1.25%, POS 1.75%)
  Tier 5 (> 10M SYP/mo): Negotiated rate (as low as QR 0.8%, POS 1.2%)

Risk-based adjustment:
  Low risk (fraud score < 20): -0.05%
  Medium risk (20-50): Standard
  High risk (50-80): +0.25%
  Very high risk (> 80): +0.50%

Integration:
  - Recalculated monthly on day 1
  - Merchant notified: "تم تخفيض رسومك إلى 1.25% — أحسنت!"
  - Applied to all transactions for the month
  - Can be overridden by sales team for strategic merchants
```

### 3. Merchant Churn Prediction
```
Input: Merchant activity signals (daily)
Model: Gradient boosting (weekly batch)
Output: Churn probability (0-100%) + top 3 risk factors

Churn signals:
  - 7 consecutive days with 0 transactions (previously active)
  - Transaction volume drop > 50% vs monthly average
  - Payment link creation rate drop > 70%
  - Customer complaints (from support tickets)
  - Competitor activity in merchant's area
  - Refund rate spike (> 30%)
  - POS terminal offline for > 5 days
  - Webhook failures increasing
  - App login frequency decreasing
  - Settlement withdrawal frequency dropping

Risk tiers:
  Low (< 20%): No action
  Medium (20-50%): Send engagement campaign
  High (50-80%): Proactive outreach (call or field visit)
  Critical (> 80%): Immediate retention offer (fee discount, free POS upgrade)

Integration:
  - Batch job runs daily at 03:00
  - Output: List of at-risk merchants with scores + reasons
  - CRM system gets push notification for high-risk merchants
  - Automated engagement: "عروض خاصة لتاجرنا العزيز — خصم 20% على الرسوم"
  - Field agent dispatched for critical merchants
```

### 4. Category-Based Spending Predictions
```
Input: Merchant transaction data + weather + time of year
Model: Time series (Prophet / ARIMA)
Output: Predicted daily/weekly/monthly volume per merchant category

Predictions by business type:
  Grocery: Predict daily sales based on day of week, holidays, weather
  Restaurant: Predict lunch vs dinner split, peak hours
  Retail: Predict seasonal spikes (Eid, Ramadan, back-to-school)
  Fruit/Vegetables: Predict based on harvest season, weather

Integration:
  - Daily prediction at 06:00 for current day
  - Displayed in merchant dashboard: "من المتوقع أن تبلغ مبيعات اليوم 350,000 ل.س"
  - Alert if actual > 2x predicted: Possible fraud or stock issue
  - Alert if actual < 0.5x predicted: Possible churn risk
  - Helps merchants plan inventory and staffing
```

### 5. Intelligent Payment Link Timing
```
Input: Customer behavior, time of day, merchant category
Model: Simple ML ranking + rule engine
Output: Optimal time to send payment link for maximum conversion

Pattern learning:
  - Restaurant: Payment links convert best 11:00-13:00 and 19:00-21:00
  - Retail: Convert best 10:00-12:00 and 16:00-18:00
  - Grocery: Convert best morning 08:00-10:00
  - General: Weekend links have 20% higher conversion

Integration:
  - When merchant creates link, show: "أفضل وقت للإرسال: 10:00 صباحاً"
  - Auto-schedule: Merchant can set "أرسل في 10:00 صباحاً" 
  - A/B test timing for each merchant to find personal optimal time
```

## ML Model Deployment

### Fraud Model Pipeline
```
Training (Daily, 02:00 AM):
  1. Extract: 90 days of merchant transactions + outcomes (legit vs fraud)
  2. Feature engineering: 150+ features
     - Merchant features: age, tier, txn velocity, refund rate
     - Transaction features: amount, method, time, device
     - Customer features: new/existing, distance, history with merchant
  3. Train: XGBoost with balanced class weights (fraud is < 0.5% of txns)
  4. Evaluate: Precision > 95%, Recall > 90% on holdout set
  5. Export: ONNX format
  6. Deploy: Canary for 5% traffic → monitor 2h → full rollout

Inference (Real-time):
  1. Pre-process: Transform raw data → feature vector
  2. Load model: ONNX Runtime session
  3. Predict: Single transaction scored
  4. Post-process: Apply rule overrides (blacklist, known patterns)
  5. Return: score + decision + reason codes
```

### Churn Model Pipeline
```
Training (Weekly, Sunday 03:00):
  1. Extract: 6 months of merchant activity
  2. Features: 80+ features (frequency, volume, engagement, support tickets)
  3. Labels: Churned (no activity for 30 days) vs Active
  4. Train: LightGBM
  5. Deploy: Updated weekly

Inference (Daily, 03:00):
  1. Score all active merchants
  2. Top 10% highest risk → action queue
  3. Update CRM with risk scores
```

## AI Service API
```http
POST /internal/ai/merchant-fraud-score
Content-Type: application/json

{
  "transaction": {
    "amount": 4500000,
    "currency": "SYP",
    "method": "qr",
    "qr_type": "static"
  },
  "merchant": {
    "id": 42,
    "tier": "small",
    "age_days": 180,
    "avg_daily_volume": 850000,
    "avg_txn_amount": 35000,
    "refund_rate_7d": 2.1,
    "daily_txn_count": 12
  },
  "customer": {
    "id": 55,
    "is_new": false,
    "txn_count_with_merchant": 15,
    "avg_amount_with_merchant": 32000
  },
  "context": {
    "hour": 22,
    "day_of_week": 6,
    "seconds_since_last_merchant_txn": 30,
    "unique_customers_today": 8
  }
}
```

```json
{
  "score": 72,
  "decision": "step_up_auth",
  "risk_level": "high",
  "reasons": [
    "amount_anomaly_high",        // 4.5M vs avg 35K — 128x normal
    "unusual_hour",               // 22:00 — merchant typically closes at 20:00
    "rapid_velocity",             // 30 seconds since last txn
    "high_unique_customers"       // 8 unique customers today (avg 5)
  ],
  "model_version": "merchant-fraud-v1.5",
  "inference_time_ms": 18
}
```
