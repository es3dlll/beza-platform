# Agent Network AI Integration

## ML Model 1: Agent Cash Demand Forecasting

### Objective
Predict daily cash-in/cash-out volume per agent to recommend optimal float levels, reducing instances of agents running out of float or holding excess idle cash.

### Model Architecture
```
Type: Time-series forecasting (ensemble)
Base Model: LightGBM with time-series features
Secondary: Prophet (Facebook) for seasonality
Ensemble: Weighted average (LightGBM 0.7, Prophet 0.3)

Features:
  - Agent-level:
    - Transaction volume last 1/7/30 days (cash-in + cash-out)
    - Float balance history (hourly, last 7 days)
    - Day of week (Syria work week: Sun-Thu)
    - Day of month (salary days: 1st-5th, 25th-30th)
    - Is holiday? (Friday, Eid, religious holidays)
    - Is salary day? (government salary distribution)
    - Agent tier
    - Agent tenure (days since activation)
  
  - Location-level:
    - District population density
    - Number of nearby agents (within 1km)
    - Distance to nearest bank branch
    - Urban/rural classification
  
  - Macro-level:
    - National inflation rate (SYP devaluation)
    - Electricity availability (impacts cash-out during outages)
    - Security index (regional stability score)
    - Mobile data price index

Targets:
  - Daily cash-in volume (SYP) for next 7 days
  - Daily cash-out volume (SYP) for next 7 days
  - Peak hour volume (SYP, for intra-day float planning)

Training:
  - Historical data: all agent transactions since launch
  - Retraining: weekly (incremental)
  - Evaluation: MAPE (Mean Absolute Percentage Error) < 20%
```

### Output: Float Recommendation
```json
{
  "agent_id": 10234,
  "date": "2026-06-02",
  "predicted_cash_in": 1250000,
  "predicted_cash_out": 800000,
  "net_predicted_change": -450000,
  "recommended_float": 1500000,
  "current_float": 550000,
  "recommended_top_up": 950000,
  "confidence": "high",  // high/medium/low
  "factors": [
    "غداً أول الشهر — زيادة متوقعة في السحوبات",
    "الجمعة — لا يوجد تأثير (محل مفتوح)",
    "الأسبوع الماضي: معدل 1.2M إيداع و 0.75M سحب"
  ]
}
```

### Integration
```
Consumed by:
  - Agent POS app: "توصية: قم بتعبئة الصندوق بـ 950,000 ل.س لليوم التالي"
  - Operations dashboard: list of agents needing top-up
  - Automated restocking system (Phase 4): auto-trigger float transfer
  - SMS service: nightly SMS to agents with predicted demand

API Endpoint:
  GET /api/v1/agent/float/recommendation
  Authorization: Bearer {agent_token}
  → Returns personalized float recommendation

Batch Process:
  Schedule: Daily at 22:00
  artisan agent:predict-float-demand
  → Updates agent_float_recommendations table
```

## ML Model 2: Agent Fraud Detection

### Objective
Detect anomalous agent transaction patterns that may indicate fraud, money laundering, or account compromise. Flag suspicious activity in real-time for compliance review.

### Model Architecture
```
Type: Anomaly detection (unsupervised + supervised ensemble)
Models:
  - Isolation Forest (unsupervised — catches novel patterns)
  - XGBoost (supervised — trained on confirmed fraud cases)
  - LSTM Autoencoder (sequence anomaly — catches behavioral drift)

Features (extracted per transaction, in real-time):
  - Amount features:
    - Transaction amount vs agent's 7-day avg
    - Transaction amount vs agent's 30-day avg
    - Amount roundness (100,000 is normal; 99,999 is suspicious)
    - Amount velocity (cumulative in last 5/15/60 min)
  
  - Time features:
    - Hour of transaction (vs agent's typical hours)
    - Day of week (vs agent's typical days)
    - Time since last transaction (< 30 seconds = rapid fire)
  
  - Customer features:
    - Is customer new to this agent? (first interaction)
    - Customer's transaction velocity across agents
    - Customer's distance from home agent
    - Customer's typical txn size vs current
  
  - Geographic features:
    - Transaction location vs agent's typical location
    - Distance between cash-in and cash-out locations (if same customer rapid)
  
  - Device features:
    - Device IP geolocation vs agent's registered location
    - Device change (new device for existing agent)
    - VPN/proxy detection

Risk Scoring:
  - Per-transaction risk score: 0-100
  - Thresholds:
    - 0-30: Normal — process immediately
    - 30-60: Watch — process, flag for review if pattern continues
    - 60-80: Warning — process, notify compliance team
    - 80-100: Block — transaction declined, agent notified

Training Data:
  - Labeled fraud cases (from compliance team)
  - Simulated fraud patterns (synthetic data generation)
  - Continuous learning: compliance team feedback loop
```

### Real-Time Fraud Detection Flow
```
Agent POS              Beza API                Fraud Engine            Compliance
  │                      │                         │                     │
  │── POST /cash-in ────>│                         │                     │
  │                      │── Extract features ────>│                     │
  │                      │                         │── Score: 15 ───────>│
  │                      │<── Normal ──────────────│                     │
  │<── 200 OK ──────────│                         │                     │
  │                      │                         │                     │
  │── POST /cash-in ────>│  (third rapid txn)      │                     │
  │                      │── Extract features ────>│                     │
  │                      │                         │── Score: 72 ───────>│
  │                      │<── Warning ────────────│                     │
  │<── 200 OK ──────────│                         │                     │
  │                      │                         │── Flag agent ──────>│
  │                      │                         │                     │── Review
```

### Backend Integration
```php
class FraudDetectionService
{
    public function evaluateTransaction(AgentTransaction $txn): FraudResult
    {
        $features = $this->extractFeatures($txn);
        $score = $this->mlService->predict($features);
        
        return new FraudResult(
            score: $score,
            decision: match (true) {
                $score < 30 => FraudDecision::APPROVE,
                $score < 60 => FraudDecision::WATCH,
                $score < 80 => FraudDecision::FLAG,
                default => FraudDecision::BLOCK,
            },
            rulesTriggered: $this->getTriggeredRules($features),
        );
    }
}
```

## ML Model 3: Agent Performance Scoring

### Objective
Score agent performance across multiple dimensions to enable tier upgrades, identify training needs, and optimize network quality.

### Model Architecture
```
Type: Multi-factor scoring system (weighted)
Update Frequency: Daily

Score Components (0-100 each, weighted):
  1. Volume Score (weight: 0.25)
     - Monthly transaction volume (normalized against tier target)
     - Monthly transaction count
     - Growth rate (month-over-month)
  
  2. Customer Satisfaction Score (weight: 0.25)
     - Post-transaction SMS survey results (1-5 stars)
     - Complaint rate (per 1000 transactions)
     - Dispute resolution rate (disputes resolved / total disputes)
  
  3. Reliability Score (weight: 0.20)
     - Uptime (hours with active transactions / total operating hours)
     - Offline transaction ratio (lower is better)
     - Float management (float low incidents per month)
  
  4. Compliance Score (weight: 0.20)
     - KYC document up-to-date
     - Fraud flags (per 1000 transactions)
     - Float discrepancy incidents
     - AML threshold breaches
  
  5. Engagement Score (weight: 0.10)
     - Training completion
     - Response to Beza announcements
     - Referral of new agents
     - Optional feature adoption (receipt printing, QR codes)

Final Score: Weighted average → 0-100
  - 0-30: Needs improvement (coaching required)
  - 31-50: Bronze (standard)
  - 51-70: Silver (good)
  - 71-85: Gold (excellent)
  - 86-100: Platinum (outstanding)
```

### Tier Upgrade Criteria
```php
class TierUpgradeService
{
    public function checkUpgradeEligibility(Agent $agent): ?AgentTier
    {
        $score = $this->performanceService->getScore($agent->id);
        $volume = $this->transactionRepo->getMonthlyTotal($agent->id);
        $txnCount = $this->transactionRepo->getMonthlyCount($agent->id);
        $activeDays = $this->transactionRepo->getActiveDaysThisMonth($agent->id);
        $rating = $this->satisfactionService->getAverageRating($agent->id);
        
        $tierConfig = AgentTierConfig::forTier($agent->tier->next());
        if (!$tierConfig) return null;
        
        $eligible = $volume >= $tierConfig->min_monthly_volume
            && $txnCount >= $tierConfig->min_monthly_txns
            && $activeDays >= $tierConfig->min_active_days
            && ($tierConfig->min_rating === null || $rating >= $tierConfig->min_rating)
            && $agent->kyc_status === 'approved'
            && $this->complianceService->hasNoRecentFlags($agent->id);
        
        return $eligible ? $agent->tier->next() : null;
    }
}
```

## ML Model 4: Predictive Float Recommendations (Phase 4)

### Objective
Proactively recommend float top-ups and surplus transfers between agents to optimize network-wide float distribution, minimizing both shortages and idle capital.

### Architecture
```
Input: Cash demand forecast (Model 1) per agent
Input: Current float levels per agent
Input: Agent-to-agent distance matrix
Input: Float transfer cost (opportunity cost of idle float)
Input: Agent tier (affects who can send/receive)

Algorithm: Network flow optimization
  - For each agent with predicted deficit next day:
    1. Find nearest agent(s) with predicted surplus
    2. Calculate optimal transfer amount
    3. Recommend: transfer X SYP from Agent A to Agent B
    4. If no nearby surplus: recommend wallet top-up

Output: Network-wide float optimization plan
  - List of recommended intra-network transfers
  - List of recommended wallet top-ups
  - Estimated cost savings (reduced idle float, reduced shortages)
```

### Expected Business Impact
| Metric | Before AI | After AI (Year 1) |
|--------|-----------|-------------------|
| Float low incidents per month | 3,000+ | <500 |
| Average idle float (excess) | 40% of total float | 20% |
| Agent revenue loss from shortage | 15% potential revenue | <3% |
| Manual float top-ups required | 80% proactive | 30% proactive (70% AI-triggered) |
| Customer cash-out failures (agent out of cash) | 5% of attempts | <0.5% |
