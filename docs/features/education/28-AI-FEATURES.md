# 28 — AI / Machine Learning Features

## 28.1 AI Feature Inventory

| Feature | Model Type | Data Source | Value |
|---|---|---|---|
| **Payment default prediction** | Gradient Boosted Trees (XGBoost) | Payment history, wallet activity | Early warning for schools; proactive reminders |
| **Smart fee reminders** | Reinforcement learning (bandit) | Open rates, payment timing | Optimal channel/time per parent |
| **Fee amount anomaly detection** | Isolation Forest | Historical fee templates | Flag potentially incorrect fee structures |
| **Parent churn prediction** | Logistic Regression | Transaction frequency, support tickets | Target at-risk parents with retention offers |
| **Cash flow forecasting** | LSTM Time Series | Daily TPV, seasonal patterns | For Beza ops: predict settlement needs |
| **Invoice line item auto-tagging** | BERT (Arabic fine-tuned) | School-uploaded fee documents | Suggest categories for manual entries |
| **Fraud detection** | Ensemble (XGBoost + AE) | Payment patterns, device fingerprints | Real-time transaction scoring |
| **Smart receipt search** | Embedding (Arabic Sentence-BERT) | Receipt text | Semantic search over payment history |

## 28.2 Default Prediction Model

### Features
```
- Wallet age (days)
- Number of previous transactions
- % of on-time payments (last 12 months)
- Average wallet balance (last 90 days)
- Number of children enrolled
- Total annual fee amount
- Days since last top-up
- Previous defaults (0/1)
- School tier (public → private → uni)
- Payment method preference
- Hour of day most transactions
- Device fingerprint count
- IP address changes in last 30 days
```

### Implementation
- **Training frequency**: Weekly (Sunday 03:00)
- **Inference**: Real-time (API call on payment initiation)
- **Threshold**: Score < 0.3 → low risk; 0.3-0.7 → medium; > 0.7 → high risk
- **Action on high risk**: Require OTP; limit to 500K SYP first payment; suggest instalments
- **Feedback loop**: Actual defaults → retrain model

## 28.3 Smart Reminder Optimisation

### Multi-Armed Bandit Setup
- **Arms**: WhatsApp morning / WhatsApp evening / SMS morning / SMS evening / Push notification / No reminder
- **Reward**: Was payment made within 7 days of reminder?
- **Update**: Thompson sampling — explore 20%, exploit 80%
- **Per-parent personalisation**: Each parent has independent bandit after 5+ interactions
- **Cold start**: Use school-level aggregate for first 5 reminders

## 28.4 Cash Flow Forecasting

- **Input**: Daily TPV (education), seasonality (term start/end), trend (growth), holiday calendar, macroeconomic indicators (SYP exchange rate)
- **Output**: Predicted TPV for next 7/30/90 days with confidence intervals
- **Use**: Reserve planning, settlement liquidity management, school credit line sizing
- **Refresh**: Daily at 06:00

## 28.5 Arabic NLP for Invoice Tagging

For schools that upload fee schedules as PDF/images:
1. OCR (Tesseract with Arabic language pack) extracts text
2. Fine-tuned Arabic BERT (AraBERT / CAMeLBERT) classifies line items:
   - "رسوم تعليم" → Tuition
   - "كتب ومناهج" → Books
   - "نشاطات لاصفية" → Activities
   - "مواصلات" → Transport
   - "تسجيل" → Registration
3. Confidence threshold: 85% for auto-tagging, <85% → flag for manual review

## 28.6 Data Requirements for AI

| Dataset | Minimum Volume | Update |
|---|---|---|
| Training: Payment history | 50,000+ transactions | Weekly |
| Training: Default labels | 1,000+ defaults | Monthly |
| Training: Parent behaviour | 10,000+ parent-months | Weekly |
| Validation: Holdout set | 20% of training volume | Each cycle |
| Embedding corpus | 100,000+ Arabic receipts | Quarterly |

## 28.7 AI Ethics & Governance

- **Explainability**: All credit/risk decisions must have an explanation (SHAP values stored alongside decision)
- **Fairness**: Monitor for bias against governorate, gender, school type
- **Human-in-the-loop**: High-risk predictions (default score > 0.7) always reviewed by human
- **Data consent**: Parents explicitly consent to AI features during onboarding (opt-out available)
- **Audit trail**: Every AI decision logged with model version, features, score, and outcome
- **Bias testing**: Quarterly fairness audit across demographic groups; retrain if bias detected
