# Customer Lifecycle — Master State Machine

> Document version: 1.0.0
> Last updated: 2026-05-29
> Owner: Domain — Identity & Compliance

## 1. State Diagram

```
                    ┌─────────────────────────────────────────┐
                    │              PROSPECT                    │
                    │  (phone entered, no OTP yet)             │
                    └──────────┬──────────────────────────────┘
                               │ OTP verified
                               ▼
                    ┌─────────────────────────────────────────┐
                    │           REGISTERED                     │
                    │  (phone + PIN set, no KYC yet)           │
                    │  Limits: Tier 1 (250K SYP/day)           │
                    └──────────┬──────────────────────────────┘
                               │ KYC documents submitted
                               ▼
                    ┌─────────────────────────────────────────┐
                    │          KYC_PENDING                     │
                    │  (documents under review)                 │
                    │  Max time: 48h auto-approve or flag       │
                    └──────────┬──────────────────────────────┘
                    ┌──────────┴──────────────────────────────┐
                    │                                         │
                    ▼                                         ▼
         ┌──────────────────────┐                 ┌──────────────────────┐
         │    KYC_APPROVED      │                 │    KYC_REJECTED      │
         │  Tier 2 limits       │                 │  Can re-upload docs  │
         │  5M SYP/day          │                 │  Max 3 attempts      │
         └──────────┬───────────┘                 └──────────────────────┘
                    │
                    ▼
         ┌──────────────────────┐
         │       ACTIVE         │
         │  Full functionality  │
         └──────────┬───────────┘
                    │
         ┌──────────┴──────────────────────────────┐
         │              │              │           │
         ▼              ▼              ▼           ▼
   ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
   │ DORMANT  │ │RESTRICTED│ │ BLOCKED  │ │  CLOSED  │
   │ 90 days  │ │ Some ops │ │Compliance│ │ User req │
   │ no txn   │ │limited   │ │Fraud     │ │ or admin  │
   └──────────┘ └──────────┘ └──────────┘ └──────────┘
```

## 2. State Definitions

### 2.1 PROSPECT

| Property | Value |
|----------|-------|
| **Entry** | User enters phone number on registration screen |
| **Exit** | OTP verified, PIN set successfully |
| **Time limits** | OTP expires after 5 minutes; max 5 OTP requests per hour per phone |
| **Data stored** | Phone number only (no PII, no address, no name) |
| **Allowed actions** | Request OTP, verify OTP, cancel registration |
| **Fraud risk** | Low — no access to any financial functionality |
| **Notifications** | None until PIN is set |
| **Retention** | Records deleted after 24h if OTP never verified (GDPR/PDPL cleanup) |

### 2.2 REGISTERED

| Property | Value |
|----------|-------|
| **Entry** | PIN set successfully, user record created in identity service |
| **Exit** | KYC documents submitted, or account closed |
| **Limits** | Tier 1 — max balance 500,000 SYP, max daily txn 250,000 SYP |
| **Allowed actions** | Receive P2P transfers, send up to Tier 1 limit, view balance, find agents, bill inquiry (electricity/water/telecom) |
| **NOT allowed** | Send > Tier 1 limit, receive USD remittance, FX conversion > 100,000 SYP, merchant payments |
| **Notifications** | Welcome SMS: *"أهلاً بك في بيزا! تم إنشاء محفظتك بنجاح. حد المعاملات اليومي: 250,000 ل.س"* |
| **KYC reminder** | SMS every 7 days (max 3 reminders): *"يرجى توثيق حسابك للاستفادة من جميع الخدمات. إرفاق الهوية + صورة شخصية + فاتورة كهرباء أو ماء"* |
| **Post-reminder action** | After 3rd reminder with no KYC → Tier 1 temporarily frozen (receive only) |

### 2.3 KYC_PENDING

| Property | Value |
|----------|-------|
| **Entry** | User submits: national ID (front + back), selfie holding ID, proof of address (utility bill ≤ 3 months) |
| **Exit** | Approved by compliance officer, or rejected |
| **Time limit** | 48 hours auto-escalation to supervisor if no manual review commenced |
| **Allowed actions** | Same as REGISTERED (Tier 1 limits maintained during review) |
| **Document storage** | Encrypted S3 bucket (server-side AES-256), access logged, retention: 10 years post account closure |
| **Supported formats** | JPEG, PNG, PDF (max 10 MB per file) |
| **National ID format** | Syrian national ID (الرقم الوطني): 11-digit numeric, validated against modulus-11 checksum |
| **Auto-approval** | Allowed if: document quality ≥ 85% (OCR confidence), face match ≥ 90%, utility bill ≤ 90 days old |
| **Auto-rejection** | Triggered if: forgery detection fires, face match < 60%, document is expired |
| **Notifications** | *"تم استلام مستندات التوثيق. سيتم مراجعتها خلال 48 ساعة"* |

### 2.4 KYC_APPROVED

| Property | Value |
|----------|-------|
| **Entry** | Compliance officer approves, or automated checks pass |
| **Exit** | Upgraded to ACTIVE (automatic), or downgraded if fraud detected later |
| **Limits** | Tier 2 — max balance 5,000,000 SYP, max daily txn 2,000,000 SYP |
| **FX allowance** | Up to 500 USD equivalent per month (if USD wallet exists) |
| **Notifications** | SMS: *"تم توثيق حسابك. يمكنك الآن الاستفادة من جميع الخدمات. حد المعاملات اليومي: 2,000,000 ل.س"* |
| **Document validation** | National ID expiry checked; if < 30 days from expiry → warning issued, KYC flagged for re-upload |

### 2.5 KYC_REJECTED

| Property | Value |
|----------|-------|
| **Entry** | Compliance officer rejects, or automated forgery detection fires |
| **Exit** | User re-uploads corrected documents, or account restricted after max attempts |
| **Max attempts** | 3 total submissions; after 3rd rejection → account moves to RESTRICTED |
| **Cooldown** | 24h before user can re-submit after rejection |
| **Rejection reasons (Syria-specific)** | ID غير واضح (unclear), صورة السيلفي لا تطابق الهوية (selfie mismatch), هوية منتهية الصلاحية (expired ID), فاتورة كهرباء/ماء أقدم من 3 أشهر (bill > 3 months old), مستندات غير أصلية (forgery suspicion) |
| **Notifications** | SMS: *"لم يتم توثيق حسابك. السبب: [reason]. يرجى إعادة تقديم المستندات. محاولة [n]/3"* |

### 2.6 ACTIVE

| Property | Value |
|----------|-------|
| **Entry** | KYC approved, user is fully verified |
| **Exit** | Transitions to DORMANT, RESTRICTED, BLOCKED, or CLOSED |
| **Allowed actions** | ALL V1 features: P2P send/receive, cash-in/cash-out, bill payments (electricity/water/telecom/internet), merchant payments (QR), USD remittance receive, FX conversion, balance inquiry, transaction history |
| **Limits** | Per KYC tier (Tier 2 default, Tier 3 available with additional KYC) |
| **Tier 3 upgrade** | Additional KYC: proof of income (salary certificate / business license), bank statement (3 months), source of funds declaration. Limit: 10,000,000 SYP/day |
| **Notifications** | *"حسابك نشط بالكامل. تمت ترقية حد المعاملات"* (on tier upgrade) |

### 2.7 DORMANT

| Property | Value |
|----------|-------|
| **Entry** | 90 consecutive calendar days with no customer-initiated transaction |
| **Exit** | Next successful customer-initiated transaction (send, bill pay, cash-out) |
| **Credit exemption** | Incoming P2P and remittance do NOT break dormancy (only outgoing activity counts) |
| **Time limit** | After 180 days dormant → account moves to RESTRICTED |
| **Notifications** | Warning at day 85: *"يرجى إجراء معاملة خلال 5 أيام للحفاظ على نشاط حسابك"*. At day 90: *"تم تجميد حسابك بسبب عدم النشاط. قم بتسجيل الدخول وإجراء معاملة لإعادة تفعيله"* |
| **Operations during** | Receive only. Cannot send or pay bills. Balance viewable. |

### 2.8 RESTRICTED

| Property | Value |
|----------|-------|
| **Entry** | Compliance flag (non-fraud reason), KYC rejection max attempts exceeded, 180-day dormancy |
| **Exit** | Issue resolved by compliance, or escalated to BLOCKED |
| **Allowed actions** | Receive only (max 100,000 SYP/day). Balance viewable. Cannot send, cash-out, FX, or merchant pay. |
| **Time limit** | 14 days auto-escalation to senior compliance if unresolved |
| **Notifications** | *"حسابك مقيد مؤقتاً. يرجى التواصل مع خدمة العملاء على الرقم 1234 لمزيد من المعلومات"* |

### 2.9 BLOCKED

| Property | Value |
|----------|-------|
| **Entry** | Fraud confirmed by fraud team, court order, CBS (Central Bank of Syria) directive, sanctions list match |
| **Exit** | Only by compliance manager + 2-person approval, or legal resolution |
| **Operations** | Complete freeze: no sends, no receives, no balance view. All sessions invalidated. |
| **Wallet cascade** | ALL wallets (SYP + USD if any) are frozen simultaneously |
| **Notifications** | *"عذراً، تم تجميد حسابك لأسباب أمنية. يرجى مراجعة فرع بيزا أو الاتصال على 1234"* |
| **Support alert** | P0 ticket raised to customer support, fraud team notified via Slack #fraud-alerts |
| **Suspicious activity triggers** | Multiple account creation from same device, unusual large txns (> 3x user's 90th percentile), rapid send-receive cycles (layering), VPN/proxy detection on login |

### 2.10 CLOSED

| Property | Value |
|----------|-------|
| **Entry** | User requests closure (7-day cooldown enforced), or admin closure (death, legal order), or auto-close after 1 year dormant + restricted |
| **Exit** | Terminal state — no exit |
| **Pre-conditions** | Balance must be zero (user must cash-out or transfer remaining funds before closure) |
| **Cooldown** | 7-day cooling-off period from request to execution (user can cancel within this window) |
| **Data retention** | Soft delete (30 days reversible by admin), then hard purge of PII. Transaction records retained 10 years per CBS regulation. Wallet history retained 5 years. |
| **Re-open** | Not possible. User must register fresh as PROSPECT. |
| **Notifications** | At request: *"تم استلام طلب إغلاق الحساب. سيتم الإغلاق بعد 7 أيام. يمكنك إلغاء الطلب خلال هذه الفترة"*. At execution: *"تم إغلاق حسابك في بيزا. شكراً لاستخدامك خدماتنا"* |
| **Admin closure reasons** | User death (شهادة وفاة), court order (أمر قضائي), CBS directive (تعليمات مصرف سورية المركزي), duplicate account detected, regulatory non-compliance |

## 3. Transition Table

| From | To | Trigger | Guard | System Action |
|------|----|---------|-------|--------------|
| PROSPECT | REGISTERED | OTP verified + PIN set | OTP not expired (< 5 min), attempts < 5, phone not blacklisted | Create user record in identity service, create wallet(s), send welcome SMS |
| REGISTERED | KYC_PENDING | Documents submitted via KYC upload API | File size < 10 MB, format in [JPEG, PNG, PDF], national ID format valid, file count >= 3 | Queue for compliance review (auto or manual), S3 upload, send acknowledgment SMS |
| KYC_PENDING | KYC_APPROVED | Compliance approval or auto-approval | All required docs present, no sanctions match, document quality >= threshold | Upgrade limits in wallet service, send approval SMS, log audit trail |
| KYC_PENDING | KYC_REJECTED | Compliance rejection or auto-rejection | Rejection reason provided | Send rejection SMS with reason, increment attempt counter, allow re-upload after 24h |
| KYC_REJECTED | KYC_PENDING | Re-submit documents | Attempts < 3, 24h cooldown elapsed | Reset review timer, clear previous rejection reason, re-queue for review |
| KYC_REJECTED | RESTRICTED | Max attempts (3) exceeded | Attempt count = 3 | Send restriction SMS, disable send capability, flag compliance |
| KYC_APPROVED | ACTIVE | System auto-transition | Approval event received | Enable full feature set, set KYC tier limits |
| ACTIVE | DORMANT | 90 days no outgoing txn | Last outgoing txn timestamp > 90 days | Set dormant flag, send warning SMS at day 85 |
| DORMANT | ACTIVE | Customer initiates any outgoing txn | Authentication passed, limits available | Clear dormant flag, restore full functionality, send reactivation SMS |
| DORMANT | RESTRICTED | 180 days dormant (90d dormant + 90d no action) | 180 days since last outgoing txn | Escalate to compliance, apply restriction |
| ACTIVE | RESTRICTED | Compliance flag (non-fraud) | Compliance officer action | Limit to receive-only, notify user |
| ACTIVE | RESTRICTED | Suspicious activity score >= 700 | Automated fraud scoring engine | Temporary restriction pending review |
| RESTRICTED | ACTIVE | Issue resolved | Compliance officer action | Restore full access, send notification |
| RESTRICTED | BLOCKED | Fraud confirmed during review | Fraud team confirmation | Freeze all wallets, alert support, invalidate sessions |
| ACTIVE | BLOCKED | Fraud confirmed | Fraud team role + evidence | Freeze all wallets, P0 alert, CBS reporting if required |
| ACTIVE | BLOCKED | Court order received | Legal team verification | Immediate freeze, document retention for legal |
| BLOCKED | ACTIVE | Investigation cleared | Compliance manager + 2-person approval | Unfreeze wallets, restore limits, send notification |
| ACTIVE | CLOSED | User requests closure | 7-day cooldown not yet started, balance = 0 | Start cooldown timer, queue closure job |
| CLOSED_(cooldown) | ACTIVE | User cancels closure | Within 7-day window | Cancel closure request, notify user |
| (ANY) | CLOSED | User death, legal order, CBS directive | Legal team / admin role | Hard close, bypass cooldown, retain records 10 years |
| CLOSED | BLOCKED | — | — | Not possible — CLOSED is terminal |

## 4. Lifecycle Events

| Event | Producer | Consumers | Payload |
|-------|----------|-----------|---------|
| `UserRegistered` | Identity service | Notification, Analytics, Wallet | `{ user_id, phone, registered_at }` |
| `KYCDocumentsSubmitted` | Identity service | Compliance queue, Notification | `{ user_id, document_ids[], submission_id }` |
| `KYCApproved` | Compliance service | Wallet (upgrade limits), Notification | `{ user_id, kyc_tier, approved_by, approved_at }` |
| `KYCRejected` | Compliance service | Notification, Identity (track attempts) | `{ user_id, reason, attempt_number, rejection_code }` |
| `UserRestricted` | Compliance service | Wallet (limit override), Fraud engine, Notification | `{ user_id, restriction_reason, restricted_by }` |
| `UserBlocked` | Compliance service | ALL services (deactivate), Notification | `{ user_id, block_reason, case_id, blocked_by }` |
| `UserDormant` | Scheduler (cron) | Wallet (set dormant flag), Notification | `{ user_id, days_since_last_txn, dormant_at }` |
| `UserReactivated` | Transaction service | Wallet (clear dormant flag) | `{ user_id, trigger_txn_id, reactivated_at }` |
| `UserClosed` | Admin / Legal / Self-service | Notification, Archive service, Wallet (close wallets) | `{ user_id, closure_reason, closed_by, closed_at }` |
| `ClosureCancelled` | Self-service | Notification | `{ user_id, cancelled_at }` |

## 5. Business Rules Summary

### 5.1 KYC Document Validation
- National ID (الرقم الوطني): 11-digit numeric, validate checksum
- Selfie: face must match ID photo (>= 70% confidence for manual, >= 90% for auto-approve)
- Proof of address: electricity bill (فاتورة كهرباء) or water bill (فاتورة ماء) issued within last 90 days
- All documents must be in Arabic or bilingual (Arabic + English)
- Max 3 submission attempts lifetime

### 5.2 Tier Limits

| Tier | Max Balance (SYP) | Max Daily Send | Max Daily Receive | FX (USD/month) |
|------|-------------------|----------------|-------------------|-----------------|
| 1 (No KYC) | 500,000 | 250,000 | 500,000 | 0 |
| 2 (KYC basic) | 5,000,000 | 2,000,000 | 5,000,000 | 500 |
| 3 (KYC enhanced) | 20,000,000 | 10,000,000 | 20,000,000 | 2,000 |

### 5.3 CBS (Central Bank of Syria) Compliance
- All transactions > 1,000,000 SYP reported to CBS within 24 hours
- Customer records retained 10 years post-closure
- Fraud/suspicious activity reports filed within 48 hours
- Sanctions list (OFAC, EU, local Syrian sanctions) checked on every KYC approval and daily batch
- PEP (Politically Exposed Person) screening on Tier 3 upgrades

### 5.4 Notification Preferences
- All notifications via SMS (primary channel for Syrian market)
- Arabic is default language
- In-app notification as secondary channel (if app installed)
- Opt-out available for non-transactional messages
