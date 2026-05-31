# المتطلبات غير الوظيفية — Non-Functional Requirements

## NFR1: Performance
| ID | Requirement | Target |
|----|-------------|--------|
| NFR1.1 | Application submission response time | < 2 seconds |
| NFR1.2 | Credit score calculation time | < 5 seconds |
| NFR1.3 | Auto-decision engine (low-risk) | < 10 seconds |
| NFR1.4 | Disbursement processing time | < 30 seconds |
| NFR1.5 | Repayment schedule generation | < 1 second |
| NFR1.6 | Dashboard queries (portfolio data) | < 3 seconds |
| NFR1.7 | Concurrent users supported | 5,000 |
| NFR1.8 | API throughput | 500 TPS |

## NFR2: Availability & Reliability
| ID | Requirement | Target |
|----|-------------|--------|
| NFR2.1 | System uptime (financing service) | 99.95% |
| NFR2.2 | Scheduled maintenance window | Sundays 02:00–04:00 AM |
| NFR2.3 | Disaster recovery RPO | < 5 minutes |
| NFR2.4 | Disaster recovery RTO | < 30 minutes |
| NFR2.5 | Automated failover | Active-active in two AZs |
| NFR2.6 | Repayment auto-deduction reliability | 100% (atomic transaction) |

## NFR3: Security
| ID | Requirement | Target |
|----|-------------|--------|
| NFR3.1 | Data encryption at rest | AES-256 |
| NFR3.2 | Data encryption in transit | TLS 1.3 |
| NFR3.3 | PII data masking in logs | All sensitive fields |
| NFR3.4 | API authentication | JWT + OAuth 2.0 |
| NFR3.5 | Rate limiting per user | 100 req/min |
| NFR3.6 | Audit logging (all financial transactions) | Immutable, 7-year retention |

## NFR4: Scalability
| ID | Requirement | Target |
|----|-------------|--------|
| NFR4.1 | Horizontal scaling for scoring service | Auto-scale (5–50 pods) |
| NFR4.2 | Database read replicas | 3 (primary + 2 replicas) |
| NFR4.3 | Queue processing (jobs) | Kafka-based, partitions by user_id |
| NFR4.4 | Storage growth projection | 500 GB/year |

## NFR5: Compliance
| ID | Requirement | Target |
|----|-------------|--------|
| NFR5.1 | Sharia audit trail | All contracts, transactions immutable |
| NFR5.2 | Data retention | 7 years (CBS regulation) |
| NFR5.3 | Right to deletion (GDPR-style) | For non-active accounts > 5 years |
| NFR5.4 | Regulator reporting | Automated monthly CBS reports |

## NFR6: Localization
| ID | Requirement | Target |
|----|-------------|--------|
| NFR6.1 | Primary language | Arabic (MSA + Levantine) |
| NFR6.2 | Secondary language | English (for diaspora) |
| NFR6.3 | RTL layout | Full RTL support |
| NFR6.4 | Number formatting | Arabic-Indic numerals (optional) |
| NFR6.5 | Currency | SYP (with future SYP-TRY-SYP-USD multi-currency) |

## NFR7: Mobile & Offline
| ID | Requirement | Target |
|----|-------------|--------|
| NFR7.1 | Offline application draft | Local storage, sync when online |
| NFR7.2 | Push notification delivery | < 30 seconds |
| NFR7.3 | App size impact | < 5 MB added |
| NFR7.4 | Low-bandwidth mode | < 100 KB per API call |
