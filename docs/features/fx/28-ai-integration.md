# FX Engine AI Integration

## AI Use Cases

### 1. ML Rate Prediction (Short-term 5-min Forecast)
```
Goal: Predict SYP/USD rate 5 minutes into the future with < 2% error
Input: Last 60 minutes of rate data (240 samples at 15s intervals)
Features:
  - Rate history (bid, ask, mid) — last 60 min
  - Provider response times (indicator of market stress)
  - Time of day (Damascus market hours: 09:00-15:00)
  - Day of week (weekend vs weekday patterns)
  - Recent CBS announcements (binary: announced/not)
  - Black market premium rate (divergence from parallel)
  - Volume of recent conversions on Beza
  - External: XE.com trend, regional market indicators

Model: LightGBM (gradient boosting) — lightweight, fast inference
Output: Predicted mid rate + confidence interval (68%, 95%)
Integration:
  - Runs every 60 seconds as batch prediction
  - Results stored in Redis: fx:prediction:SYP/USD
  - Consumed by RateEngine for spread optimization
  - Consumed by UI for "predicted trend" display
  - Not used for actual conversion rates (regulatory requirement)

Performance Targets:
  - MAE (Mean Absolute Error): < 200 SYP on rate of 14,500
  - MAPE (Mean Absolute Percentage Error): < 1.5%
  - Inference time: < 10ms per prediction
  - Retraining: Weekly (rolling 30-day window)
```

### 2. Anomaly Detection (Unusual Spread Widening)
```
Goal: Detect anomalous rate movements before they cause financial loss
Approach: Statistical + ML ensemble

Statistical Layer (rules-based):
  - Z-score on rate change: |z| > 3 → anomaly
  - IQR on spread: Q3 + 1.5*IQR → outlier
  - Rolling window std dev: > 3σ → anomaly

ML Layer (unsupervised):
  - Model: Isolation Forest (train on normal rate patterns)
  - Features: same as prediction model + provider count online
  - Anomaly score: 0 (normal) to 1 (anomalous)
  - Threshold: score > 0.7 → flag for review
  - Score > 0.9 → auto-mitigation (deprioritize anomalous provider)

Integration:
  - Runs every 60 seconds alongside prediction
  - Output: anomaly_score + contributing factors
  - When anomaly detected: emit RateAnomalyDetected event
  - Auto-mitigation: exclude affected provider, cap spread at 3%
  - Dashboard: anomaly timeline with drill-down

Model Performance:
  - Precision: > 90% (low false positive rate)
  - Recall: > 85% (catch most real anomalies)
  - Detection latency: < 60s from event to alert
```

### 3. Optimal Rate Provider Selection
```
Goal: Dynamically select the best rate provider based on real-time conditions
Approach: Multi-armed bandit (epsilon-greedy)

Context:
  - Multiple providers with varying:
    - Rate quality (competitiveness vs mid-market)
    - Response time
    - Uptime/reliability
    - Historical accuracy
  - Need to balance:
    - Exploitation (use best known provider)
    - Exploration (try other providers for better rates)

Model:
  - Online learning: provider "score" updated after each fetch
  - Score = α × rate_competitiveness + β × reliability + γ × speed
  - α = 0.5, β = 0.3, γ = 0.2 (weights tuned monthly)
  - Epsilon = 0.1 (10% of time, explore random provider)
  - Decaying epsilon: 0.1 → 0.05 over 30 days

Integration:
  - Provider scores stored in Redis: fx:provider:score:{id}
  - RateProviderService re-orders providers by score (not static priority)
  - If score difference > 0.2: clear winner used
  - If scores close: random weighted selection
  - Scores reset on provider re-configuration

Benefits:
  - 5-8% improvement in rate competitiveness over static priority
  - Automatic adaptation to provider performance changes
  - Graceful handling of provider degradation
```

### 4. Rate Volatility Prediction for Hedging
```
Goal: Predict rate volatility in next hour to inform hedging decisions
Input: Last 6 hours of rate data, provider metrics, market indicators
Model: Quantile Regression Forest
Output: Predicted volatility (standard deviation of rate returns) + volatility regime

Volatility Regimes:
  - Low: σ < 0.5% — no hedging needed
  - Medium: σ 0.5-2% — partial hedge (50% of exposure)
  - High: σ 2-5% — full hedge (100% of exposure)
  - Extreme: σ > 5% — halt conversions, emergency mode

Integration:
  - Runs every 15 minutes
  - Output stored: fx:volatility:prediction:SYP/USD
  - Consumed by HedgeService for exposure management
  - When extreme volatility predicted:
    1. Halt new conversions (prevent spread losses)
    2. Alert treasury team
    3. Tighten spread caps (reduce max spread to 2%)
    4. Reduce rate lock duration to 15s

Performance:
  - Volatility regime accuracy: > 80%
  - Hedge loss reduction: 15-25% vs static hedging
```

## ML Model Deployment

### Rate Prediction Pipeline
```
Training (Weekly, Sunday 03:00 AM):
  1. Extract: 30 days of rate data (100K+ samples)
  2. Feature engineering:
     - Lag features (t-1, t-5, t-10, t-30)
     - Rolling statistics (mean, std, min, max over windows)
     - Time features (hour, day_of_week, is_market_hours)
     - Provider features (response times, online count)
  3. Train: LightGBM with early stopping
     - Learning rate: 0.05
     - Max depth: 7
     - Subsample: 0.8
     - Feature fraction: 0.8
  4. Evaluate: holdout 20% of data
     - Target: MAPE < 1.5%, MAE < 200 SYP
  5. Export: ONNX format
  6. Deploy: canary 10% traffic → 1h → full rollout

Inference (Real-time, every 60 seconds):
  1. Pre-process: transform raw rate data → feature vector
  2. Load model: ONNX Runtime session
  3. Predict: 5-min ahead rate + confidence intervals
  4. Post-process: clamp predictions to realistic bounds (±10%)
  5. Store: Redis fx:prediction:{pair}
  6. Log: prediction vs actual (for monitoring drift)
```

### Anomaly Detection Pipeline
```
Training (Monthly, 1st of month):
  1. Extract: 90 days of normal rate data (no known anomalies)
  2. Train: Isolation Forest
     - n_estimators: 100
     - max_samples: 256
     - contamination: 0.01 (expect 1% anomalies)
  3. Evaluate: precision/recall on labeled anomaly dataset
  4. Deploy: alongside prediction model

Inference (Real-time, every 60 seconds):
  1. Pre-process: same feature vector as prediction
  2. Load model: ONNX Runtime session
  3. Score: anomaly score (0-1)
  4. Threshold: > 0.7 flag, > 0.9 auto-mitigate
  5. Alert: emit event if score > threshold
```

## AI Service API

```http
POST /internal/ai/rate-prediction
Content-Type: application/json

{
  "pair": "SYP/USD",
  "features": {
    "rate_history": [14500, 14550, 14600, 14580, 14520, 14500, 14530, 14550],
    "spread_history": [0.025, 0.026, 0.027, 0.028, 0.026, 0.025, 0.025, 0.026],
    "provider_online_count": 3,
    "hour": 10,
    "day_of_week": 3,
    "is_market_hours": true,
    "cbs_announcement_today": false,
    "conversion_volume_15m": 25000000,
    "avg_provider_response_time_ms": 95,
    "black_market_premium": 0.045
  }
}
```

```json
{
  "prediction": {
    "mid": 14580,
    "confidence_68": [14530, 14630],
    "confidence_95": [14480, 14680],
    "direction": "up",
    "change_pct": 0.35
  },
  "volatility": {
    "predicted_std": 0.012,
    "regime": "low",
    "next_hour_volatility": 0.008
  },
  "anomaly": {
    "score": 0.12,
    "is_anomaly": false,
    "contributing_factors": []
  },
  "model_version": "rate-pred-v3.2",
  "inference_time_ms": 8,
  "features_used": 24,
  "timestamp": "2026-06-01T10:00:00Z"
}
```

## Model Monitoring

### Drift Detection
```
Monitor for model drift:
  - Prediction error (MAE) tracked hourly
  - If MAE > 300 SYP for 2 consecutive hours:
    → Alert: "Rate prediction model degrading"
    → Fall back to simple moving average forecast
    → Trigger retraining
  - Feature distribution drift (PSI > 0.2)
  - Target distribution drift
  - Model retrained automatically if drift detected

Dashboard Metrics:
  - Prediction MAE (rolling 24h) — target < 200 SYP
  - Anomaly detection precision — target > 90%
  - Provider selection score distribution
  - Volatility prediction accuracy
  - Model inference time P99 — target < 50ms
  - Drift metrics (PSI, KS statistic)
```
