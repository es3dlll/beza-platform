# AI Platform Architecture

## AI Services Overview
```
┌────────────────────────────────────────────────────────────────────┐
│                        AI ORCHESTRATOR                             │
│  Routes between specialized AI services, manages context, cache   │
└────────┬──────────┬──────────┬──────────┬──────────┬──────────────┘
         │          │          │          │          │
    ┌────▼────┐ ┌──▼───┐ ┌───▼────┐ ┌───▼────┐ ┌───▼────────┐
    │  Fraud  │ │ Risk │ │  AML   │ │  NLP   │ │ Document   │
    │ Detect  │ │Score │ │Screen  │ │ Chatbot│ │ OCR+Verify │
    └────────┘ └──────┘ └────────┘ └────────┘ └────────────┘
```

## Fraud Detection Engine
```
Input Signals (100+ per transaction):
  Transaction: amount, frequency, velocity, counterparty history
  Device: fingerprint mismatch, emulator, rooted, IP geolocation
  Behavior: typing speed changes, unusual hours, new location
  Network: VPN detection, Tor exit nodes, known proxy IPs
  Account: age, transaction history, KYC level, linked accounts

Model Architecture:
  Real-time: XGBoost ensemble (inference < 50ms)
  Batch: Deep Neural Network (hourly retraining)
  Feature Store: Redis (hot features), ClickHouse (cold features)

Rules Override:
  Hard blocks: Country sanctions, blacklisted entities, negative list
  Soft blocks: Excess velocity, amount spikes, new device

Output:
  { score: 0-100, decision: "allow|review|block", reasons: [], model_version: "v2.3" }
```

## Risk Scoring Engine
```
Inputs:
  User profile: age, KYC level, transaction history (90 days)
  Device score: trust level 0-100
  Location risk: country risk score (0-100)
  Transaction risk: amount vs average, type, counterparty
  Network risk: social graph, shared devices

Outputs:
  Transaction Risk: 0-100 (used for step-up, limits, flagging)
  User Risk: Low/Medium/High (recalculated daily)
  Account Risk: 0-100 (used for reserve requirements)
```

## NLP Chatbot (Rasa)
```
Languages: Arabic (primary), English (secondary)
Channels: In-app chat, WhatsApp, SMS

Intents (50+):
  Balance, Transfer, Agent Locator, Bill Payment, FX Rates
  Account Statement, Fee Inquiry, Support Ticket, Complaint
  Loan Application, Savings Goal, Card Management, Dispute

Entities:
  Amount, Currency, Date, Phone Number, Agent Name
  Merchant Name, Bill Type, Loan Amount, Card Type

Fallback:
  Confidence < 0.8 → Suggest clarifying options
  Confidence < 0.5 → Transfer to human agent
  Arabic dialect detected → Route to dialect model
```

## Document OCR Pipeline
```
Input: ID card, passport, license, proof of address (photo/scanned)
Preprocessing: Deskew, crop, contrast enhance, remove glare
OCR: Arabic + English text extraction (PaddleOCR)
Field Extraction: Named entity extraction (Name, ID Number, DOB, Expiry)
Validation: MRZ check, format validation, issuing authority DB
Liveness Detection: Eye blink, head turn, random prompt
Output: { status: "verified|manual_review|rejected", confidence: 0-100, fields: {} }
```

## ML Infrastructure
```
Model Training:
  Framework: PyTorch / XGBoost
  Training Frequency: Daily (fraud), Weekly (risk), Monthly (NLP)
  Experiment Tracking: MLflow
  Feature Store: Feast (Redis + ClickHouse)

Model Serving:
  Format: ONNX (cross-platform)
  Infrastructure: Kubernetes (GPU node pool for batch, CPU for real-time)
  Inference: BentoML / Triton Inference Server
  A/B Testing: 10% traffic to new model, compare metrics

Monitoring:
  Prediction drift: PSI (Population Stability Index) daily
  Feature drift: Statistical distributions hourly
  Performance: Precision, Recall, F1, AUC-ROC
  Latency: P50 < 50ms, P99 < 200ms
  Data quality: Missing values, outliers, schema validation
```
