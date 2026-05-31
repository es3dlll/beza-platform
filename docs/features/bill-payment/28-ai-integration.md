# Bill Payment AI Integration

## AI Use Cases

### 1. Bill Amount Prediction (Proactive Balance Check)
```
Input: User's bill payment history (12 months), biller type, month of year
Model: Seasonal ARIMA + linear regression
Output: Predicted bill amount for next period
  - PEED: predicted 42,000–48,000 SYP (summer higher due to AC usage)
  - Water: predicted 8,000–12,000 SYP (stable)
  - Syriatel: predicted 33,000 SYP (fixed plan, minimal variance)

Integration:
  - Runs monthly: 5th of each month
  - Results stored in user_bill_predictions table
  - Displayed on bill home: "فاتورة الكهرباء المتوقعة الشهر القادم: 44,000-48,000 ل.س"
  - Used for proactive notifications: "قد لا يكفي رصيدك لفاتورة الكهرباء — قم بشحن المحفظة"
  - Threshold: if predicted > 80% of wallet balance → send alert

Accuracy Target: ±15% for variable bills (electricity, water)
```

### 2. Smart Due Date Prediction
```
Input: Historical payment dates per biller per customer ID
Model: Simple pattern recognition (day-of-month clustering)
Output: Predicted due date range

Example:
  Ahmad's PEED bill: historically due between 12th–18th of each month
  → Predict: next due around July 13–16
  → Set reminder: July 10 (3 days before earliest expected)

Integration:
  - When user first fetches a bill, check if due date follows pattern
  - If pattern found: auto-suggest reminder date
  - If no pattern (first time): use biller's stated due date cycle
  - On each subsequent fetch: refine prediction

Benefit: Reduces late payments for users who don't know their due schedule
```

### 3. Anomalous Bill Detection
```
Input: Bill amount, customer ID, biller type, user history, regional averages
Model: Isolation Forest (unsupervised anomaly detection)
Output: Anomaly score (0-100) + reason codes

Example Anomalies:
  - PEED bill of 120,000 SYP vs normal 42,000 SYP (user's history)
    → Possible: meter reading error, tariff change, or fraud
    → Action: Flag bill with "هذه الفاتورة أعلى من المعتاد — يرجى التأكد من القراءة"
  - Water bill of 50,000 SYP vs normal 9,000 SYP
    → Possible: leak detected on meter (helpful alert for user)
  - Syriatel bill of 150,000 SYP vs normal 33,000 SYP
    → Possible: international call fraud or SIM cloning
    → Action: Flag + suggest user contact Syriatel

Integration:
  - Synchronous: Score every fetched bill before displaying to user
  - If score > 70: Show warning banner on bill detail screen
  - If score > 85: Require additional confirmation before payment
```

### 4. Bill Payment Pattern Insights (Personalized)
```
Input: 12 months of bill payment history per user
Model: Simple clustering + trend analysis
Output: Monthly insights card

Example:
  "لقد دفعت 312,500 ل.س للفواتير هذا الشهر — أعلى بنسبة 15% من الشهر الماضي"
  "فاتورة الكهرباء هذا الشهر أقل بنسبة 22% مقارنة بنفس الشهر من العام الماضي"
  "تستطيع توفير 500 ل.س شهرياً بالتحول إلى الفاتورة الإلكترونية بدلاً من الورقية"
  "متوسط دفعك للفواتير في ازدياد — قم بتعيين ميزانية شهرية للفواتير"

Integration:
  - Batch job: Runs monthly (1st of month)
  - Results cached in Redis: TTL 30 days
  - Displayed on bill home screen as insight cards
```

### 5. Optimal Auto-pay Timing
```
Input: User's wallet funding patterns, bill due dates, balance history
Model: Simple heuristic + reinforcement learning (Phase 2)
Output: Recommended auto-pay day

Example:
  User typically gets salary on 5th of month
  PEED due: 15th
  → Recommend: auto-pay on 12th (after salary + 7 days buffer)
  → If insufficient on 12th: retry daily until sufficient

Integration:
  - Shown during auto-pay setup
  - Dynamic: adjusts if user's funding pattern changes
  - Fallback: use due date if no pattern detected

Benefit: Reduce auto-pay failures by 30%+
```

### 6. Customer ID Auto-complete (OCR + ML)
```
Input: Photo of smart meter, paper bill, or customer card
Model: Lightweight OCR (Tesseract + post-processing)
Output: Extracted customer ID

Integration:
  - Camera button on customer ID entry screen
  - OCR reads the 24-digit PEED meter number / 10-digit water sub number
  - Post-processing: validates check digit, corrects common OCR errors
  - User confirms extracted ID before fetching

Benefit: Reduce manual entry errors by 60%
```

## ML Model Deployment

### Bill Amount Prediction Pipeline
```
Training (Monthly, 1st of month):
  1. Extract: 12 months of user bill transactions
  2. Feature engineering: biller type, month, user region, previous amount
  3. Train: Seasonal ARIMA per biller category
  4. Evaluate: MAE < 5,000 SYP for electricity, < 2,000 SYP for water
  5. Deploy: Model weights stored in S3, loaded on demand

Inference (On-demand):
  1. Fetch user's last 12 months of payments for this biller
  2. Run Seasonal ARIMA with monthly seasonality
  3. Return: predicted amount + confidence interval (80% CI)
  4. Cache: TTL 24 hours
```

### Anomaly Detection Pipeline
```
Training (Weekly):
  1. Extract: All bill transactions from the past 3 months
  2. Feature engineering: amount, amount/avg, day_of_month, biller, region
  3. Train: Isolation Forest (contamination = 0.02 = 2% anomalies expected)
  4. Evaluate: Precision > 90%, Recall > 80%
  5. Deploy: ONNX model to inference service

Inference (Real-time, per bill fetch):
  1. Pre-process: normalize amount by biller/region averages
  2. Score: Isolation Forest anomaly score
  3. Post-process: apply rule overrides
     - First bill ever for this ID: lower threshold (don't flag new customers)
     - Commercial tariff (electricity): higher threshold
  4. Return: anomaly_score (0-100), reason_codes
  5. Threshold: score > 70 → warning banner, score > 85 → block payment
```

## AI Service API

### Bill Prediction Endpoint
```http
POST /internal/ai/bill-predict
Content-Type: application/json

{
  "user_id": 42,
  "biller_type": "peed",
  "customer_id": "123456789012345678901234"
}
```

```json
{
  "predicted_amount": 44500,
  "confidence_interval": {
    "lower": 38000,
    "upper": 51000
  },
  "confidence_level": 0.8,
  "based_on_months": 9,
  "seasonal_pattern": "summer_peak",
  "next_due_estimated": "2026-07-15",
  "model_version": "bill-pred-v1.2"
}
```

### Anomaly Detection Endpoint
```http
POST /internal/ai/bill-anomaly
Content-Type: application/json

{
  "bill": {
    "amount": 120000,
    "biller_type": "peed",
    "customer_id": "123456789012345678901234",
    "billing_period": "يونيو 2026"
  },
  "user": {
    "id": 42,
    "avg_bill_amount": 42500,
    "num_previous_bills": 18,
    "months_with_biller": 12
  },
  "context": {
    "region_avg": 43800,
    "biller_avg": 41500,
    "tariff": "residential",
    "season": "summer"
  }
}
```

```json
{
  "anomaly_score": 87,
  "is_anomaly": true,
  "severity": "high",
  "reasons": [
    "amount_3x_above_user_average",
    "amount_2_5x_above_region_average",
    "sudden_spike_vs_last_month"
  ],
  "action": "flag_for_review",
  "model_version": "anomaly-v2.1",
  "inference_time_ms": 8
}
```
