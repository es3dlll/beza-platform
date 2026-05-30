# Government Collections AI Integration

## AI Use Cases

### 1. Smart Payment Prediction & Nudges
```
Input:
- User's payment history (last 12 months)
- Known government deadlines for their tax ID / properties / vehicles
- Ministry calendar (tax season, tuition deadlines, registration renewal)
- User's wallet balance pattern

Output:
- "يبدو أن ضريبة العقار مستحقة بعد ٣٠ يوماً. رصيدك الحالي كافٍ للتسديد."
- "ننصحك بتوفير ١٠,٠٠٠ ل.س أسبوعياً لضريبة الدخل السنوية."
- "مخالفة مرورية جديدة على مركبتك — فترة الخصم ٥٠٪ متبقية ٢٠ يوماً."

Implementation:
- Python microservice: government-ml.beza.sy
- Model: LightGBM classifier trained on payment patterns
- Feature: days_to_deadline, wallet_balance, previous_year_paid, late_penalty_rate
- Inference: every 24h for users with upcoming deadlines
```

### 2. Anomaly Detection for Reconciliation
```
Input:
- Beza transaction records for a period
- Ministry payment records for the same period
- Historical reconciliation patterns

Output:
- Suspicious transactions flagged for manual review
- Likely explanation for mismatches (e.g., "amount differs by exactly 1% — likely fee calculation difference")
- "تم كشف 3 معاملات غير متطابقة. 1 منها خطأ في التاريخ، 2 منها اختلاف في المبلغ بنسبة 0.5%"

Implementation:
- Anomaly detection model (Isolation Forest)
- Rule-based pattern matcher for common discrepancy types
- Auto-resolution for known patterns (e.g., fixed fee rounding)
- Escalation to human for novel patterns
```

### 3. Receipt AI Assistant
```
Input:
- User uploads a photo of a paper government receipt
- "ارفع صورة الإيصال الورقي"

Output:
- Extracted: biller name, amount, date, reference number
- Matched against Beza records
- "تم العثور على هذه المعاملة في سجلاتنا بتاريخ ١٠ مارس ٢٠٢٥"
- Or: "لم نجد هذه المعاملة — قد تحتاج إلى مراجعة الوزارة"

Implementation:
- OCR using Tesseract with Arabic language pack
- Fine-tuned for Syrian government receipt format
- Named entity extraction for biller name, amounts (Arabic-Indic digits)
- Receipt matching against government_transactions table
```

### 4. Ministry Query Caching & Prediction
```
Input:
- Ministry API query patterns (tax IDs, passport numbers, student IDs)
- Time of day, day of week, season (tax season, exam results)
- Ministry API latency and availability

Output:
- Predicted query result for repeated lookups (cache with TTL)
- "من المحتمل أن تكون ضريبة العقار لهذا المكلف ٢٥٠,٠٠٠ ل.س (بناءً على العام السابق)"
- Pre-fetch obligations for users who viewed them recently

Implementation:
- Cache-aside pattern with Redis
- Predictive pre-fetch triggered by user behaviour (e.g., opened government hub)
- Ministry-specific TTL: MoF tax data = 6h, Traffic fines = 1h, Tuition = 3h
```

### 5. Smart Classification of Ministry Payments
```
Input:
- Payment description from ministry system (unstructured Arabic text)
- "رسم تجديد ترخيص مركبة خصوصية 2025 — محافظة دمشق"

Output:
- Classified service type: "vehicle_registration"
- Mapped biller: "TRAF" (Traffic Directorate)
- Extracted amounts, dates, vehicle type
- Confidence score

Implementation:
- Fine-tuned Arabic BERT (AraBERT) classifier
- Training data: 10K+ labelled ministry payment descriptions
- Fallback to rule-based regex for known patterns
```

## Data Requirements for AI Models

| Use Case | Training Data | Features | Labels |
|----------|--------------|----------|--------|
| Payment prediction | 6 months of user payment history | Deadline proximity, balance, past behaviour, season | Did user pay on time? |
| Anomaly detection | 12 months of reconciliation data | Amount variance, date diff, reference pattern | Is this a genuine mismatch? |
| Receipt OCR | 5K scanned government receipts | Pixel data (image) | Extracted text fields |
| Payment classification | 10K ministry descriptions | Arabic text | Service type enum |

## AI Service Architecture

```
┌────────────────────────────────────────────────────────┐
│              AI Microservice (Python FastAPI)           │
├────────────────────────────────────────────────────────┤
│  /api/v1/ai/predict-payment-dates                      │
│  /api/v1/ai/detect-anomalies                           │
│  /api/v1/ai/parse-receipt                              │
│  /api/v1/ai/classify-payment                           │
│  /api/v1/ai/cache-warmup                               │
├────────────────────────────────────────────────────────┤
│  Models:                                                │
│  - LightGBM (payment prediction)                        │
│  - Isolation Forest (anomaly detection)                 │
│  - Tesseract + AraBERT (OCR + classification)           │
│  - Redis (cache + predictions)                          │
└────────────────────────────────────────────────────────┘
```

## Ethical AI Principles
1. **No denial of service based on prediction** — AI only suggests, never blocks
2. **Transparent reasoning** — Explain why a payment was flagged (feature importance)
3. **Feedback loop** — Users can correct AI predictions; corrections improve model
4. **Privacy-preserving** — Models trained on anonymised data; no raw ministry references in logs
5. **Human-in-the-loop** — Reconciliation anomalies always reviewed by human before action
