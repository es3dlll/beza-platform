# Production Readiness Checklist — Beza Platform V1 Launch

> **Platform:** Beza — Syrian Digital Wallet & Financial Services
> **Version:** V1.0
> **Target Launch:** 2026-07-01
> **Owner:** Program Management
> **Regulator:** Central Bank of Syria (CBS)

---

## 1. Infrastructure

### 1.1 Compute & Hosting

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 1.1 | Production server provisioning | Syrian Cloud (dam-cloud.sy) or local DC — 3 app nodes (8 vCPU, 32GB RAM each) | DevOps | ☐ | T-30 |
| 1.2 | Database servers | MySQL 8.0 — Primary + Replica (16 vCPU, 64GB RAM, NVMe SSD) | DevOps | ☐ | T-30 |
| 1.3 | Cache cluster | Redis 7 — 3-node cluster (4 vCPU, 16GB RAM each) | DevOps | ☐ | T-30 |
| 1.4 | Message broker | RabbitMQ 3.12 — 3-node cluster (4 vCPU, 8GB RAM each) | DevOps | ☐ | T-30 |
| 1.5 | Object storage | MinIO cluster (S3-compatible) — 4 nodes, 4TB each | DevOps | ☐ | T-30 |
| 1.6 | Staging environment | 1:1 replica of production (scaled down) | DevOps | ☐ | T-45 |
| 1.7 | CI/CD pipeline | GitLab CI / GitHub Actions — build, test, deploy | DevOps | ☐ | T-45 |
| 1.8 | Container orchestration | Docker Compose (single host) or Kubernetes (multi-host) | DevOps | ☐ | T-30 |
| 1.9 | Infrastructure as Code | Terraform or Ansible playbooks | DevOps | ☐ | T-30 |

### 1.2 Networking

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 2.1 | DNS configuration | Cloudflare — A records, CNAMEs, DNSSEC | DevOps | ☐ | T-21 |
| 2.2 | CDN / DDoS protection | Cloudflare Enterprise or Syrian CDN provider | DevOps | ☐ | T-21 |
| 2.3 | SSL/TLS certificates | Let's Encrypt (auto-renewal) + internal CA for microservices | DevOps | ☐ | T-21 |
| 2.4 | Load balancer | Nginx/HAProxy — SSL termination, HTTP/2, WebSocket support | DevOps | ☐ | T-21 |
| 2.5 | Firewall rules | IP whitelists for admin, API gateway rate limits | DevOps | ☐ | T-21 |
| 2.6 | VPN / Private network | WireGuard or IPSec tunnel between app and DB tiers | DevOps | ☐ | T-21 |
| 2.7 | Syriatel/MTN IP whitelist | Static IPs for USSD gateway and SMPP bindings | DevOps | ☐ | T-21 |
| 2.8 | Bank SFTP IPs | BSO, Bemo, SIIB SFTP server IPs whitelisted | DevOps | ☐ | T-21 |
| 2.9 | CBS API connectivity | Mutual TLS certificates configured for CBS rate feed & reporting | DevOps | ☐ | T-21 |

### 1.3 Monitoring & Observability

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 3.1 | Metric collection | Prometheus — all services instrumented (CPU, memory, requests, latency) | DevOps | ☐ | T-21 |
| 3.2 | Dashboarding | Grafana — operational dashboards (per service, business metrics) | DevOps | ☐ | T-21 |
| 3.3 | Log aggregation | Elasticsearch + Logstash + Kibana (ELK) — structured JSON logging | DevOps | ☐ | T-21 |
| 3.4 | Distributed tracing | OpenTelemetry + Jaeger — trace every transaction end-to-end | DevOps | ☐ | T-21 |
| 3.5 | Uptime monitoring | External synthetic checks every 1min (multi-region) | DevOps | ☐ | T-14 |
| 3.6 | API health checks | `/health` endpoint per service (liveness + readiness) | Engineering | ☐ | T-21 |
| 3.7 | Database monitoring | MySQL metrics (connections, slow queries, replication lag) | DevOps | ☐ | T-21 |
| 3.8 | Queue monitoring | RabbitMQ queue depth, consumer lag, dead letter count | DevOps | ☐ | T-21 |
| 3.9 | SMS delivery monitoring | SMPP delivery rate, latency per provider, fallback activation | DevOps | ☐ | T-14 |
| 3.10 | USSD session monitoring | Session success rate, timeouts, errors per menu | DevOps | ☐ | T-14 |

### 1.4 Alerting

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 4.1 | P0 alerts | Telegram bot (instant) + SMS to on-call — service down, DB down, queue flooding | DevOps | ☐ | T-14 |
| 4.2 | P1 alerts | Telegram + email — high latency, error rate > 1%, disk > 80% | DevOps | ☐ | T-14 |
| 4.3 | P2 alerts | Email — warning thresholds, slow growth trends | DevOps | ☐ | T-14 |
| 4.4 | On-call rotation | 24/7 schedule — 3 engineers (primary, secondary, tertiary) | DevOps | ☐ | T-14 |
| 4.5 | Escalation matrix | Engineer (15min) → Team Lead (30min) → CTO (60min) | Ops | ☐ | T-14 |
| 4.6 | Silent hours / maintenance window | Tuesday 02:00-04:00 AM (lowest usage) | Ops | ☐ | T-14 |

### 1.5 Backup & Disaster Recovery

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 5.1 | Database — hourly snapshot | MySQL snapshot every hour, retained 24h | DevOps | ☐ | T-21 |
| 5.2 | Database — daily full backup | Full mysqldump at 01:00, retained 30 days | DevOps | ☐ | T-21 |
| 5.3 | Database — weekly archive | Weekly backup retained 12 months | DevOps | ☐ | T-21 |
| 5.4 | Backup destination | S3-compatible (MinIO) + encrypted (AES-256-GCM) | DevOps | ☐ | T-21 |
| 5.5 | Backup restoration test | Monthly restore drill — verify integrity | DevOps | ☐ | T-14 |
| 5.6 | Application backup | Source code, configs, .env templates in Git + encrypted vault | DevOps | ☐ | T-21 |
| 5.7 | DR plan documented | Runbook: failover to replica (RTO < 5min, RPO < 1min) | DevOps | ☐ | T-14 |
| 5.8 | Multi-region DR (future) | Secondary DC in another Syrian city (Phase 2) | Ops | ☐ | Post-V1 |

---

## 2. Security

### 2.1 Application Security

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 6.1 | Penetration test | Third-party security audit (e.g., Syrian cyber security firm) | CTO | ☐ | T-21 |
| 6.2 | OWASP Top 10 scan | Automated scan passed (SAST + DAST) | Engineering | ☐ | T-21 |
| 6.3 | Dependency scan | Snyk / npm audit / composer audit — zero critical/high vulnerabilities | Engineering | ☐ | T-21 |
| 6.4 | Secrets management | HashiCorp Vault or Laravel encrypted .env — no secrets in code | Engineering | ☐ | T-21 |
| 6.5 | Input validation | JSON Schema validation on API Gateway, ORM parameterization | Engineering | ☐ | T-30 |
| 6.6 | Rate limiting | Configured per endpoint group (see API Matrix) | Engineering | ☐ | T-21 |
| 6.7 | CORS configuration | Whitelist origins — no wildcard in production | Engineering | ☐ | T-21 |
| 6.8 | Security headers | Content-Security-Policy, X-Frame-Options, HSTS, X-Content-Type-Options | Engineering | ☐ | T-21 |
| 6.9 | MFA for admin | TOTP mandatory for all admin accounts | Engineering | ☐ | T-21 |
| 6.10 | Session management | JWT with 15min access, 7d refresh, device binding, rotation on login | Engineering | ☐ | T-21 |
| 6.11 | Idempotency keys | Required for all mutating financial endpoints | Engineering | ☐ | T-21 |
| 6.12 | API versioning | `/api/v1/` — backwards compatible for at least 2 versions | Engineering | ☐ | T-21 |

### 2.2 Data Security

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 7.1 | Encryption at rest | AES-256-GCM for all PII and financial data | Engineering | ☐ | T-21 |
| 7.2 | Encryption in transit | TLS 1.3 for all external + internal service communication | DevOps | ☐ | T-21 |
| 7.3 | KYC document storage | Encrypted, isolated S3 bucket — access logged, access limited to Compliance team | Engineering | ☐ | T-21 |
| 7.4 | PII minimization | Store only essential PII, tokenize where possible | Engineering | ☐ | T-21 |
| 7.5 | Data classification policy | Public → Internal → Confidential → Restricted — documented | CTO | ☐ | T-30 |
| 7.6 | Data retention policy | Transaction data: 10 years (CBS requirement), KYC docs: 5 years post-closure | Compliance | ☐ | T-30 |
| 7.7 | Data deletion process | User right to deletion (GDPR-aligned), with legal hold override | Compliance | ☐ | T-30 |
| 7.8 | Audit log — append-only | MySQL event + backup — immutable, no DELETE/UPDATE allowed | Engineering | ☐ | T-21 |
| 7.9 | Audit log retention | 7 years online, 10 years archived | Compliance | ☐ | T-21 |

### 2.3 Infrastructure Security

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 8.1 | OS hardening | CIS benchmark — disable root SSH, key-only auth, fail2ban | DevOps | ☐ | T-21 |
| 8.2 | DB access control | No direct DB access from internet — only via app servers + VPN | DevOps | ☐ | T-21 |
| 8.3 | Redis password | Redis AUTH + binding to internal network only | DevOps | ☐ | T-21 |
| 8.4 | RabbitMQ access | TLS + user permissions per vhost | DevOps | ☐ | T-21 |
| 8.5 | Docker security | Non-root containers, read-only root filesystem, no privileged mode | DevOps | ☐ | T-21 |
| 8.6 | Regular security updates | Weekly patching window — automated dependency updates | DevOps | ☐ | T-14 |
| 8.7 | Vulnerability scanning | Weekly Trivy / Clair scan on container images | DevOps | ☐ | T-14 |
| 8.8 | Access review | Monthly review of all admin accounts, API keys, SSH keys | CTO | ☐ | T-14 |

---

## 3. Regulatory & Compliance

### 3.1 Licensing

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 9.1 | CBS e-money license | License obtained from Central Bank of Syria | CEO/Legal | ☐ | T-60 |
| 9.2 | License conditions | All conditions in license agreement met (capital, governance, reporting) | CEO/Legal | ☐ | T-30 |
| 9.3 | Commercial registry | Syrian Commercial Register updated with fintech activity | Legal | ☐ | T-60 |
| 9.4 | Data center approval | CBS-approved data center or certified local DC | CEO/CTO | ☐ | T-30 |

### 3.2 AML/CFT

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 10.1 | AML/CFT policy | Documented and approved by board | Compliance | ☐ | T-45 |
| 10.2 | AML/CFT officer | Designated MLRO (Money Laundering Reporting Officer) appointed | CEO | ☐ | T-60 |
| 10.3 | Customer screening | World-Check integration for PEP/sanctions screening | Engineering | ☐ | T-30 |
| 10.4 | Transaction monitoring | Rule-based + ML scoring for suspicious transactions | Engineering | ☐ | T-30 |
| 10.5 | SAR filing process | Workflow documented — internal SAR → CBS submission within 24h | Compliance | ☐ | T-30 |
| 10.6 | Threshold reporting | All transactions > 1,000,000 SYP automatically flagged | Engineering | ☐ | T-21 |
| 10.7 | Cash transaction reporting | Daily report to CBS for all cash-in/out > 500,000 SYP | Engineering | ☐ | T-21 |
| 10.8 | Travel rule compliance | Beneficiary data sharing for cross-border transfers > $1,000 | Engineering | ☐ | Post-V1 |
| 10.9 | AML training | All staff trained on AML/CFT procedures — annual certification | Compliance | ☐ | T-30 |
| 10.10 | Independent audit | Annual AML/CFT audit by external auditor | Compliance | ☐ | Post-V1 |

### 3.3 CBS Reporting

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 11.1 | Daily transaction report | Volume, value, type breakdown — submitted by 09:00 next day | Engineering | ☐ | T-21 |
| 11.2 | Monthly financial report | Balance sheet, P&L, wallet balances, agent float positions | Finance | ☐ | T-30 |
| 11.3 | Quarterly compliance report | KYC stats, AML alerts, SARs filed, declined transactions | Compliance | ☐ | T-30 |
| 11.4 | Annual external audit | CBS-approved auditor — financial statements, internal controls | CEO/Finance | ☐ | Post-V1 |
| 11.5 | CBS system access | CBS API credentials, certificate, and test environment access | DevOps | ☐ | T-30 |
| 11.6 | Reporting template | CBS-standardized Excel/CSV templates configured and auto-populated | Engineering | ☐ | T-21 |

### 3.4 Data Protection

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 12.1 | Privacy policy | Published on app and website — user consent obtained at registration | Legal | ☐ | T-30 |
| 12.2 | Data protection officer | DPO appointed (can be MLRO in initial phase) | CEO | ☐ | T-30 |
| 12.3 | User consent management | Granular consent for data processing (SMS, location, contacts) | Engineering | ☐ | T-21 |
| 12.4 | Data breach response plan | Documented procedure — 72h notification to regulator | Compliance | ☐ | T-30 |
| 12.5 | Cross-border data transfer | Remittance beneficiary data — DPA with MTO partners | Legal | ☐ | T-30 |

### 3.5 Sharia Compliance (if applicable for V1.5 savings)

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 13.1 | Sharia advisory board | 3 scholars with fintech experience appointed | CEO | ☐ | Post-V1 |
| 13.2 | Fatwa for platform | Sharia opinion on digital wallet, transfers, fees | Board | ☐ | Post-V1 |
| 13.3 | Sharia audit | Annual compliance audit | Scholars | ☐ | Post-V1 |

---

## 4. Operations

### 4.1 Support

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 14.1 | Support ticket system | Zendesk / Freshdesk / self-hosted (e.g., OTRS) — integrated with app | Ops | ☐ | T-21 |
| 14.2 | Call center | Local Syrian number (09xx-xxx-xxx) — 08:00-20:00, 7 days | Ops | ☐ | T-14 |
| 14.3 | Support team | 5 agents (Arabic/English), 1 team lead | Ops | ☐ | T-14 |
| 14.4 | Escalation matrix | L1 (5min) → L2 (15min) → L3 engineering (1h) | Ops | ☐ | T-14 |
| 14.5 | FAQ / Help center | In-app FAQ (Arabic + English) — 50+ articles | Product | ☐ | T-14 |
| 14.6 | Support SLA | P0: 1h response, P1: 4h, P2: 24h, P3: 72h | Ops | ☐ | T-14 |
| 14.7 | Dispute handling process | Documented flow — user disputes → review → resolution within 48h | Ops | ☐ | T-14 |

### 4.2 Agent Network

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 15.1 | Agent recruitment | 200 agents onboarded in Damascus for launch | Agent Ops | ☐ | T-21 |
| 15.2 | Agent training | 2-day training program (KYC, operations, fraud awareness) | Agent Ops | ☐ | T-21 |
| 15.3 | Agent materials | POS terminal/tablet, branded signage, QR code, receipt printer | Agent Ops | ☐ | T-14 |
| 15.4 | Agent float funding | Initial float deposited in agent bank accounts | Finance | ☐ | T-7 |
| 15.5 | Agent field team | 3 regional supervisors (Damascus, Aleppo, Latakia) | Agent Ops | ☐ | T-14 |
| 15.6 | Agent commission | Commission schedule communicated, first month guaranteed min income | Finance | ☐ | T-14 |
| 15.7 | Agent SLA | Float top-up within 2h, technical support hotline | Agent Ops | ☐ | T-14 |
| 15.8 | Agent KYC | All agents KYC-complete, registered with CBS as MSB | Compliance | ☐ | T-21 |

### 4.3 Banking Partners

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 16.1 | BSO Bank account | Settlement account opened — SYP | Finance | ☐ | T-30 |
| 16.2 | Bemo Bank account | Settlement account opened — SYP | Finance | ☐ | T-30 |
| 16.3 | SIIB Bank account | Settlement account opened — SYP + USD | Finance | ☐ | T-30 |
| 16.4 | Bank integration | SFTP access configured for all 3 banks | DevOps | ☐ | T-14 |
| 16.5 | Test settlement | Test transaction settled via each bank (1 SYP test) | Finance | ☐ | T-7 |
| 16.6 | Bank fee schedule | Fee agreement signed with each bank | Finance | ☐ | T-21 |
| 16.7 | Bank cutoff times | Confirmed daily cutoff and settlement times per bank | Finance | ☐ | T-21 |

### 4.4 Telco Partners

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 17.1 | Syriatel SMPP | SMPP binding active and tested | Engineering | ☐ | T-14 |
| 17.2 | MTN SMPP | SMPP binding active and tested | Engineering | ☐ | T-14 |
| 17.3 | Syriatel USSD | USSC short code *123# active on Syriatel network | Engineering | ☐ | T-14 |
| 17.4 | MTN USSD | USSD short code *123# active on MTN network | Engineering | ☐ | T-14 |
| 17.5 | SMS throughput test | 100 SMS/min sustained — both providers | Engineering | ☐ | T-7 |
| 17.6 | USSD latency test | P99 < 2s menu response — both providers | Engineering | ☐ | T-7 |
| 17.7 | Syriatel/MTN billing | Bill payment integration tested end-to-end | Engineering | ☐ | T-14 |

---

## 5. Testing

### 5.1 Performance Testing

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 18.1 | Load test — P2P transfer | 500 concurrent users, 100 TPS sustained for 30 min | Engineering | ☐ | T-14 |
| 18.2 | Load test — agent cash-in | 200 concurrent agents, 50 TPS | Engineering | ☐ | T-14 |
| 18.3 | Load test — USSD | 100 concurrent USSD sessions, 30 TPS | Engineering | ☐ | T-14 |
| 18.4 | Load test — admin panel | 50 concurrent admins searching and viewing | Engineering | ☐ | T-14 |
| 18.5 | Stress test | 2x expected peak load — identify breaking point | Engineering | ☐ | T-14 |
| 18.6 | Soak test | Sustained 80% peak load for 4h — memory leak detection | Engineering | ☐ | T-14 |
| 18.7 | Spike test | Sudden 5x traffic spike — verify auto-scaling | Engineering | ☐ | T-14 |
| 18.8 | P99 latency validation | Internal API calls < 200ms at peak load | Engineering | ☐ | T-14 |
| 18.9 | End-to-end transaction time | P2P < 1s, agent cash < 2s, remittance < 5s | Engineering | ☐ | T-14 |

### 5.2 Resilience Testing

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 19.1 | DB failover test | Primary DB failure → replica promoted within 60s | DevOps | ☐ | T-7 |
| 19.2 | Redis failover test | Master Redis failure → replica promoted automatically | DevOps | ☐ | T-7 |
| 19.3 | RabbitMQ queue recovery | Queue purge → messages requeued from DB | DevOps | ☐ | T-7 |
| 19.4 | App server failure | One app node dies → requests routed to remaining nodes | DevOps | ☐ | T-7 |
| 19.5 | Network partition test | Service isolation → queuing works, recovery replayed | DevOps | ☐ | T-7 |
| 19.6 | Backup restoration test | Full DB restore from backup — verified within 2h | DevOps | ☐ | T-7 |
| 19.7 | Rate limiter test | Flood requests → 429 returned, system stable | Engineering | ☐ | T-7 |

### 5.3 Security Testing

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 20.1 | Penetration test | Third-party pen test — all critical/high findings fixed | CTO | ☐ | T-14 |
| 20.2 | OWASP Top 10 scan | Automated scan — zero findings | Engineering | ☐ | T-14 |
| 20.3 | Fraud scenario test | Known fraud patterns blocked by rules engine | Engineering | ☐ | T-7 |
| 20.4 | Injection test | SQLi, NoSQLi, LDAPi — all inputs validated | Engineering | ☐ | T-14 |
| 20.5 | Authentication test | JWT tampering, session hijacking, MFA bypass | Engineering | ☐ | T-14 |
| 20.6 | Rate limit bypass test | Distributed attacks — rate limiting holds | Engineering | ☐ | T-7 |
| 20.7 | IDOR test | User A cannot access User B's data via API | Engineering | ☐ | T-14 |

### 5.4 Functional Testing

| # | Item | Details | Owner | Status | Target |
|---|------|---------|-------|--------|--------|
| 21.1 | P2P transfer — happy path | Full flow: login → send → receive → receipt | QA | ☐ | T-14 |
| 21.2 | P2P transfer — insufficient balance | Error path: validation, error message, retry | QA | ☐ | T-14 |
| 21.3 | P2P transfer — reversal | Failed txn reversal, balance restored | QA | ☐ | T-14 |
| 21.4 | Agent cash-in/out | All states: success, failure, reversal, timeout | QA | ☐ | T-14 |
| 21.5 | Agent float management | Top-up, transfer, mismatch detection | QA | ☐ | T-14 |
| 21.6 | FX conversion | Rate lock, execution, expiry, cancellation | QA | ☐ | T-14 |
| 21.7 | Remittance — full cycle | Submit → screen → MTO → paid → notification | QA | ☐ | T-14 |
| 21.8 | Remittance — blocked | Sanctions hit → block → reversal → notification | QA | ☐ | T-14 |
| 21.9 | Bill payment — Syriatel | Inquiry → pay → receipt → provider settlement | QA | ☐ | T-14 |
| 21.10 | Bill payment — PEED | Same flow, different provider | QA | ☐ | T-14 |
| 21.11 | KYC Tier 1 | Upload → auto-verify → limits increased | QA | ☐ | T-14 |
| 21.12 | KYC Tier 2 | Upload → manual review → approve/reject | QA | ☐ | T-14 |
| 21.13 | USSD — all menus | `*123#` through `*123*9#` on Syriatel + MTN | QA | ☐ | T-7 |
| 21.14 | USSD — error cases | Invalid input, timeout, insufficient balance | QA | ☐ | T-7 |
| 21.15 | SMS — OTP delivery | OTP received within 10s on Syriatel + MTN | QA | ☐ | T-7 |
| 21.16 | SMS — transaction alerts | All 12 notification types delivered correctly | QA | ☐ | T-7 |
| 21.17 | Admin — user search | Filters, pagination, export | QA | ☐ | T-14 |
| 21.18 | Admin — KYC review | Document viewing, approve/reject flow | QA | ☐ | T-14 |
| 21.19 | Admin — transaction reversal | Full reversal flow with audit trail | QA | ☐ | T-14 |
| 21.20 | Admin — SAR filing | Create, review, submit SAR workflow | QA | ☐ | T-14 |
| 21.21 | Dispute flow | User disputes → support reviews → resolved | QA | ☐ | T-14 |
| 21.22 | Multi-language | Arabic/English/Kurdish — all screens verified | QA | ☐ | T-14 |
| 21.23 | Offline behavior | No network → cached data → reconnect → sync | QA | ☐ | T-14 |
| 21.24 | Force update | Old app version → update prompt → app store | QA | ☐ | T-14 |

---

## 6. Launch Checklist (T-7 Days)

### 6.1 Feature Readiness

| # | Item | Owner | Status | Dependencies |
|---|------|-------|--------|-------------|
| L1 | All Tier A features code-complete and merged to `main` | Engineering | ☐ | — |
| L2 | All critical bugs fixed (no P0/P1 open in tracker) | Engineering | ☐ | Bug tracker |
| L3 | All Tier A features QA-signed-off | QA | ☐ | L1 |
| L4 | Performance test results reviewed (P99 within targets) | Engineering | ☐ | Section 18 |
| L5 | Security scan report reviewed, all findings addressed | CTO | ☐ | Section 20 |
| L6 | App store builds submitted (Google Play) — allowance for 48h review | Engineering | ☐ | L1 |
| L7 | App store builds submitted (App Store) — allowance for 7-day review | Engineering | ☐ | L1 — START EARLY |
| L8 | Feature flags configured — kill switches for each Tier B feature | Engineering | ☐ | — |

### 6.2 Operations Readiness

| # | Item | Owner | Status | Dependencies |
|---|------|-------|--------|-------------|
| L9 | Ops team on standby — 24/7 on-call schedule published | Ops | ☐ | — |
| L10 | Runbooks printed and digital (P0/P1 scenarios) | Ops | ☐ | — |
| L11 | Support team fully trained on all features | Ops | ☐ | — |
| L12 | Call center number active and tested | Ops | ☐ | Telco |
| L13 | Support ticket system configured with auto-routing | Ops | ☐ | — |
| L14 | Known issues / FAQ published internally for L1 support | Product | ☐ | — |
| L15 | Escalation tree tested — call chain works | Ops | ☐ | — |

### 6.3 Agent Network Readiness

| # | Item | Owner | Status | Dependencies |
|---|------|-------|--------|-------------|
| L16 | 200 agents active in Damascus — KYC complete, trained | Agent Ops | ☐ | Section 15 |
| L17 | Agent floats funded — average 500,000 SYP each | Finance | ☐ | Bank accounts |
| L18 | Agent POS terminals deployed and tested | Agent Ops | ☐ | — |
| L19 | Agent QR codes printed and distributed | Agent Ops | ☐ | — |
| L20 | Agent hotline operational | Agent Ops | ☐ | — |
| L21 | Agent commission for launch month pre-funded | Finance | ☐ | — |
| L22 | Regional supervisors deployed (Damascus) | Agent Ops | ☐ | — |

### 6.4 Financial Readiness

| # | Item | Owner | Status | Dependencies |
|---|------|-------|--------|-------------|
| L23 | Bank settlement accounts funded (sufficient for 7 days of txns) | Finance | ☐ | Section 16 |
| L24 | FX rates manually verified against CBS rate | Finance | ☐ | — |
| L25 | Fee schedule confirmed and configured in system | Finance | ☐ | — |
| L26 | Agent commission rates confirmed and configured | Finance | ☐ | — |
| L27 | Daily settlement batch test run successfully (synthetic data) | Finance | ☐ | — |
| L28 | GL reconciliation test — zero discrepancies | Finance | ☐ | — |
| L29 | Bank SFTP settlement tested — file accepted by bank | Finance | ☐ | Banks |
| L30 | Initial float to agent float top-up mechanism tested | Finance | ☐ | — |

### 6.5 Regulatory Readiness

| # | Item | Owner | Status | Dependencies |
|---|------|-------|--------|-------------|
| L31 | CBS e-money license displayed in app (per license condition) | Legal | ☐ | License |
| L32 | CBS daily reporting automated test run | Engineering | ☐ | — |
| L33 | AML/CFT system test — known test subject flagged | Compliance | ☐ | — |
| L34 | SAR filing test — test SAR filed with CBS (if required) | Compliance | ☐ | — |
| L35 | Privacy policy and terms of service published in app | Legal | ☐ | — |
| L36 | User consent records verified in database | Engineering | ☐ | — |

### 6.6 Communications

| # | Item | Owner | Status | Dependencies |
|---|------|-------|--------|-------------|
| L37 | Launch SMS drafted and approved — targeting initial user list | Marketing | ☐ | — |
| L38 | In-app launch banner prepared | Product | ☐ | — |
| L39 | Social media posts scheduled (Telegram, WhatsApp, Facebook) | Marketing | ☐ | — |
| L40 | Press release prepared (if applicable) | CEO | ☐ | — |
| L41 | Agent network launch materials delivered | Agent Ops | ☐ | — |
| L42 | Internal team comms — all-hands meeting pre-launch | CEO | ☐ | — |

### 6.7 Rollback Plan

| # | Item | Owner | Status | Details |
|---|------|-------|--------|---------|
| L43 | Feature flag kill switches | Engineering | ☐ | Disable any Tier B feature without deploy |
| L44 | DB rollback script | Engineering | ☐ | Last 3 migrations reversible |
| L45 | App version rollback | Engineering | ☐ | Force-update mechanism can revert |
| L46 | Previous deploy artifact | DevOps | ☐ | Last working Docker images tagged |
| L47 | Rollback decision tree | Ops | ☐ | Documented: txn failures, high error rate, fraud outbreak |
| L48 | Go/No-go meeting | Program | ☐ | T-1: final sign-off from all stakeholders |

---

## 7. Post-Launch (First 72 Hours)

| Time Window | Activity | Owner |
|-------------|----------|-------|
| T+0 to T+1h | War room: CTO, Ops lead, Engineering lead, CEO | All |
| T+0 to T+4h | Active monitoring — every 15min check all dashboards | Ops |
| T+4h to T+24h | Monitoring — every 1h, watch for fraud patterns | Ops + Fraud |
| T+24h | Launch retrospective — issues, metrics, user feedback | Program |
| T+48h | First settlement batch reconciliation | Finance |
| T+72h | CBS daily report successfully submitted | Compliance |
| T+72h | All launch metrics reviewed, adjustments made | All |

---

## 8. Key Launch Metrics (Targets)

| Metric | Target | Measurement |
|--------|--------|------------|
| User sign-ups (first 7 days) | 5,000 | New registered users |
| Active agents (first 7 days) | 150 | Agents with ≥ 1 daily txn |
| Transaction volume (first 7 days) | 10,000 | Total txns |
| Transaction value (first 7 days) | 500M SYP | Total value |
| Uptime | 99.9% | Synthetic monitoring |
| P2P transfer success rate | > 99% | Completed / initiated |
| Agent cash-in success rate | > 98% | Completed / initiated |
| SMS delivery rate | > 95% | Delivered / sent |
| USSD session success rate | > 97% | Completed / initiated |
| App crash-free rate | > 99.5% | Firebase/Sentry |
| Average app rating (Week 1) | ≥ 4.0 | Google Play + App Store |
| Support resolution (P0) | < 1h | Ticket system |
| Support resolution (P1) | < 4h | Ticket system |

---

*End of Production Readiness Checklist. 200+ items across 8 domains covering infrastructure, security, regulatory, operations, testing, and launch readiness for the Syria-context Beza platform V1 launch.*
