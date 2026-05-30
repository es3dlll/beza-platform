# Open Finance AI Integration

## AI Use Cases

### 1. API Usage Prediction
```
Input: Developer usage history (90 days), tier, endpoint patterns
Model: Time-series forecasting (Prophet/LightGBM)
Output: Predicted next-month API calls + recommended tier
Integration:
  - Monthly batch job: predicts usage for each developer
  - Suggests tier upgrade if usage > 80% of current limit
  - Alerts developer via email/in-app before hitting limits
```

### 2. Anomaly Detection (API Abuse)
```
Input: Request patterns per developer (rate, payload, endpoints)
Model: Isolation Forest + rule-based overlay
Output: Anomaly score (0-100) + reason codes
Integration:
  - Real-time: score every request as it arrives
  - Score > 80: auto-block + alert compliance team
  - Score 50-80: step-up auth (require additional verification)
  - Feedback loop: false positives retrain model weekly
```

### 3. Smart Webhook Retry Strategy
```
Input: Webhook endpoint response patterns, time of day
Model: Simple ML classifier
Output: Optimal retry schedule per endpoint
  - Some endpoints have maintenance windows (skip retries then)
  - Some endpoints consistently fail at certain times
  - Adaptive: increase/decrease retry intervals based on history
Integration:
  - WebhookDeliveryService uses model output for retry timing
  - Model updated daily based on delivery success patterns
```

### 4. Developer Documentation Recommendations
```
Input: Developer API usage, error patterns, support tickets
Model: Content-based recommendation
Output: Suggested documentation articles, code examples, guides
Integration:
  - Portal dashboard shows "Recommended for you" section
  - Based on:
    - Most called endpoints (show advanced guides)
    - Most frequent errors (show troubleshooting docs)
    - Recently added features (show what's new)
```

### 5. Automated Integration Testing
```
Input: Sandbox usage patterns, typical integration flows
Model: Rule-based + pattern matching
Output: Customized test suite for sandbox → production transition
Integration:
  - When developer requests production access, analyze sandbox usage
  - Generate tailored test cases covering their most-used endpoints
  - Score their test coverage and suggest gaps
```

## ML Model Deployment

### Anomaly Detection Pipeline
```
Training (Weekly, 02:00 AM):
  1. Extract: 90 days of API logs per developer
  2. Feature engineering: call frequency, payload size, endpoint variety
  3. Train: Isolation Forest for per-developer baseline
  4. Evaluate: Precision > 90%, Recall > 85%
  5. Deploy: Model served via ONNX Runtime

Inference (Real-time):
  1. Pre-process: normalize current request features
  2. Score: compare against developer's historical baseline
  3. Post-process: apply rule-based overlay (sanctions, blacklist)
  4. Return: anomaly score + recommended action
```

## AI Service API
```http
POST /internal/ai/anomaly-score
Content-Type: application/json

{
  "developer": {
    "id": 42,
    "tier": "startup",
    "account_age_days": 120,
    "avg_daily_calls": 5000,
    "avg_error_rate": 0.015
  },
  "request": {
    "endpoint": "/v1/of/payments",
    "method": "POST",
    "amount": 5000000,
    "currency": "SYP",
    "hour": 14,
    "day_of_week": 3
  },
  "context": {
    "calls_last_minute": 85,
    "calls_last_hour": 1200,
    "errors_last_hour": 2,
    "distinct_recipients_last_hour": 45
  }
}
```

```json
{
  "score": 12,
  "decision": "allow",
  "risk_level": "low",
  "reasons": ["normal_pattern", "established_account", "consistent_volume"],
  "model_version": "anomaly-v1.5",
  "inference_time_ms": 8
}
```
