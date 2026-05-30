# Non-Functional Requirements

## Scale & Performance

| ID | Requirement | Target | Rationale |
|----|-------------|--------|-----------|
| NFR-01 | Concurrent beneficiary enrolment | 5,000/min peak | Mass registration after emergency |
| NFR-02 | Batch distribution throughput | 10,000 beneficiaries in < 2 min | Monthly MPC to 500k within 2 hours |
| NFR-03 | Biometric verification latency | < 30 sec end-to-end | Agent queue throughput |
| NFR-04 | API response time (p95) | < 500ms | Dashboard responsiveness |
| NFR-05 | Report generation time | < 5 min for 500k records | Donor deadlines |
| NFR-06 | Concurrent users | 500 NGO staff + 2,000 agents | Peak distribution day |
| NFR-07 | Max beneficiary records | 10M | Full Syria coverage |

## Availability & Reliability

| ID | Requirement | Target | Rationale |
|----|-------------|--------|-----------|
| NFR-08 | System uptime | 99.9% (excluding scheduled maintenance) | Distribution windows are time-sensitive |
| NFR-09 | Disaster recovery | RTO < 1 hour, RPO < 5 min | Funds-in-transit must not be lost |
| NFR-10 | Offline agent mode | Core verification functions must work with sync queue | Camps often lack connectivity |
| NFR-11 | Idempotent distributions | Duplicate trigger must not double-credit | Financial correctness |

## Security & Privacy

| ID | Requirement | Target | Rationale |
|----|-------------|--------|-----------|
| NFR-12 | Beneficiary data encryption at rest | AES-256-GCM | Vulnerable population data |
| NFR-13 | PII tokenisation/log masking | All logs must never expose PII | Privacy under humanitarian law |
| NFR-14 | API authentication | OAuth 2.0 + JWT, mTLS for server-to-server | Fund security |
| NFR-15 | Audit trail | Immutable log of all financial operations | Donor compliance |
| NFR-16 | Data residency | Beneficiary data stored in-region (Jordan/Istanbul) | Sovereignty / UN requirements |

## Compliance

| ID | Requirement | Target | Rationale |
|----|-------------|--------|-----------|
| NFR-17 | Sanctions screening match rate | 100% of names screened against UN/EU/OFAC | Legal obligation |
| NFR-18 | False positive rate | < 5% to avoid blocking legitimate beneficiaries | Humanitarian access |

## Localisation

| ID | Requirement | Target | Rationale |
|----|-------------|--------|-----------|
| NFR-19 | Arabic language support | Full RTL UI, Arabic numerals, Hijri date option | Primary language for beneficiaries |
| NFR-20 | Literacy accommodation | Voice-guided flows, icon-based navigation | Beneficiaries may be illiterate |
| NFR-21 | Script direction | All UI must support RTL layout | Arabic-first design |
