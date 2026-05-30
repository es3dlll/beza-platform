# Savings AI Integration

## ML Models

### 1. Goal Completion Prediction

```
Purpose: Predict which users will successfully complete their savings goals
         Used for: early intervention for at-risk goals, nudge optimization

Input Features:
  User-level:
    - account_age_days
    - wallet_avg_balance_30d
    - wallet_balance_volatility
    - num_previous_goals
    - previous_goal_completion_rate
    - auto_save_enabled (bool)
    - round_up_enabled (bool)
    - kyc_level
    - app_session_frequency_30d
    - notification_open_rate

  Goal-level:
    - goal_duration_days
    - target_amount
    - initial_deposit_amount
    - days_since_last_deposit
    - auto_save_skip_rate_30d
    - round_up_frequency_30d
    - manual_deposit_frequency_30d
    - progress_pct
    - progress_velocity (avg daily increase)
    - goal_locked (bool)
    - goal_type (individual/team)

  Behavioral:
    - deposit_time_of_day (modal)
    - deposit_day_of_week (modal)
    - amount_consistency (std_dev of deposits)
    - milestone_celebration_engagement
    - push_response_rate

Model: XGBoost Classifier
  - Training: daily, incremental
  - Output: probability (0-1) of goal completion
  - Threshold: < 0.3 → high risk, 0.3-0.7 → medium risk, > 0.7 → low risk
  - Evaluation: AUC > 0.85, Precision > 80% for high-risk class
  - Inference: batch (daily) + real-time (on deposit)
```

### 2. Optimal Round-Up Amount Personalization

```
Purpose: Determine the optimal round-up amount for each user
         Balance between savings impact and user friction

Input Features:
  - avg_transaction_amount_30d
  - transaction_frequency_30d
  - wallet_avg_balance_30d
  - income_frequency (detected pattern)
  - past_round_up_acceptance_rate (A/B test results)
  - goal_remaining_amount
  - goal_days_remaining
  - auto_save_amount (current)
  - user_sensitivity (how often they change round-up settings)

Model: Bayesian Optimization
  - Objective: maximize (total_round_up_saved * acceptance_rate)
  - Exploration: epsilon-greedy (10% exploration rounds)
  - Personalization: per-user optimal round_to_nearest {500, 1000, 2000, 5000}
  - Constraints:
    - round_up_amount <= 10% of avg transaction amount
    - daily_round_up_total <= 50,000 SYP

Output Example:
  User A: round_to_nearest=1000, max_daily=50,000, avg_roundup=450/transaction
  User B: round_to_nearest=5000, max_daily=100,000, avg_roundup=2,300/transaction
  User C: round_to_nearest=500, max_daily=10,000, avg_roundup=120/transaction
```

### 3. Savings Habit Nudge Timing

```
Purpose: Determine optimal time to send savings nudges for each user
         Maximize nudge-to-deposit conversion

Input Features:
  - deposit_time_history (all user deposits timestamps)
  - app_session_times_30d
  - transaction_times_30d
  - salary_credit_detected_day
  - salary_credit_detected_time
  - auto_save_time (current config)
  - timezone_offset
  - nudge_response_history (which nudges they acted on)

Model: Temporal Pattern Mining + Simple Ranker
  - Cluster users by deposit time patterns
  - Best time: calculated from historical deposit distribution mode

Output Example:
  User Cluster A: "Morning" — best nudge time 08:00-09:00
    → "صباح الخير! يوم جديد، فرصة جديدة للتوفير ☀️"
  User Cluster B: "Evening" — best nudge time 20:00-22:00
    → "مساء الخير! هل وفرت اليوم؟ 5 دقائق كافية 💪"
  User Cluster C: "Payday" — best nudge day: detected payday
    → "يوم الراتب! ضاعف توفير اليوم 💰"
  User Cluster D: "Transaction-triggered" — best nudge: after large txn
    → "عملية شراء كبيرة! وفر الفكة الآن ↻"
```

### 4. Savings Goal Recommendation

```
Purpose: Suggest savings goals to users based on their spending patterns
         "You spend 50,000 SYP/month on delivery — want to save for a delivery bike?"

Input:
  - 90 days of transaction history (categorized)
  - User demographics (age, city, occupation)
  - Previous goals (completed, cancelled)

Output: Top 3 goal suggestions with:
  - Goal name
  - Suggested target amount
  - Suggested duration
  - Personalized auto-save amount
  - Confidence score

Example:
  Spending pattern: High food delivery spending
  → "وفر 500,000 ل.س لدراجة توصيل في 6 أشهر (2,778 ل.س/يوم)"
  Confidence: 0.72
```

## AI Service API

```http
POST /internal/ai/savings/completion-prediction
Content-Type: application/json

{
  "goal": {
    "id": "goal_abc123",
    "target_amount": 2500000,
    "current_amount": 1250000,
    "days_remaining": 124,
    "auto_save_enabled": true,
    "round_up_enabled": true,
    "goal_locked": true,
    "days_since_last_deposit": 0
  },
  "user": {
    "id": 42,
    "account_age_days": 180,
    "kyc_level": 1,
    "wallet_avg_balance": 150000,
    "previous_goals_completed": 2,
    "previous_goals_total": 3,
    "app_session_frequency": 12,
    "notification_open_rate": 0.45
  },
  "behavioral": {
    "deposit_frequency_30d": 28,
    "auto_save_skip_rate": 0.03,
    "manual_deposit_frequency": 4,
    "avg_deposit_amount": 10000,
    "milestone_engagement": true
  }
}
```

```json
{
  "completion_probability": 0.87,
  "risk_level": "low",
  "predicted_completion_date": "2026-11-12",
  "early_alert": null,
  "suggested_actions": [
    {"type": "none", "reason": "on_track"}
  ],
  "model_version": "savings-completion-v1.3",
  "inference_time_ms": 45
}
```

```http
POST /internal/ai/savings/nudge-time
Content-Type: application/json

{
  "user": {
    "id": 42,
    "deposit_times": ["08:15", "08:30", "09:00", "08:45", "09:15"],
    "session_times": ["07:30", "08:00", "12:00", "20:00"],
    "timezone": "Asia/Damascus",
    "auto_save_time": "10:00",
    "has_salary_pattern": true,
    "salary_day": 25
  },
  "active_nudges": [
    {"type": "daily_save_reminder", "last_sent": "2026-05-28T08:00:00Z"},
    {"type": "milestone", "reached": true}
  ]
}
```

```json
{
  "recommended_nudge_time": "2026-05-29T08:30:00Z",
  "cluster": "morning",
  "nudge_type": "daily_save_reminder",
  "confidence": 0.92,
  "message": "صباح الخير! يوم جديد، فرصة جديدة للتوفير ☀️",
  "model_version": "savings-nudge-v2.1"
}
```

## ML Pipeline Architecture

```
Training Pipeline (Daily, 03:00 AM):
  1. Feature extraction:
     - Extract from: savings_goals, savings_transactions, wallets,
                     wallet_transactions, user_sessions
     - Join on user_id + goal_id
     - Window: 90 days rolling

  2. Feature engineering:
     - Calculate aggregates (mean, std, min, max, trend)
     - Time-based features (hour, day_of_week, day_of_month)
     - Ratio features (progress_velocity, skip_rate)

  3. Model training:
     - XGBoost for completion prediction
     - LightGBM for nudge time clustering
     - Bandit (epsilon-greedy) for round-up optimization
     - Collaborative filtering for goal recommendation

  4. Evaluation:
     - Holdout set (last 30 days)
     - Precision/Recall/F1 for classification
     - RMSE for regression
     - Conversion rate for nudges (A/B test)

  5. Export:
     - ONNX format
     - Model version + feature importance
     - Push to model registry

Inference (Real-time via API):
  - Completion prediction: synchronous (< 100ms)
  - Nudge time: synchronous (< 50ms)
  - Round-up optimization: async (pre-computed daily)
  - Goal recommendation: async (pre-computed weekly)

A/B Testing Framework:
  - Control: random nudge time
  - Variant A: ML-recommended nudge time
  - Variant B: ML-recommended nudge time + personalized message
  - Metric: deposit conversion rate within 2 hours of nudge
  - Minimum detectable effect: 5% improvement
  - Duration: 14 days minimum
```
