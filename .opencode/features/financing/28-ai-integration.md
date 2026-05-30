# تكامل الذكاء الاصطناعي — AI & Machine Learning Integration

## Overview
Machine learning powers four critical functions in the Financing module:
1. Credit scoring (XGBoost model)
2. Default prediction (before first missed payment)
3. Optimal offer generation (amount + rate for maximum acceptance)
4. Collection prioritization (which accounts to contact first)

---

## 1. Credit Scoring Model

### Model Architecture
```yaml
model:
  type: XGBoost Classifier (regression for score)
  framework: XGBoost 2.0+ (Python)
  input_features: 80+
  output: Score 300-850 (scaled from probability)
  training_frequency: Monthly
  inference_latency: < 500ms
  
  training_data:
    source: PostgreSQL (financing_credit_scores + wallet transactions)
    window: Rolling 24 months
    min_samples: 10,000 active users
    validation_split: 80/20
```

### Feature Engineering (80+ Features)

#### Transaction Features (25 features)
| Feature | Description | Engineering |
|---------|-------------|-------------|
| tx_count_30d | Number of transactions in last 30 days | COUNT |
| tx_avg_amount_90d | Average transaction amount (90 days) | AVG |
| tx_std_amount_90d | Standard deviation of transaction amounts | STDDEV |
| cash_in_ratio_30d | Cash-in vs cash-out volume ratio | SUM(in)/SUM(out) |
| merchant_diversity_90d | Unique merchant count | COUNT(DISTINCT) |
| tx_recency_days | Days since last transaction | MAX(date) - CURRENT_DATE |
| weekend_tx_ratio | Weekend transaction frequency | Conditional COUNT |
| large_tx_count_90d | Count of transactions > SYP 100,000 | Conditional COUNT |
| small_tx_count_90d | Count of transactions < SYP 10,000 | Conditional COUNT |
| recurring_tx_count | Count of recurring bill payments | COUNT WHERE recurring=true |

#### Savings Features (15 features)
| Feature | Description |
|---------|-------------|
| avg_daily_balance_30d | Average daily wallet balance |
| avg_daily_balance_90d | Average daily wallet balance (90 days) |
| min_daily_balance_30d | Minimum daily balance |
| max_daily_balance_90d | Maximum daily balance |
| savings_deposit_count | Number of savings account deposits |
| savings_total_balance | Total savings across products |
| savings_to_wallet_ratio | Savings balance / wallet balance |
| balance_volatility | Coefficient of variation of daily balance |
| days_balance_above_threshold | Days balance > SYP 50,000 |
| savings_growth_rate | (Current savings - 3m ago) / 3m ago |

#### Bill Payment Features (10 features)
| Feature | Description |
|---------|-------------|
| bill_payment_count | Total bills paid through Beza |
| bill_on_time_pct | Percentage paid on time |
| bill_avg_delay_days | Average delay in payment |
| bill_payment_regularity | Variance in payment dates |
| utility_bills_paid | Distinct utility types paid |

#### Agent Network Features (10 features)
| Feature | Description |
|---------|-------------|
| agent_interaction_count | Number of agent interactions |
| unique_agents | Distinct agents used |
| agent_avg_distance_km | Average distance to agents |
| agent_complaint_count | Complaints filed against agents used |
| agent_rating_avg | Average rating of agents used |

#### Identity & Behavior Features (10 features)
| Feature | Description |
|---------|-------------|
| wallet_age_days | Account age in days |
| kyc_level | KYC completion level |
| device_count | Number of devices used |
| login_frequency | Average logins per week |
| support_ticket_count | Support tickets submitted |
| feature_usage_diversity | Number of different Beza features used |

#### Derived Composite Features (10 features)
| Feature | Formula |
|---------|---------|
| financial_health_index | (savings_ratio × 0.3) + (tx_regularity × 0.3) + (bill_payment × 0.2) + (wallet_age × 0.2) |
| stability_score | 1 - (balance_volatility / avg_balance) |
| loyalty_score | wallet_age_days × login_frequency × feature_usage |
| cash_flow_ratio | avg_inflow / (avg_outflow + 1) |

### Model Training Pipeline
```python
# pseudocode
def train_scoring_model():
    # 1. Load data
    df = load_training_data(months=24)
    
    # 2. Feature engineering
    features = engineer_features(df)
    X = features.drop(['target', 'user_id'], axis=1)
    y = features['target']  # binary: good_payer (1) / defaulted (0)
    
    # 3. Split
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, stratify=y)
    
    # 4. Train XGBoost
    model = XGBClassifier(
        n_estimators=500,
        max_depth=6,
        learning_rate=0.01,
        subsample=0.8,
        colsample_bytree=0.8,
        scale_pos_weight=compute_scale_pos_weight(y_train),
        eval_metric=['auc', 'logloss'],
        early_stopping_rounds=20
    )
    model.fit(X_train, y_train, eval_set=[(X_test, y_test)])
    
    # 5. Evaluate
    y_pred = model.predict(X_test)
    y_prob = model.predict_proba(X_test)[:, 1]
    
    metrics = {
        'auc_roc': roc_auc_score(y_test, y_prob),
        'precision': precision_score(y_test, y_pred),
        'recall': recall_score(y_test, y_pred),
        'f1': f1_score(y_test, y_pred)
    }
    
    # 6. Scale to 300-850
    scaler = MinMaxScaler(feature_range=(300, 850))
    score = scaler.fit_transform(y_prob.reshape(-1, 1))
    
    # 7. Save model + scaler
    model.save('credit_model_v2.3.json')
    joblib.dump(scaler, 'score_scaler_v2.3.pkl')
    
    return model, metrics
```

### Model Performance Targets
| Metric | Current | Target |
|--------|---------|--------|
| AUC-ROC | 0.87 | > 0.90 |
| Precision (default prediction) | 0.72 | > 0.80 |
| Recall (default prediction) | 0.68 | > 0.75 |
| F1 Score | 0.70 | > 0.77 |
| Kolmogorov-Smirnov (KS) | 0.52 | > 0.55 |

---

## 2. Default Prediction

### Early Warning System
Predicts likelihood of default before the first missed payment.

```yaml
model_input:
  - Current credit score
  - Score trend (last 3 months)
  - Wallet balance trajectory
  - Transaction pattern changes
  - Agent interaction decline
  - Bill payment delays (even if paid)
  - Support ticket sentiment

model_output:
  risk_score: 0.0 - 1.0
  risk_tier: low | medium | high | critical
  early_warning_signals: [list of detected risk indicators]
  suggested_action: monitor | alert | contact | restructure_offer
```

### Prediction Windows
| Horizon | Use | Action |
|---------|-----|--------|
| 7 days before due | Short-term liquidity risk | Send pre-emptive reminder |
| 30 days before due | Medium-term default risk | Offer restructure pre-emptively |
| 90 days before due | Long-term risk assessment | Adjust credit limit, increase monitoring |

---

## 3. Optimal Offer Generation

### Multi-Objective Optimization
```python
def generate_optimal_offer(user_id, score, product_type, requested_amount):
    """
    Optimize for: 
    1. Maximum acceptance probability
    2. Minimum default probability
    3. Target portfolio yield
    """
    
    # Acceptance model (logistic regression)
    acceptance_prob = predict_acceptance(
        amount=requested_amount,
        profit_rate=rate,
        term=term,
        user_segment=segment
    )
    
    # Default model (XGBoost)
    default_prob = predict_default(
        score=score,
        amount=requested_amount,
        rate=rate,
        term=term
    )
    
    # Objective: maximize (acceptance * (1 - default))
    best_offer = maximize({
        'amount': [requested_amount * 0.7, requested_amount, requested_amount * 1.2],
        'profit_rate': [rate_min, (rate_min + rate_max) / 2, rate_max],
        'term': [term_min, (term_min + term_max) / 2, term_max]
    }, objective_function)
    
    return best_offer
```

### Offer Personalization Examples
| Score | Requested | Optimized Offer | Rationale |
|-------|-----------|-----------------|-----------|
| 750 | SYP 500K Qard | SYP 500K, 0%, 180d | High trust: max amount, long term |
| 680 | SYP 2M Murabaha | SYP 1.5M, 7%, 12mo | Moderate: slightly lower amount to reduce risk |
| 580 | SYP 5M Micro | SYP 3M, 12%, 9mo | Higher risk: lower amount, shorter term, higher rate |
| 450 | SYP 300K Qard | Reject with tips | Below threshold |

---

## 4. Collection Prioritization

### Priority Scoring
```python
def calculate_collection_priority(contract):
    """
    Score: 0-100 (higher = call first)
    Factors:
    - Days overdue (40% weight)
    - Amount at risk (20%)
    - Default probability (25%)
    - Historical responsiveness (15%)
    """
    score = (
        overdue_weight * normalize(days_overdue, 0, 90) +
        amount_weight * normalize(amount_remaining, 0, max_amount) +
        risk_weight * default_probability +
        responsiveness_weight * (1 - historical_response_rate)
    )
    return score
```

### Queue Strategy
```yaml
priority_tiers:
  critical (score > 80):
    action: Call immediately
    frequency: Daily
    channel: Phone + SMS + field
    
  high (score 60-80):
    action: Call within 4 hours
    frequency: Every 2 days
    channel: Phone + SMS
    
  medium (score 40-60):
    action: Call within 24 hours
    frequency: Every 3 days
    channel: SMS + in-app
    
  low (score < 40):
    action: Automated reminder
    frequency: Weekly
    channel: SMS + in-app
```

### ML for Collection Optimization
| Model | Purpose | Features |
|-------|---------|----------|
| Contact time optimization | Best time to call | Historical answer patterns, day of week |
| Channel preference | Best channel for user | Past response rates by channel |
| Restructure likelihood | Willingness to restructure | User profile, past restructures, sentiment |
| Recovery amount prediction | Expected recoverable amount | Contract value, user assets, guarantor quality |
