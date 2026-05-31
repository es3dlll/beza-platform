# المراجع والمصادر — References & Resources

## Internal References

### Beza Platform Documentation
| Document | Description | Location |
|----------|-------------|----------|
| Wallet Feature Bible | Wallet architecture, transaction types | `.opencode/features/wallet/` |
| Savings Feature Bible | Savings products, interest-free structure | `.opencode/features/savings/` |
| Bill Payment Feature Bible | Bill pay history for credit scoring | `.opencode/features/bill-payment/` |
| Agent Network Feature Bible | Agent interaction data for scoring | `.opencode/features/agent-network/` |
| KYC Feature Bible | Identity verification requirements | `.opencode/features/support/` |
| CFE Architecture | Core Financial Engine disbursement flows | `docs/architecture/cfe.md` |
| Notification Service | Push/SMS/WhatsApp notification specs | `docs/services/notifications.md` |
| Platform Security Policy | Encryption, authentication, audit | `docs/security/platform-security.md` |

### Technical Design Documents
| Document | Location |
|----------|----------|
| Financing Database ERD | `db/erd/financing-erd.png` |
| Financing Service API Postman Collection | `api-collections/financing.postman.json` |
| Scoring Model Training Notebook | `ml/notebooks/credit-scoring-v2.ipynb` |
| Contract PDF Templates | `templates/contracts/` |
| Queue Architecture Diagram | `docs/architecture/queue-architecture.md` |

## Sharia References

### Primary Sources
| Source | Reference |
|--------|-----------|
| القرآن الكريم | سورة البقرة 2:275, سورة المائدة 5:1, سورة النساء 4:29, سورة الحديد 57:11 |
| الحديث النبوي | "البيعان بالخيار ما لم يتفرقا" (متفق عليه) |
| الحديث | "الذهب بالذهب رباً إلا هاء وهاء" (مسلم) |
| الحديث | "لعن الله آكل الربا وموكله" (مسلم) |

### AAOIFI Standards
| Standard | Title |
|----------|-------|
| FAS 1 | General Presentation and Disclosure in Financial Statements |
| FAS 2 | Murabaha and Murabaha to the Purchase Orderer |
| FAS 3 | Mudaraba |
| FAS 4 | Qard |
| FAS 7 | Salam and Parallel Salam |
| FAS 10 | Provisions and Reserves |
| Sharia Standard 5 | Guarantees |
| Sharia Standard 8 | Murabaha |
| Sharia Standard 19 | Qard |
| Sharia Standard 40 | Late Payment Penalty |

### Relevant Fatwas
| Issuer | Subject | Reference |
|--------|---------|-----------|
| OIC Fiqh Academy | Murabaha to the purchase orderer | Resolution 51 (1/6) |
| OIC Fiqh Academy | Late payment penalty | Resolution 74 (7/8) |
| Syrian Sharia Board | Digital contracts validity | Fatwa 2024-03 |
| Al-Azhar | Fintech and Islamic finance | Fatwa 2023-15 |

## Regulatory References

### Central Bank of Syria
| Regulation | Subject | Year |
|------------|---------|------|
| Law No. 24 | Banking Law (including Islamic banking) | 2017 |
| Law No. 23 | Consumer Protection in Financial Services | 2019 |
| Circular 124 | Lending Regulations and Capital Adequacy | 2020 |
| Circular 98 | Electronic Payments and Digital Contracts | 2020 |
| Law No. 21 | Anti-Money Laundering and Counter-Terrorism Financing | 2013 |
| Law No. 15 | Personal Data Protection | 2022 |
| Circular 156 | Microfinance Institution Regulations | 2021 |
| Circular 172 | Provisioning and Risk Management Requirements | 2022 |

### International Standards
| Standard | Subject | Publisher |
|----------|---------|-----------|
| Basel III | Capital adequacy, stress testing | BIS |
| IFRS 9 | Financial instruments, expected credit losses | IASB |
| IFRS 16 | Leases | IASB |
| FATF Recommendations | AML/CFT | FATF |

## Technology References

### ML & Data
| Resource | Description | URL |
|----------|-------------|-----|
| XGBoost Documentation | Model training and tuning | https://xgboost.readthedocs.io/ |
| Scikit-learn | Feature engineering, evaluation | https://scikit-learn.org/ |
| SHAP | Model interpretability | https://shap.readthedocs.io/ |
| MLflow | Model registry and tracking | https://mlflow.org/ |

### Infrastructure
| Resource | Description |
|----------|-------------|
| Apache Kafka | Event streaming for financing events |
| Redis | Caching, rate limiting, job queuing |
| PostgreSQL 15 | Primary database |
| Kubernetes | Container orchestration |
| Prometheus + Grafana | Monitoring and dashboards |

### Mobile & Frontend
| Library | Purpose |
|---------|---------|
| React Native | Mobile application framework |
| Zustand | State management |
| TanStack Query (React Query) | Server state, caching, polling |
| react-native-pdf | Contract PDF viewing |
| react-native-signature | E-signature capture |

## Market Research
| Report | Source | Year |
|--------|--------|------|
| Islamic Fintech in MENA | IFN/Ernst & Young | 2025 |
| Financial Inclusion in Syria | World Bank | 2024 |
| Syrian Micro-Enterprise Landscape | UNDP | 2024 |
| Digital Lending Best Practices | CGAP | 2023 |
| Credit Scoring for the Unbanked | IFC | 2024 |

## Contact Information
| Role | Contact |
|------|---------|
| Product Owner (Financing) | financing-pm@beza.sy |
| Sharia Board Secretary | sharia@beza.sy |
| Risk & Compliance | risk@beza.sy |
| ML Engineering | ml@beza.sy |
| Customer Support (Financing) | 1234 (or financing-support@beza.sy) |
