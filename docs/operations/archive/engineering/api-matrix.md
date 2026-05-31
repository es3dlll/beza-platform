# API Matrix — Beza Platform

> **Last Updated:** 2026-05-29
> **Owner:** Platform Engineering
> **Scope:** Internal (microservice), External (3rd party), Client (app/web/ussd)

---

## 1. Internal API Matrix (Module → Module)

### 1.1 Identity Service APIs

| Provider | Consumer   | Endpoint                                  | Method   | Purpose                          | Frequency       | SLA    | Payload Size |
| -------- | ---------- | ----------------------------------------- | -------- | -------------------------------- | --------------- | ------ | ------------ |
| Identity | Wallet     | `POST /api/v1/identity/validate-session`  | Internal | Validate JWT + session freshness | Per txn         | <50ms  | <1KB         |
| Identity | ALL        | `GET /api/v1/identity/users/{id}`         | Internal | Fetch user profile by ID         | Per request     | <50ms  | <2KB         |
| Identity | ALL        | `POST /api/v1/identity/users/batch`       | Internal | Batch user lookup                | Bulk ops        | <200ms | <10KB        |
| Identity | Auth       | `POST /api/v1/identity/register`          | Internal | Create user identity record      | 1x per user     | <100ms | <1KB         |
| Identity | Auth       | `POST /api/v1/identity/verify-otp`        | Internal | Validate OTP and mark verified   | Per OTP attempt | <50ms  | <1KB         |
| Identity | Auth       | `POST /api/v1/identity/pin/validate`      | Internal | Validate PIN (hashed)            | Per PIN entry   | <30ms  | <1KB         |
| Identity | Auth       | `POST /api/v1/identity/pin/update`        | Internal | Change PIN                       | Per PIN change  | <100ms | <1KB         |
| Identity | Compliance | `GET /api/v1/identity/users/{id}/flags`   | Internal | Fetch user compliance flags      | Per screening   | <50ms  | <1KB         |
| Identity | Compliance | `POST /api/v1/identity/users/{id}/flag`   | Internal | Flag user for compliance         | Per flag        | <100ms | <1KB         |
| Identity | Admin      | `GET /api/v1/identity/users`              | Internal | Search users (paginated)         | Per admin query | <200ms | <10KB        |
| Identity | Admin      | `POST /api/v1/identity/users/{id}/status` | Internal | Update user status               | Per action      | <100ms | <1KB         |

### 1.2 Wallet Service APIs

| Provider | Consumer     | Endpoint                                        | Method   | Purpose                            | Frequency          | SLA    |
| -------- | ------------ | ----------------------------------------------- | -------- | ---------------------------------- | ------------------ | ------ |
| Wallet   | CFE          | `POST /api/v1/cfe/post-entry`                   | Internal | Post double-entry to ledger        | Per txn            | <200ms |
| Wallet   | Fraud        | `POST /api/v1/fraud/screen-transaction`         | Internal | Pre-transaction fraud screening    | Per txn            | <200ms |
| Wallet   | FX           | `GET /api/v1/fx/rates/{pair}`                   | Internal | Get current FX rate for conversion | Per cross-currency | <100ms |
| Wallet   | FX           | `POST /api/v1/fx/convert`                       | Internal | Execute FX conversion              | Per conversion     | <200ms |
| Wallet   | Settlement   | `POST /api/v1/settlement/agent`                 | Internal | Trigger agent settlement           | Batch daily        | <5min  |
| Wallet   | Settlement   | `POST /api/v1/settlement/merchant`              | Internal | Trigger merchant settlement        | Batch daily        | <5min  |
| Wallet   | Notification | `POST /api/v1/notification/send`                | Internal | Send txn notification              | Per txn            | <100ms |
| Wallet   | Identity     | `POST /api/v1/identity/validate-session`        | Internal | Session validation                 | Per txn            | <50ms  |
| Wallet   | Agent        | `GET /api/v1/agent/{id}/float`                  | Internal | Validate agent float               | Per cash-in/out    | <50ms  |
| Wallet   | Agent        | `POST /api/v1/agent/{id}/float/debit`           | Internal | Debit agent float                  | Per cash-out       | <100ms |
| Wallet   | Agent        | `POST /api/v1/agent/{id}/float/credit`          | Internal | Credit agent float                 | Per cash-in        | <100ms |
| Wallet   | Admin        | `GET /api/v1/wallet/transactions`               | Internal | List/search transactions           | Per admin query    | <300ms |
| Wallet   | Admin        | `POST /api/v1/wallet/transactions/{id}/reverse` | Internal | Reverse a transaction              | Per reversal       | <500ms |

### 1.3 CFE (Core Financial Engine) APIs

| Provider | Consumer   | Endpoint                                  | Method   | Purpose                         | Frequency            | SLA    |
| -------- | ---------- | ----------------------------------------- | -------- | ------------------------------- | -------------------- | ------ |
| CFE      | Settlement | `POST /api/v1/cfe/settlement-batch`       | Internal | Post settlement batch to ledger | Daily                | <5min  |
| CFE      | Wallet     | `POST /api/v1/cfe/hold`                   | Internal | Place hold on funds             | Per txn              | <100ms |
| CFE      | Wallet     | `POST /api/v1/cfe/release-hold`           | Internal | Release hold                    | Per txn              | <100ms |
| CFE      | Wallet     | `POST /api/v1/cfe/post`                   | Internal | Post completed transaction      | Per txn              | <100ms |
| CFE      | Wallet     | `POST /api/v1/cfe/fail`                   | Internal | Mark transaction failed         | Per txn              | <100ms |
| CFE      | Admin      | `GET /api/v1/cfe/ledger/{account}`        | Internal | View account balance & entries  | Per admin query      | <200ms |
| CFE      | Admin      | `GET /api/v1/cfe/journal`                 | Internal | Query journal entries           | Per admin query      | <500ms |
| CFE      | FX         | `POST /api/v1/cfe/fx-entry`               | Internal | Post FX conversion entry        | Per conversion       | <100ms |
| CFE      | Compliance | `GET /api/v1/cfe/suspicious-transactions` | Internal | Query flagged txns for SAR      | Per compliance check | <500ms |

### 1.4 Fraud Detection APIs

| Provider | Consumer     | Endpoint                                | Method   | Purpose                               | Frequency        | SLA    |
| -------- | ------------ | --------------------------------------- | -------- | ------------------------------------- | ---------------- | ------ |
| Fraud    | Wallet       | `POST /api/v1/fraud/screen-transaction` | Internal | Rule-based + ML screening             | Per txn          | <200ms |
| Fraud    | Wallet       | `POST /api/v1/fraud/transaction-result` | Internal | Report txn outcome for model training | Per txn          | <100ms |
| Fraud    | Admin        | `GET /api/v1/fraud/cases`               | Internal | List fraud cases                      | Per admin query  | <300ms |
| Fraud    | Admin        | `POST /api/v1/fraud/cases/{id}/update`  | Internal | Update case status                    | Per action       | <100ms |
| Fraud    | Admin        | `POST /api/v1/fraud/rules`              | Internal | Create/update rules                   | Per admin action | <200ms |
| Fraud    | Admin        | `GET /api/v1/fraud/rules`               | Internal | List fraud rules                      | Per admin query  | <100ms |
| Fraud    | Compliance   | `GET /api/v1/fraud/cases/search`        | Internal | Search fraud cases for SAR            | Per compliance   | <300ms |
| Fraud    | Notification | `POST /api/v1/notification/alert`       | Internal | Send fraud alert                      | Per flag         | <100ms |

### 1.5 FX Service APIs

| Provider | Consumer   | Endpoint                            | Method   | Purpose                            | Frequency        | SLA    |
| -------- | ---------- | ----------------------------------- | -------- | ---------------------------------- | ---------------- | ------ |
| FX       | Remittance | `GET /api/v1/fx/rates/{pair}`       | Internal | Get rate for remittance            | Per remittance   | <100ms |
| FX       | Wallet     | `POST /api/v1/fx/convert`           | Internal | Execute FX conversion lock         | Per conversion   | <200ms |
| FX       | Wallet     | `GET /api/v1/fx/rates`              | Internal | Get all current rates              | Per app open     | <100ms |
| FX       | Admin      | `POST /api/v1/fx/override`          | Internal | Manual rate override               | Per admin action | <100ms |
| FX       | Admin      | `GET /api/v1/fx/rates/history`      | Internal | Rate change audit trail            | Per admin query  | <200ms |
| FX       | Admin      | `POST /api/v1/fx/margins`           | Internal | Update margin configuration        | Per admin action | <100ms |
| FX       | Settlement | `GET /api/v1/fx/rates/{pair}?date=` | Internal | Historical rate for reconciliation | Batch daily      | <200ms |
| FX       | CBS Feed   | `POST /api/v1/fx/cbs-rate`          | Internal | Ingest CBS official rate           | Every 30min      | <1s    |

### 1.6 Agent Service APIs

| Provider | Consumer     | Endpoint                              | Method   | Purpose                        | Frequency        | SLA    |
| -------- | ------------ | ------------------------------------- | -------- | ------------------------------ | ---------------- | ------ |
| Agent    | Wallet       | `GET /api/v1/agent/{id}`              | Internal | Validate agent exists & active | Per agent action | <50ms  |
| Agent    | Wallet       | `GET /api/v1/agent/{id}/float`        | Internal | Check agent float balance      | Per cash-in/out  | <50ms  |
| Agent    | Settlement   | `POST /api/v1/settlement/agent`       | Internal | Calculate agent settlement     | Daily            | <5min  |
| Agent    | Admin        | `GET /api/v1/agent`                   | Internal | Search agents                  | Per admin query  | <300ms |
| Agent    | Admin        | `POST /api/v1/agent`                  | Internal | Create/modify agent            | Per admin action | <200ms |
| Agent    | Admin        | `GET /api/v1/agent/{id}/transactions` | Internal | Agent txn history              | Per admin query  | <300ms |
| Agent    | Admin        | `POST /api/v1/agent/{id}/commission`  | Internal | Set commission rate            | Per admin action | <100ms |
| Agent    | Admin        | `POST /api/v1/agent/{id}/status`      | Internal | Suspend/activate agent         | Per admin action | <100ms |
| Agent    | Notification | `POST /api/v1/notification/send`      | Internal | Agent notification             | Per event        | <100ms |

### 1.7 Notification Service APIs

| Provider     | Consumer | Endpoint                              | Method   | Purpose                             | Frequency        | SLA    |
| ------------ | -------- | ------------------------------------- | -------- | ----------------------------------- | ---------------- | ------ |
| Notification | ALL      | `POST /api/v1/notification/send`      | Internal | Queue notification (SMS/Push/Email) | Per event        | <100ms |
| Notification | Wallet   | `GET /api/v1/notification/templates`  | Internal | Get notification templates          | Per template     | <50ms  |
| Notification | Admin    | `POST /api/v1/notification/templates` | Internal | Manage templates                    | Per admin action | <100ms |
| Notification | Admin    | `GET /api/v1/notification/logs`       | Internal | Delivery logs                       | Per admin query  | <300ms |
| Notification | Admin    | `POST /api/v1/notification/test`      | Internal | Test notification delivery          | Per test         | <1s    |

### 1.8 Settlement Service APIs

| Provider   | Consumer | Endpoint                              | Method   | Purpose                   | Frequency        | SLA    |
| ---------- | -------- | ------------------------------------- | -------- | ------------------------- | ---------------- | ------ |
| Settlement | CFE      | `POST /api/v1/cfe/settlement-batch`   | Internal | Post settlement batch     | Daily            | <5min  |
| Settlement | Admin    | `GET /api/v1/settlement/batches`      | Internal | List settlement batches   | Per admin query  | <300ms |
| Settlement | Admin    | `GET /api/v1/settlement/batches/{id}` | Internal | Batch detail              | Per admin query  | <200ms |
| Settlement | Admin    | `GET /api/v1/settlement/banks`        | Internal | Bank accounts             | Per admin query  | <100ms |
| Settlement | Admin    | `POST /api/v1/settlement/process`     | Internal | Trigger manual settlement | Per admin action | <5min  |
| Settlement | Admin    | `GET /api/v1/settlement/reports`      | Internal | Settlement reports        | Per admin query  | <500ms |

### 1.9 Remittance Service APIs

| Provider   | Consumer     | Endpoint                             | Method   | Purpose                      | Frequency        | SLA    |
| ---------- | ------------ | ------------------------------------ | -------- | ---------------------------- | ---------------- | ------ |
| Remittance | FX           | `GET /api/v1/fx/rates/{pair}`        | Internal | Get rate for corridor        | Per remittance   | <100ms |
| Remittance | Wallet       | `POST /api/v1/wallet/transactions`   | Internal | Debit sender wallet          | Per remittance   | <200ms |
| Remittance | Compliance   | `POST /api/v1/compliance/aml/screen` | Internal | Screen beneficiary           | Per remittance   | <1s    |
| Remittance | CFE          | `POST /api/v1/cfe/post-entry`        | Internal | Post remittance ledger entry | Per remittance   | <200ms |
| Remittance | Admin        | `GET /api/v1/remittance`             | Internal | List/search remittances      | Per admin query  | <300ms |
| Remittance | Admin        | `GET /api/v1/remittance/{id}`        | Internal | Remittance detail            | Per admin query  | <200ms |
| Remittance | Admin        | `POST /api/v1/remittance/partners`   | Internal | Manage MTO partners          | Per admin action | <200ms |
| Remittance | Notification | `POST /api/v1/notification/send`     | Internal | Notify sender/beneficiary    | Per event        | <100ms |

### 1.10 Compliance Service APIs

| Provider   | Consumer    | Endpoint                                  | Method   | Purpose                                  | Frequency        | SLA    |
| ---------- | ----------- | ----------------------------------------- | -------- | ---------------------------------------- | ---------------- | ------ |
| Compliance | Identity    | `POST /api/v1/identity/users/{id}/flag`   | Internal | Flag user for AML                        | Per screening    | <100ms |
| Compliance | Remittance  | `POST /api/v1/compliance/aml/screen`      | Internal | Screen beneficiary against sanctions/PEP | Per remittance   | <1s    |
| Compliance | Wallet      | `POST /api/v1/compliance/kyc/status`      | Internal | Check KYC tier eligibility               | Per txn          | <50ms  |
| Compliance | Admin       | `GET /api/v1/compliance/kyc`              | Internal | KYC queue                                | Per admin query  | <300ms |
| Compliance | Admin       | `POST /api/v1/compliance/kyc/{id}/review` | Internal | Approve/reject KYC                       | Per admin action | <100ms |
| Compliance | Admin       | `POST /api/v1/compliance/aml/sar`         | Internal | Create SAR                               | Per compliance   | <200ms |
| Compliance | Admin       | `GET /api/v1/compliance/audit-log`        | Internal | Query audit log                          | Per admin query  | <500ms |
| Compliance | Admin       | `GET /api/v1/compliance/pep`              | Internal | PEP watchlist search                     | Per admin query  | <500ms |
| Compliance | World-Check | `POST /api/v1/compliance/screen-external` | Internal | Screen against World-Check               | Per screening    | <3s    |

### 1.11 Bill Payment Service APIs

| Provider | Consumer     | Endpoint                             | Method   | Purpose                        | Frequency        | SLA    |
| -------- | ------------ | ------------------------------------ | -------- | ------------------------------ | ---------------- | ------ |
| Bills    | Wallet       | `POST /api/v1/wallet/transactions`   | Internal | Debit user wallet for bill pay | Per bill pay     | <200ms |
| Bills    | CFE          | `POST /api/v1/cfe/post-entry`        | Internal | Post bill payment entry        | Per bill pay     | <200ms |
| Bills    | Notification | `POST /api/v1/notification/send`     | Internal | Notify bill payment            | Per bill pay     | <100ms |
| Bills    | Admin        | `GET /api/v1/bills/history`          | Internal | Bill payment history           | Per admin query  | <300ms |
| Bills    | Admin        | `POST /api/v1/bills/provider/config` | Internal | Configure bill provider        | Per admin action | <200ms |

### 1.12 Reporting Service APIs

| Provider | Consumer | Endpoint                        | Method   | Purpose                   | Frequency        | SLA    |
| -------- | -------- | ------------------------------- | -------- | ------------------------- | ---------------- | ------ |
| Reports  | ALL      | `GET /api/v1/reports/{type}`    | Internal | Generate report (CSV/PDF) | Per request      | <10s   |
| Reports  | Admin    | `GET /api/v1/reports/scheduled` | Internal | List scheduled reports    | Per admin query  | <200ms |
| Reports  | Admin    | `POST /api/v1/reports/schedule` | Internal | Schedule recurring report | Per admin action | <200ms |
| Reports  | Admin    | `GET /api/v1/reports/dashboard` | Internal | Dashboard metrics         | Per admin load   | <500ms |

### 1.13 Admin Service APIs

| Provider | Consumer     | Endpoint                        | Method   | Purpose                | Frequency         | SLA    |
| -------- | ------------ | ------------------------------- | -------- | ---------------------- | ----------------- | ------ |
| Admin    | All Admin UI | `POST /api/v1/admin/auth/login` | Internal | Admin authentication   | Per login         | <200ms |
| Admin    | All Admin UI | `POST /api/v1/admin/auth/mfa`   | Internal | MFA verification       | Per MFA           | <100ms |
| Admin    | All Admin UI | `GET /api/v1/admin/permissions` | Internal | Check user permissions | Per admin action  | <50ms  |
| Admin    | Super Admin  | `POST /api/v1/admin/config`     | Internal | System configuration   | Per config change | <200ms |
| Admin    | Super Admin  | `POST /api/v1/admin/roles`      | Internal | RBAC management        | Per role change   | <200ms |

---

## 2. External API Matrix (Beza → 3rd Party)

### 2.1 SMS Gateway

| Provider | External System | Endpoint                                    | Protocol   | Frequency        | Purpose            | SLA  | Auth                 |
| -------- | --------------- | ------------------------------------------- | ---------- | ---------------- | ------------------ | ---- | -------------------- |
| SMS      | Syriatel SMPP   | `smpp://smpp.syriatel.sy:2775`              | SMPP 3.4   | Per notification | Send OTP & alerts  | <5s  | System ID + Password |
| SMS      | MTN SMPP        | `smpp://smpp.mtn.com.sy:2775`               | SMPP 3.4   | Per notification | Send OTP & alerts  | <5s  | System ID + Password |
| SMS      | SMS Backup HTTP | `POST https://api.sms-provider.com/v1/send` | REST/HTTPS | Fallback         | Backup SMS channel | <10s | API Key              |

### 2.2 Central Bank of Syria (CBS)

| Provider      | External System      | Endpoint                                                  | Protocol   | Frequency   | Purpose                       | SLA   | Auth                       |
| ------------- | -------------------- | --------------------------------------------------------- | ---------- | ----------- | ----------------------------- | ----- | -------------------------- |
| CBS Feed      | CBS Rate API         | `GET https://cbs.gov.sy/api/rates`                        | REST/HTTPS | Every 30min | Official FX rates             | <30s  | Client Certificate         |
| CBS Reporting | CBS Reporting Portal | `POST https://cbs.gov.sy/api/reports/transaction-volumes` | REST/HTTPS | Daily       | Regulatory reporting          | <5min | Client Certificate + Token |
| CBS Reporting | CBS SAR Submission   | `POST https://cbs.gov.sy/api/reports/sar`                 | REST/HTTPS | Per SAR     | Suspicious activity reporting | <5min | Client Certificate + Token |

### 2.3 Remittance / MTO Partners

| Provider   | External System | Endpoint                                         | Protocol   | Frequency      | Purpose                  | SLA  | Auth                |
| ---------- | --------------- | ------------------------------------------------ | ---------- | -------------- | ------------------------ | ---- | ------------------- |
| Remittance | Western Union   | `POST https://api.westernunion.com/v3/transfers` | REST/HTTPS | Per remittance | Cross-border payout      | <30s | OAuth 2.0           |
| Remittance | MoneyGram       | `POST https://api.moneygram.com/v1/transactions` | REST/HTTPS | Per remittance | Cross-border payout      | <30s | API Key + Signature |
| Remittance | Ria             | `POST https://api.riafinancial.com/v2/payments`  | REST/HTTPS | Per remittance | Cross-border payout      | <30s | API Key             |
| Remittance | Small World     | `POST https://api.smallworldfs.com/v1/transfers` | REST/HTTPS | Per remittance | Cross-border payout      | <30s | API Key             |
| Remittance | Status Check    | `POST /api/v1/remittance/status`                 | REST/HTTPS | Per remittance | Update remittance status | <10s | HMAC Signature      |

### 2.4 Bill Payment Providers

| Provider | External System    | Endpoint                                           | Protocol   | Frequency        | Purpose                | SLA  | Auth       |
| -------- | ------------------ | -------------------------------------------------- | ---------- | ---------------- | ---------------------- | ---- | ---------- |
| Bills    | Syriatel Billing   | `POST https://api.syriatel.sy/billing/v2/inquiry`  | REST/HTTPS | Per bill inquiry | Check bill amount      | <10s | Mutual TLS |
| Bills    | Syriatel Billing   | `POST https://api.syriatel.sy/billing/v2/payment`  | REST/HTTPS | Per bill payment | Pay bill               | <15s | Mutual TLS |
| Bills    | MTN Billing        | `POST https://api.mtn.com.sy/billing/v1/inquiry`   | REST/HTTPS | Per bill inquiry | Check bill amount      | <10s | Mutual TLS |
| Bills    | MTN Billing        | `POST https://api.mtn.com.sy/billing/v1/payment`   | REST/HTTPS | Per bill payment | Pay bill               | <15s | Mutual TLS |
| Bills    | PEED (Electricity) | `POST https://api.peed.gov.sy/billing/inquiry`     | REST/HTTPS | Per bill inquiry | Check electricity bill | <10s | API Key    |
| Bills    | PEED (Electricity) | `POST https://api.peed.gov.sy/billing/payment`     | REST/HTTPS | Per bill payment | Pay electricity bill   | <15s | API Key    |
| Bills    | Damascus Water     | `POST https://api.damwater.gov.sy/billing/inquiry` | REST/HTTPS | Per bill inquiry | Check water bill       | <10s | API Key    |
| Bills    | Damascus Water     | `POST https://api.damwater.gov.sy/billing/payment` | REST/HTTPS | Per bill payment | Pay water bill         | <15s | API Key    |

### 2.5 Compliance / Screening

| Provider   | External System | Endpoint                                                     | Protocol   | Frequency                     | Purpose                 | SLA   | Auth          |
| ---------- | --------------- | ------------------------------------------------------------ | ---------- | ----------------------------- | ----------------------- | ----- | ------------- |
| Compliance | World-Check     | `POST https://api.world-check.com/v3/screening`              | REST/HTTPS | Per new user + per remittance | PEP/Sanctions screening | <3s   | API Key       |
| Compliance | World-Check     | `POST https://api.world-check.com/v3/monitoring`             | REST/HTTPS | Daily                         | Continuous monitoring   | <5min | API Key       |
| Compliance | OFAC SDN List   | `GET https://sanctionslistservice.ofac.treas.gov/api/v1/sdn` | REST/HTTPS | Daily (cached)                | Sanctions list sync     | <5min | None (public) |
| Compliance | EU Sanctions    | `GET https://webgate.ec.europa.eu/fsd/fsd/api/v1/`           | REST/HTTPS | Daily (cached)                | EU sanctions list       | <5min | None (public) |

### 2.6 Banking / Settlement

| Provider   | External System | Endpoint                                         | Protocol   | Frequency      | Purpose                  | SLA    | Auth       |
| ---------- | --------------- | ------------------------------------------------ | ---------- | -------------- | ------------------------ | ------ | ---------- |
| Settlement | BSO Bank        | `SFTP sftp://bso-bank.com.sy:22/settlement/`     | SFTP       | Daily          | Settlement file transfer | <30min | SSH Key    |
| Settlement | Bemo Bank       | `SFTP sftp://bemo-bank.com.sy:22/settlement/`    | SFTP       | Daily          | Settlement file transfer | <30min | SSH Key    |
| Settlement | SIIB Bank       | `SFTP sftp://siib.com.sy:22/settlement/`         | SFTP       | Daily          | Settlement file transfer | <30min | SSH Key    |
| Settlement | BSO Bank        | `POST https://api.bso-bank.com.sy/v1/transfers`  | REST/HTTPS | Per settlement | Bank transfer initiation | <5min  | Mutual TLS |
| Settlement | Bemo Bank       | `POST https://api.bemo-bank.com.sy/v1/transfers` | REST/HTTPS | Per settlement | Bank transfer initiation | <5min  | Mutual TLS |
| Settlement | SIIB Bank       | `POST https://api.siib.com.sy/v1/transfers`      | REST/HTTPS | Per settlement | Bank transfer initiation | <5min  | Mutual TLS |

### 2.7 KYC / Identity

| Provider | External System       | Endpoint                                                 | Protocol   | Frequency          | Purpose                   | SLA  | Auth            |
| -------- | --------------------- | -------------------------------------------------------- | ---------- | ------------------ | ------------------------- | ---- | --------------- |
| KYC      | Syrian Civil Registry | `POST https://registry.syria.gov/api/verify`             | REST/HTTPS | Per KYC submission | National ID verification  | <5s  | Gov API License |
| KYC      | Liveness Check        | `POST https://api.veriff.com/v1/sessions`                | REST/HTTPS | Per KYC submission | Biometric liveness        | <10s | API Key         |
| KYC      | Liveness Check        | `POST https://api.veriff.com/v1/sessions/{id}/decisions` | REST/HTTPS | Per KYC submission | Get verification decision | <5s  | API Key         |

### 2.8 Partner Merchant APIs

| Provider | External System | Endpoint                                     | Protocol   | Frequency            | Purpose                     | SLA  | Auth    |
| -------- | --------------- | -------------------------------------------- | ---------- | -------------------- | --------------------------- | ---- | ------- |
| Merchant | Partner API     | `POST https://{merchant}.com/api/v1/payment` | REST/HTTPS | Per merchant payment | Merchant payment processing | <10s | API Key |
| Merchant | Partner API     | `POST https://{merchant}.com/api/v1/verify`  | REST/HTTPS | Per merchant payment | Merchant order verification | <5s  | API Key |
| Merchant | Partner API     | `POST https://{merchant}.com/api/v1/refund`  | REST/HTTPS | Per refund           | Merchant payment reversal   | <10s | API Key |

---

## 3. Client API Matrix (App/Web/USSD → Beza)

### 3.1 Flutter Mobile App

| Client  | Endpoint Group    | Base Path                 | Auth          | Rate Limit       | Payload          | Retry Strategy                  |
| ------- | ----------------- | ------------------------- | ------------- | ---------------- | ---------------- | ------------------------------- |
| Flutter | Auth APIs         | `/api/v1/auth/*`          | None (mostly) | 10/min per IP    | JSON             | Exponential backoff (3x)        |
| Flutter | Wallet APIs       | `/api/v1/wallet/*`        | JWT           | 100/min per user | JSON             | Idempotent retry (3x)           |
| Flutter | Transfer APIs     | `/api/v1/transfer/*`      | JWT           | 30/min per user  | JSON             | Idempotent with idempotency key |
| Flutter | Agent APIs        | `/api/v1/agent/*`         | JWT           | 100/min per user | JSON             | Simple retry (2x)               |
| Flutter | Bill APIs         | `/api/v1/bills/*`         | JWT           | 50/min per user  | JSON             | Idempotent retry (3x)           |
| Flutter | FX APIs           | `/api/v1/fx/*`            | JWT           | 60/min per user  | JSON             | Stale-while-revalidate          |
| Flutter | Remittance APIs   | `/api/v1/remittance/*`    | JWT           | 20/min per user  | JSON             | Idempotent with idempotency key |
| Flutter | KYC APIs          | `/api/v1/kyc/*`           | JWT           | 10/min per user  | Multipart        | Exponential backoff (3x)        |
| Flutter | Profile APIs      | `/api/v1/profile/*`       | JWT           | 30/min per user  | JSON             | Simple retry (2x)               |
| Flutter | Notification APIs | `/api/v1/notifications/*` | JWT           | 30/min per user  | JSON             | Poll with backoff               |
| Flutter | Support APIs      | `/api/v1/support/*`       | JWT           | 20/min per user  | JSON + Multipart | Simple retry (2x)               |

### 3.2 Web Admin (React)

| Client      | Endpoint Group   | Base Path                       | Auth           | Rate Limit             | Payload       | Notes            |
| ----------- | ---------------- | ------------------------------- | -------------- | ---------------------- | ------------- | ---------------- |
| React Admin | Admin Auth       | `/api/v1/admin/auth/*`          | JWT + MFA      | 10/min per IP          | JSON          | MFA enforced     |
| React Admin | Admin Dashboard  | `/api/v1/admin/dashboard/*`     | JWT + IP       | 200/min per user       | JSON          | Aggregated data  |
| React Admin | User Management  | `/api/v1/admin/users/*`         | JWT + IP       | 200/min per user       | JSON          | RBAC enforced    |
| React Admin | KYC Management   | `/api/v1/admin/kyc/*`           | JWT + IP       | 200/min per compliance | JSON + Binary | Compliance role  |
| React Admin | Agent Management | `/api/v1/admin/agents/*`        | JWT + IP       | 200/min per user       | JSON          | Admin role       |
| React Admin | Transaction Mgmt | `/api/v1/admin/transactions/*`  | JWT + IP       | 200/min per user       | JSON          | Admin role       |
| React Admin | Fraud Management | `/api/v1/admin/fraud/*`         | JWT + IP       | 200/min per fraud_ops  | JSON          | Fraud Ops role   |
| React Admin | FX Management    | `/api/v1/admin/fx/*`            | JWT + IP       | 100/min per user       | JSON          | Admin role       |
| React Admin | Remittance Mgmt  | `/api/v1/admin/remittance/*`    | JWT + IP       | 200/min per user       | JSON          | Admin/Ops role   |
| React Admin | Settlement Mgmt  | `/api/v1/admin/settlement/*`    | JWT + IP       | 100/min per user       | JSON          | Finance role     |
| React Admin | Compliance Mgmt  | `/api/v1/admin/compliance/*`    | JWT + IP       | 200/min per compliance | JSON          | Compliance role  |
| React Admin | Reports          | `/api/v1/admin/reports/*`       | JWT + IP       | 50/min per user        | JSON          | RBAC enforced    |
| React Admin | Audit Log        | `/api/v1/admin/audit/*`         | JWT + IP       | 100/min per user       | JSON          | Compliance role  |
| React Admin | Config           | `/api/v1/admin/config/*`        | JWT + IP + MFA | 30/min per user        | JSON          | Super Admin only |
| React Admin | Notifications    | `/api/v1/admin/notifications/*` | JWT + IP       | 100/min per user       | JSON          | Admin role       |

### 3.3 USSD Gateway

| Client | Endpoint Group | Base Path                 | Auth         | Rate Limit        | Payload     | Notes                |
| ------ | -------------- | ------------------------- | ------------ | ----------------- | ----------- | -------------------- |
| USSD   | USSD APIs      | `/api/ussd/*`             | PIN + MSISDN | 30/min per MSISDN | Plain text  | Session-based        |
| USSD   | USSD Callback  | `POST /api/ussd/callback` | IP whitelist | 60/min per IP     | URL-encoded | From telco gateway   |
| USSD   | USSD Session   | `/api/ussd/session`       | MSISDN       | 30/min per MSISDN | Plain text  | Session timeout 120s |

### 3.4 Agent POS

| Client    | Endpoint Group | Base Path                  | Auth            | Rate Limit        | Payload | Notes                     |
| --------- | -------------- | -------------------------- | --------------- | ----------------- | ------- | ------------------------- |
| Agent POS | POS Auth       | `/api/v1/pos/auth/*`       | PIN + Device ID | 10/min per device | JSON    | Device binding            |
| Agent POS | POS Operations | `/api/v1/pos/*`            | JWT             | 300/min per agent | JSON    | Higher limit for cash ops |
| Agent POS | POS Float      | `/api/v1/pos/float/*`      | JWT             | 60/min per agent  | JSON    | Float management          |
| Agent POS | POS Settlement | `/api/v1/pos/settlement/*` | JWT             | 30/min per agent  | JSON    | EOD settlement            |

### 3.5 Webhooks / Callbacks

| Provider     | Endpoint                                  | Triggered By       | Direction | Retry             | Notes                                 |
| ------------ | ----------------------------------------- | ------------------ | --------- | ----------------- | ------------------------------------- |
| Beza         | `POST /api/v1/webhooks/remittance-status` | MTO Partner        | Inbound   | 3x, 5min interval | Status update from remittance partner |
| Beza         | `POST /api/v1/webhooks/kyc-decision`      | Veriff             | Inbound   | 3x, 5min interval | KYC verification result               |
| Beza         | `POST /api/v1/webhooks/ussd-callback`     | Telco USSD Gateway | Inbound   | 2x, 10s interval  | USSD session input                    |
| Beza         | `POST /api/v1/webhooks/cbs-rate`          | CBS Rate Feed      | Inbound   | 3x, 1min interval | Official FX rate update               |
| MTO Partner  | `POST {partner_callback_url}`             | Remittance Service | Outbound  | 3x, 5min interval | Notify partner of status change       |
| SMS Provider | `POST {sms_provider_callback_url}`        | SMS Service        | Outbound  | 3x, 1min interval | Delivery receipt callback             |

---

## 4. API Dependency Graph (Critical Paths)

### 4.1 Transaction Flow Dependencies (P2P Transfer)

```
Client → API Gateway → Auth (validate-session)
                       → Wallet (validate-balance, check-limits)
                       → Fraud (screen-transaction)
                       → CFE (hold)
                       → CFE (post-entry: debit sender)
                       → CFE (post-entry: credit receiver)
                       → CFE (post-entry: fee)
                       → Notification (send push/SMS to sender)
                       → Notification (send push/SMS to receiver)
```

**Critical path services:** Auth → Wallet → Fraud → CFE
**Maximum acceptable latency:** P99 < 1s end-to-end

### 4.2 Agent Cash-In Flow Dependencies

```
Client → API Gateway → Auth (validate-session)
                       → Agent (validate-agent)
                       → Agent (get-float)
                       → Wallet (validate-limits)
                       → Fraud (screen-transaction)
                       → CFE (post-entry: debit agent float)
                       → CFE (post-entry: credit user wallet)
                       → CFE (post-entry: fee)
                       → Agent (credit-float)
                       → Notification (send SMS to user + agent)
```

**Critical path services:** Auth → Agent → Wallet → Fraud → CFE
**Maximum acceptable latency:** P99 < 2s end-to-end

### 4.3 Remittance Flow Dependencies

```
Client → API Gateway → Auth (validate-session)
                       → Wallet (validate-balance)
                       → FX (get-rate)
                       → Compliance (aml-screen beneficiary)
                       → Fraud (screen-transaction)
                       → CFE (hold)
                       → CFE (post-entry: debit sender)
                       → CFE (post-entry: FX conversion)
                       → Remittance (submit to MTO partner)
                       → CFE (post-entry: fee)
                       → Notification (send SMS)
```

**Critical path services:** Auth → Wallet → FX → Compliance → Fraud → CFE → Remittance
**Maximum acceptable latency:** P99 < 5s end-to-end (includes external MTO API)

---

## 5. API Security Matrix

| Aspect               | Implementation                                | Notes                                       |
| -------------------- | --------------------------------------------- | ------------------------------------------- |
| **Authentication**   | JWT (RS256) for client APIs                   | Short-lived (15min access, 7d refresh)      |
| **MFA**              | TOTP + SMS backup for admin                   | Required for sensitive actions              |
| **API Keys**         | HMAC-SHA256 for POS & partners                | Rotated quarterly                           |
| **Rate Limiting**    | Token bucket per user/IP/endpoint             | Configured per route group                  |
| **Input Validation** | JSON Schema validation on API Gateway         | Whitelist-based                             |
| **Idempotency**      | Idempotency-Key header for mutating endpoints | 24h key expiry                              |
| **CORS**             | Whitelist origins per environment             | Production: specific mobile + admin domains |
| **Audit**            | All admin mutating requests logged            | Append-only audit table                     |
| **Encryption**       | TLS 1.3 in transit, AES-256-GCM at rest       | HSM for key management                      |
| **Secrets**          | HashiCorp Vault for all secrets               | Dynamic DB credentials                      |

---

## 6. API SLA Summary by Tier

| Tier                  | Max P50 | Max P99 | Availability | Examples                                          |
| --------------------- | ------- | ------- | ------------ | ------------------------------------------------- |
| **Tier 0 — Critical** | <50ms   | <200ms  | 99.99%       | session validation, PIN verify, balance check     |
| **Tier 1 — High**     | <100ms  | <500ms  | 99.95%       | transaction processing, fraud screening, FX rates |
| **Tier 2 — Normal**   | <200ms  | <1s     | 99.9%        | user search, agent search, transaction history    |
| **Tier 3 — Batch**    | <5s     | <30s    | 99.5%        | settlement, batch operations, report generation   |
| **Tier 4 — External** | <1s     | <5s     | 99.5%        | SMS delivery, bill inquiry, remittance payout     |

---

_End of API Matrix. 150+ documented API relationships across internal, external, and client domains._
