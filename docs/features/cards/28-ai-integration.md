# Cards AI Integration

## AI Use Cases

### 1. Fraud Detection (Real-time Transaction Scoring)
```
Input: Transaction data (amount, merchant, country, MCC, time) + card history + device fingerprint
Model: XGBoost ensemble (inference < 20ms)
Features:
  - Transaction amount vs user's average (ratio)
  - Time since last transaction
  - Distance from last transaction (impossible travel)
  - Merchant category frequency for user
  - Card velocity (txns in last 5min/1h/24h)
  - Cross-card velocity (txns across user's cards in 1h)
  - BIN attack score (similar txns across different PANs)
  - Device fingerprint match score
  - CVV match (valid/invalid)
  - 3DS authentication result
  - Merchant risk score (pre-computed merchant reputation)
  - Country risk score (sanctions, fraud rates)
Output: Risk score (0-100) + top 3 reason codes
Integration:
  - Synchronous: Every auth calls fraud service before CardProcessor decision
  - Threshold: Score > 70 → decline, 40-70 → 3DS challenge required
  - Score < 40 → approve (with velocity checks)
  - Feedback loop: Manual review results + chargebacks retrain model weekly
```

### 2. Spending Pattern Analysis
```
Input: 90 days of card transactions per user
Model: Lightweight clustering (transaction categorization + anomaly detection)
Output:
  - Category breakdown (Food, Transport, Shopping, Subscriptions, ATM)
  - Monthly spending trend with percentage change
  - Top 5 merchants by spend
  - Subscription detection (recurring charges detected)
  - Unusual merchant type alert ("You spent at a travel agency — first time!")
  - Spending pattern change detection ("Your spending doubled this week")
Integration:
  - Batch job: Runs daily at 02:00
  - Results cached in Redis: TTL 24h
  - Displayed on card insights tab
  - Trigger push notification for significant anomalies

Model Details:
  - Category classification: Logistic regression (MCC → category)
  - Anomaly detection: Isolation Forest (spending amount + frequency)
  - Subscription detection: Rule-based (same merchant + same amount monthly)
  - Pattern change: Compare rolling 7-day vs 30-day average
```

### 3. Limit Recommendation
```
Input: User transaction history (90 days), KYC level, card type, spending categories
Model: Statistical model (mean + 2σ per category) + user segmentation
Output: Personalized per-category limit suggestions
  - Online: Historical max + 20% buffer (capped by KYC max)
  - POS: Average weekly + 50% buffer
  - ATM: Average withdrawal + 100% buffer
  - International: Only if prior international transactions

Integration:
  - Recalculated weekly via cron job
  - Stored in card_limit_recommendations table
  - Displayed as banner on limit settings page:
    "نقترح زيادة حد التسوق الإلكتروني إلى 750,000 ل.س بناءً على تاريخ معاملاتك"
  - User can apply suggestion with one tap
  - Lower-bound protection: Never recommend lower than current limit

User Segments for Recommendations:
  - Conservative: < 5 txns/week → recommend lower buffer (10%)
  - Regular: 5-20 txns/week → standard model (20% buffer)
  - Heavy: > 20 txns/week → higher buffer (30%)
  - Inactive: No txns in 30d → suggest reducing limits temporarily
```

### 4. Card Churn Prediction
```
Input: 60 days of card activity + user profile + app engagement
Model: Gradient Boosted Trees (churn probability)
Features:
  - Days since last card transaction
  - Transaction count (weekly trend)
  - Spending volume (weekly trend)
  - Card freeze/unfreeze frequency
  - Failed transaction count (last 30 days)
  - Support tickets related to card
  - App login frequency
  - Other Beza product usage (wallet, savings)
  - Card type (virtual vs physical)
  - Card age
  - Negative sentiment in support interactions (NLP)
Output: Churn probability (0-100%) + top 3 drivers
Integration:
  - Batch job: Runs weekly
  - Segments: High risk (>60%), Medium risk (30-60%), Low risk (<30%)

Interventions by Risk Level:
  High Risk (>60%):
    - Push notification: "عرض خاص! اشحن بطاقتك واحصل على 5,000 ل.س مجاناً"
    - SMS: "بطاقتك لم تستخدم منذ فترة — قم بالشراء اليوم"
    - In-app offer: "أول 3 معاملات بدون رسوم"
    - Support outreach if premium user

  Medium Risk (30-60%):
    - In-app banner: "اكتشف مميزات بطاقتك الجديدة"
    - Feature highlight: One-time card feature reminder
    - Spending insight notification

  Low Risk (<30%):
    - No intervention (maintain normal engagement)

Model Retraining:
  - Frequency: Monthly with new churn labels
  - Evaluation: AUC > 0.80, precision > 0.70 at top decile
  - Champion/challenger: Run new model alongside current for 2 weeks
```

## Data Requirements

### Training Data Sources
```
Data Source              Access Frequency    Retention
────────────             ────────────────    ────────
Card Transactions        Real-time stream    24 months
Auth Declines            Real-time stream    12 months
Card Status Changes      Real-time stream    24 months
Chargebacks              Daily batch         24 months
Support Tickets          Real-time           24 months
User Profile (KYC)       Daily batch         Permanent
Fraud Manual Reviews     Daily batch         24 months
Device Fingerprints      Real-time           12 months
```

### Feature Store
```python
# Feature definitions for online inference
features = {
    # Card-level features
    "card_age_days": "DATEDIFF(NOW(), issued_at)",
    "card_txn_count_7d": "COUNT(*) FROM card_transactions WHERE created_at > NOW() - 7d",
    "card_spend_30d": "SUM(amount) FROM card_transactions WHERE created_at > NOW() - 30d",
    "card_freeze_count_90d": "COUNT(*) FROM card_status_log WHERE status='frozen' AND created_at > NOW() - 90d",

    # User-level features
    "user_kyc_level": "users.kyc_level",
    "user_txn_count_all_30d": "COUNT(*) FROM card_transactions WHERE user_id=X AND created_at > NOW() - 30d",
    "user_login_frequency": "COUNT(*) FROM auth_logs WHERE user_id=X AND created_at > NOW() - 30d / 30",

    # Transaction-level features
    "txn_amount_vs_avg": "amount / AVG(amount) OVER (WHERE card_id=X AND status='settled' AND created_at > NOW() - 90d)",
    "txn_time_since_last": "TIMESTAMPDIFF(MINUTE, last_txn.created_at, NOW())",
    "txn_velocity_5min": "COUNT(*) FROM card_transactions WHERE card_id=X AND created_at > NOW() - 5m",
    "merchant_risk_score": "merchant_risk_scores.score WHERE merchant_id = txn.merchant_id",
}
```
