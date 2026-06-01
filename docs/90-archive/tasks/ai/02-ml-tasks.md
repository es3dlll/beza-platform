# AI/ML Tasks — Machine Learning Implementation

## Phase 1: Fraud Detection (Week 1-3)
| Task ID | Description | Est. Hours |
|---------|-------------|------------|
| AI-001 | Set up ML infrastructure (MLflow, Feast feature store, ONNX runtime) | 8 |
| AI-002 | Collect and label historical transaction data (90 days) | 6 |
| AI-003 | Feature engineering: 120+ features for fraud detection | 16 |
| AI-004 | Train XGBoost fraud detection model | 8 |
| AI-005 | Evaluate model: precision > 95%, recall > 90% | 4 |
| AI-006 | Export model to ONNX format | 2 |
| AI-007 | Deploy model to ONNX Runtime inference server | 4 |
| AI-008 | Implement real-time fraud scoring API (< 50ms inference) | 6 |
| AI-009 | Implement canary deployment for model updates (10% traffic) | 4 |
| AI-010 | Implement model monitoring: PSI drift, feature drift, performance | 6 |
| AI-011 | Set up feedback loop (manual review results → retraining data) | 4 |

## Phase 2: Credit Scoring (Week 4-6)
| Task ID | Description | Est. Hours |
|---------|-------------|------------|
| AI-012 | Collect alternative credit data from wallet history | 6 |
| AI-013 | Feature engineering: 80+ features from transaction patterns, savings, agent usage | 12 |
| AI-014 | Train XGBoost credit scoring model | 8 |
| AI-015 | Train default prediction model (users at risk before first missed payment) | 8 |
| AI-016 | Implement credit scoring API | 4 |
| AI-017 | Implement dynamic limit adjustment based on credit score | 4 |
| AI-018 | Create A/B testing framework for credit model versions | 4 |

## Phase 3: NLP & Documents (Week 7-9)
| Task ID | Description | Est. Hours |
|---------|-------------|------------|
| AI-019 | Set up Rasa chatbot infrastructure | 8 |
| AI-020 | Train Arabic NLP model (50+ intents, entity extraction) | 12 |
| AI-021 | Implement in-app chatbot with fallback to human agent | 8 |
| AI-022 | Implement WhatsApp bot integration | 6 |
| AI-023 | Set up document OCR pipeline (ID cards, passports, licenses) | 8 |
| AI-024 | Train Arabic OCR model (PaddleOCR + field extraction) | 8 |
| AI-025 | Implement liveness detection (eye blink, head turn, random prompt) | 8 |
| AI-026 | Implement KYC document validation API | 4 |

## Phase 4: Analytics & Personalization (Week 10-12)
| Task ID | Description | Est. Hours |
|---------|-------------|------------|
| AI-027 | Implement spending insights (transaction categorization, trends) | 6 |
| AI-028 | Implement personalized savings recommendations | 4 |
| AI-029 | Implement agent cash demand forecasting (time-series) | 6 |
| AI-030 | Implement FX rate prediction (5-min forecast) | 6 |
| AI-031 | Implement notification ranking (smart push, suppress irrelevant) | 4 |
| AI-032 | Implement merchant churn prediction | 4 |
| AI-033 | Implement agent performance scoring | 3 |
| AI-034 | Implement dynamic MDR pricing for merchants | 4 |
| AI-035 | Create ML model monitoring dashboard (Grafana) | 6 |
| AI-036 | Document all models, features, and deployment procedures | 6 |
