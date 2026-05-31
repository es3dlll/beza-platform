# Loyalty AI Integration

## AI Use Cases

### 1. Personalized Reward Recommendations
```
Input: User transaction history, points balance, tier, past redemptions
Model: Collaborative filtering + content-based recommendation
Output: Top 3 recommended rewards for user
Integration:
  - Shown on Loyalty Hub as "مقترحات لك"
  - Updated weekly based on latest transaction patterns
  - Consider: fee discounts for heavy transfer users, airtime for bill payers
```

### 2. Churn Prediction & Retention
```
Input: User transaction volume trend, points balance trend, tier status
Model: XGBoost classifier
Output: Churn probability score (0-100) + recommended intervention
Integration:
  - Users with score > 70: trigger retention campaign
  - Intervention: bonus points offer, tier grace period extension
  - Score > 90: VIP outreach by support team
Features:
  - Transaction frequency trend (30d vs previous 30d)
  - Points earning rate decline
  - Points balance draining (redeeming without earning)
  - Time since last transaction
  - Support ticket frequency
```

### 3. Smart Tier Grace Period
```
Input: User's current tier, rolling total trend, history of tier changes
Model: Logistic regression (will user earn enough in 30 days?)
Output: Automatic grace period extension or early downgrade warning
Integration:
  - If user is likely to re-qualify within grace period: extend to 60 days
  - If user is unlikely: send early warning with personalized tips
  - "أنت على بعد 5,000 نقطة فقط للحفاظ على المستوى الذهبي!"
```

### 4. Campaign Performance Prediction
```
Input: Campaign type, budget, duration, merchant category, customer base
Model: Simple regression based on historical campaign data
Output: Predicted redemptions, budget utilization, ROI
Integration:
  - Show merchant during campaign creation: "من المتوقع 500 استبدال"
  - Real-time: "الحملة تسير بنسبة 120% من المتوقع"
  - Alerts if campaign underperforming: "جرب زيادة المضاعف إلى 3×"
```

### 5. Points Expiry Optimization
```
Input: User redemption patterns, points balance, expiry schedule
Model: Simple decision tree
Output: Optimal expiry reminder timing and channel
Integration:
  - Notify based on past behavior:
    - Users who redeem on reminder: send 14 days before
    - Users who need multiple reminders: send 30d, 14d, 7d
    - Users who don't redeem: offer auto-redeem option
  - "نقاطك على وشك الانتهاء! هل تريد استبدالها تلقائياً بأفضل عرض؟"
```

## ML Model Deployment

### Churn Prediction Pipeline
```
Training (Weekly, 03:00 AM):
  1. Extract: 90 days of user behavior data
  2. Feature engineering: 50+ features
  3. Train: XGBoost with balanced classes
  4. Evaluate: Precision > 85%, Recall > 80%
  5. Export: ONNX format
  6. Deploy: Canary 10% → full rollout

Inference (Daily batch):
  1. Score all users with recent activity
  2. Generate intervention list (top 10% by churn score)
  3. Execute retention campaigns automatically
```

## AI Service API
```http
POST /internal/ai/recommend-rewards
Content-Type: application/json

{
  "user": {
    "id": 42,
    "tier": "silver",
    "points_balance": 15000,
    "top_categories": ["send", "bills", "airtime"],
    "past_redemptions": ["fee_discount", "airtime"],
    "monthly_transaction_volume": 500000
  }
}
```

```json
{
  "recommendations": [
    {
      "reward_id": 1,
      "name": "خصم رسوم تحويل 5,000",
      "score": 0.92,
      "reason": "تقوم بالتحويل كثيراً — هذا العرض سيوفر لك 5,000 ل.س"
    },
    {
      "reward_id": 2,
      "name": "رصيد سيريتيل 2,500",
      "score": 0.78,
      "reason": "تشحن رصيدك شهرياً — استبدلها برصيد"
    }
  ],
  "model_version": "rec-v2.1"
}
```
