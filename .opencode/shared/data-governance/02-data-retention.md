# Data Retention Policy — Beza Platform

**Effective date:** Platform launch | **Owner:** Compliance Officer | **Approval:** CBS Compliance (نظام مكافحة غسل الأموال وتمويل الإرهاب)

---

## Retention Schedule

| Data Category | Retention Period | Legal Basis | Deletion Procedure | Archive Procedure |
|--------------|-----------------|-------------|-------------------|-------------------|
| **Transaction records** (P2P, bill pay, merchant QR, FX conversion, agent cash-in/out) | **10 years** from transaction date | CBS Law No. 23/2005; AML Law No. 31/2010 (Art. 8) | Logical deletion from operational DB, moved to cold archive; after 10 years: secure wipe (DoD 5220.22-M standard) | Yearly archive: full export to encrypted cold storage (S3 Glacier or local tape); indexed by transaction ID, user ID, date; retrievable within 5 business days |
| **KYC documents** (national ID photo, selfie, proof of address, passport, business registration, CR, tax ID) | **10 years after account closure** | AML Law No. 31/2010 (Art. 7); CBS KYC circular | Account closure date recorded; T+10 years: documents deleted via secure file deletion; metadata (verification status, date verified) retained in log | Annual archive snapshot to encrypted WORM storage; separate from transactional archive; keyed by user ID |
| **Wallet balances & limits** (Tier level, max balance, daily limits, current balance at closure) | **10 years after account closure** | CBS e-money circular (settlement audit requirement) | Part of account record; deleted with account records at T+10 years | Archived with transaction records; balance snapshot at account closure included |
| **Session logs** (login timestamp, IP, device ID, session duration, logout reason) | **90 days** | Operational requirement; CMT data privacy (draft) | Automated purge: cron job deletes logs older than 90 days every Sunday 03:00 AST | N/A — no archive; exception: extend to 1 year if fraud investigation is open |
| **Device fingerprints** (device ID, Android ID, ICCID, IMEI — hashed) | **30 days after last login** | Fraud prevention purpose limitation | Automated purge: daily job removes fingerprints for devices not seen in 30+ days | N/A — no archive; fraud blacklist (device IDs flagged for fraud) retained 2 years separately |
| **IP addresses** | **90 days** (full IP); **12 months** (truncated to /24 subnet) | Security monitoring; threat intelligence | After 90 days: last octet zeroed; after 12 months: deleted | N/A — truncated data retained in security logs for trend analysis |
| **GPS location data** (from agent search, transaction geo-tagging) | **30 days** (precise); **12 months** (1km grid anonymized) | Service improvement; fraud detection | After 30 days: precise coordinates replaced with grid centroid | Anonymized grid data kept for network planning |
| **Audit logs** (admin actions, compliance actions, system config changes) | **7 years** | CBS audit requirement; AML Law No. 31/2010 | After 7 years: secure wipe; must retain at least 5 years from last CBS audit | WORM storage: daily append-only log files; SHA-256 hash chain for integrity; annual archive to optical media (write-once) mandated by CBS |
| **Chat messages** (in-app support chat) | **2 years** from message date | Operational need; dispute evidence | Automated purge: messages older than 2 years deleted monthly | Archived on dispute closure if dispute-tagged; otherwise no archive |
| **Support tickets** | **5 years** from ticket closure | Dispute resolution; CBS complaint handling circular | After 5 years: secure deletion; if dispute-tagged → extend to 7 years | Archive export quarterly to encrypted storage; indexed by ticket ID |
| **Call recordings** (support calls) | **1 year** from recording date | Quality monitoring; dispute evidence | Automated deletion after 1 year; if dispute-tagged → retain until dispute resolution + 1 year | Archived with ticket if dispute-related |
| **Marketing data** (consent records, marketing preferences, email/SMS opt-in/opt-out, campaign interaction) | **Until consent withdrawn** + 30 days grace | CMT data privacy (draft); legitimate interest | User withdraws consent → data flagged; 30-day grace period for campaign wind-down → then deleted | N/A — no archive; aggregate anonymized campaign stats retained indefinitely |
| **Analytics / Behavioral data** (anonymous sessions, feature usage, navigation paths) | **24 months** after collection | Product improvement (legitimate interest) | Automated purge: data older than 24 months deleted quarterly | Aggregated reports (no PII) retained indefinitely |
| **SMS logs** (delivery receipts, message content) | **2 years** from send date | Operational record; dispute evidence | Automated deletion after 2 years | Archived if dispute-related |
| **Remittance sender data** (sender name, passport copy, corridor, purpose, reference number) | **10 years** from transaction date | AML Law No. 31/2010; CBS remittance circular | Part of transaction record; deleted with transaction archive at 10 years | Archived with transaction records; separate index for sender name for AML audits |
| **USSD session logs** (*123# menu navigation, timestamps) | **90 days** | Operational; fraud detection | Automated purge: older than 90 days deleted weekly | N/A — no archive |
| **System logs** (server logs, application logs, database logs) | **1 year** | IT operations; security incident investigation | Log rotation: 90 days hot storage, 9 months cold storage; deleted at 1 year | Security incident-related logs: retain 7 years (tagged and moved to audit archive) |
| **API logs** (3rd party API calls — billers, CBS, agent) | **1 year** | Operational; dispute resolution | Deleted after 1 year; biller transaction logs retained with transaction records (10 years) | Archived with transaction records if payment-related |

---

## Deletion Procedures

### Standard Deletion
1. Automated cron job runs daily at 02:00 AST (Syria time, UTC+3)
2. Script identifies records exceeding retention period
3. Records are **soft-deleted** first (deleted_at timestamp, is_deleted=1)
4. After 30-day grace period in soft-delete, records are **hard-deleted**
5. Hard deletion: UPDATE with NULL/0 for all sensitive fields, then TRUNCATE or DROP for archived partitions
6. Verification: row count before/after logged; any anomaly alerts Compliance team

### Secure Deletion (KYC, PII, Financial)
- Storage media must be overwritten: DoD 5220.22-M standard (3-pass overwrite: zeros, ones, random)
- For SSD/NVMe storage: use ATA Secure Erase command
- Database records: column-level overwrite with random data before DROP
- File storage (S3, object store): multi-part delete + verification; bucket lifecycle policy for automatic expiry
- KYC document storage: deletion confirmation report sent to Compliance Officer

### Emergency Deletion
- Required if: data breach, regulatory order, court order
- Must be approved by: Compliance Officer + CEO
- Must be logged in audit trail with legal reference
- Must preserve a copy for legal proceedings (encrypted, access logged, separate from main storage)

---

## Archive Procedures

### Archive Schedule

| Archive Type | Frequency | Retention | Storage Medium | Encryption |
|-------------|-----------|-----------|----------------|------------|
| Transaction archive | Yearly (Jan 1) | 10 years | Cold storage (S3 Glacier Deep Archive or local HDD in fireproof safe) | AES-256-GCM; separate archive key |
| KYC document archive | Monthly | 10 years after closure | WORM optical media (M-DISC) or S3 Object Lock in compliance mode | AES-256-GCM; key held by Compliance Officer |
| Audit log archive | Daily (incremental), Yearly (full) | 7 years | WORM storage: S3 Object Lock or Write-Once optical | AES-256-GCM + SHA-256 hash chain |
| Remittance sender archive | Yearly (Jan 1) | 10 years | Same as transaction archive | AES-256-GCM |

### Archive Access
- **Standard access:** Compliance Officer can request archive retrieval with 5 business day SLA (CBS audit requirement)
- **Emergency access:** Security incident or legal request — 4 hour SLA with CEO approval
- **All archive accesses logged** in audit trail with: who, what archive, what date range, reason, approval reference

### Archive Verification
- Quarterly: random sample of 100 archived records verified for readability and integrity
- Annual: full archive integrity check (checksum comparison)
- Any corruption detected: immediately recover from secondary backup (if available) or notify CBS of data loss

---

## Exceptions

| Exception Type | Approval Required | Max Extension | Conditions |
|---------------|------------------|---------------|------------|
| **Fraud investigation hold** | Compliance Officer + Head of Security | Until investigation closes + 1 year | Specific records/case only; cannot apply to entire user base; reviewed monthly |
| **Legal hold** (court order, CBS investigation) | Compliance Officer + Legal Counsel | Until legal hold released | Must have written legal document; must be in audit log; released within 30 days of hold expiration |
| **Dispute hold** | Support Team Lead | Until dispute resolved + 6 months | Dispute-tagged records only; automatic release when dispute closed + 6 months |
| **Regulatory audit hold** | Compliance Officer | Until audit complete + 1 year | Records within scope of active CBS/CMT audit; released when audit report finalized |
| **Bulk retention extension** | CEO + Compliance Officer | Per CBS directive | Only if CBS issues written directive; must be board-approved; must notify users if PII affected |

### Exception Tracking
- All exceptions recorded in `retention_exceptions` table: case_id, record_ids, reason, start_date, end_date, approver, status (active/expired/released)
- Monthly report to Compliance Officer: active exceptions count, soon-to-expire exceptions, any exceptions exceeding 12 months
- Exception release: automated release when end_date reached, unless renewed with approval

---

## Syria-Specific Compliance Notes

1. **CBS Audit Readiness**: All archived transaction data must be retrievable within 5 business days of a CBS audit request (CBS e-money circular 2021).
2. **Server Location**: All production and archive data must be stored on servers physically located within Syria (Damascus primary, Aleppo DR). Cold archive may be in a separate Syria location (e.g., Homs) with CBS notification.
3. **Offline Records**: For areas with intermittent electricity/internet (e.g., Deir Ezzor, Hasakeh, rural Idlib), transaction data buffered on agent device must sync to server within 48 hours. If device is lost/destroyed before sync, paper backup (agent transaction log book) must be retained for 10 years.
4. **Civil Registry Data**: If integrated with Ministry of Interior civil registry for e-KYC, data must be purged per MoI data-sharing agreement (typically 90 days after verification result, retain only verification token).
5. **Sanctions Data**: Sanctions screening match records (hits) retained for 7 years per AML law. False positive records: retained 7 years in screening log, but PII redacted after 2 years.
6. **Currency Records**: SYP and USD transaction records both subject to 10-year retention. USD-denominated records additionally must be reportable to CBS for foreign currency audit.
