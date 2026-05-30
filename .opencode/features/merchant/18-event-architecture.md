# Merchant Event Architecture

## Events Produced

### MerchantRegistered
```json
{
  "specversion": "1.0",
  "id": "evt_merchant_reg_abc123",
  "source": "/beza/merchant/1.0",
  "type": "com.beza.merchant.registered",
  "datacontenttype": "application/json",
  "subject": "merchant_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "merchant_id": 42,
    "user_id": 100,
    "business_name": "متجر الشمّام",
    "business_type": "grocery",
    "tier": "small",
    "mdr_rate": 1.5,
    "status": "pending",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Analytics, Notification (admin), Onboarding (create default QR), Compliance

### MerchantVerified
```json
{
  "specversion": "1.0",
  "id": "evt_merchant_ver_abc123",
  "source": "/beza/merchant/1.0",
  "type": "com.beza.merchant.verified",
  "datacontenttype": "application/json",
  "subject": "merchant_42",
  "time": "2026-06-01T12:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "merchant_id": 42,
    "user_id": 100,
    "business_name": "متجر الشمّام",
    "approved": true,
    "verified_at": "2026-06-01T12:00:00Z",
    "qr_code_url": "https://cdn.beza.com/merchant/42/qr_static.png"
  }
}
```
**Consumers**: Notification (push + SMS to merchant), Analytics, Onboarding

### QrPaymentCompleted
```json
{
  "specversion": "1.0",
  "id": "evt_qr_pay_abc123",
  "source": "/beza/merchant/1.0",
  "type": "com.beza.merchant.qr_payment_completed",
  "datacontenttype": "application/json",
  "subject": "merchant_42",
  "time": "2026-06-01T10:30:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": "txn_mer_abc123",
    "merchant_id": 42,
    "merchant_name": "متجر الشمّام",
    "customer_id": 55,
    "customer_phone": "+963912345678",
    "amount": 45000,
    "mdr_rate": 1.5,
    "mdr_amount": 675,
    "net_amount": 44325,
    "currency": "SYP",
    "method": "qr",
    "qr_type": "static",
    "qr_id": 1,
    "reference": "TXN-MER-ABC123",
    "created_at": "2026-06-01T10:30:00Z"
  }
}
```
**Consumers**: Merchant notification (push/SMS/voice), Webhook delivery, Analytics, Settlement engine

### PaymentLinkPaid
```json
{
  "specversion": "1.0",
  "id": "evt_link_paid_abc123",
  "source": "/beza/merchant/1.0",
  "type": "com.beza.merchant.payment_link_paid",
  "datacontenttype": "application/json",
  "subject": "merchant_42",
  "time": "2026-06-01T11:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": "txn_mer_def456",
    "merchant_id": 42,
    "merchant_name": "متجر الشمّام",
    "link_id": "pl_abc123",
    "description": "شنطة ظهر جلدية",
    "customer_id": 78,
    "customer_phone": "+963987654321",
    "amount": 45000,
    "mdr_rate": 2.0,
    "mdr_amount": 900,
    "net_amount": 44100,
    "currency": "SYP",
    "reference": "TXN-MER-DEF456",
    "created_at": "2026-06-01T11:00:00Z"
  }
}
```
**Consumers**: Merchant notification (push), Webhook delivery, Analytics, Settlement engine

### MerchantSettled
```json
{
  "specversion": "1.0",
  "id": "evt_settle_abc123",
  "source": "/beza/merchant/1.0",
  "type": "com.beza.merchant.settled",
  "datacontenttype": "application/json",
  "subject": "merchant_42",
  "time": "2026-06-02T00:15:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "settlement_id": 1,
    "merchant_id": 42,
    "merchant_name": "متجر الشمّام",
    "period_start": "2026-06-01T00:00:00Z",
    "period_end": "2026-06-01T23:59:59Z",
    "gross_amount": 850000,
    "mdr_amount": 12750,
    "net_amount": 837250,
    "currency": "SYP",
    "transaction_count": 12,
    "cfe_reference": "CFE_SETTLE_001",
    "paid_at": "2026-06-02T00:15:00Z"
  }
}
```
**Consumers**: Merchant notification (push), Analytics, Accounting, Webhook delivery

### MerchantPayoutFailed
```json
{
  "specversion": "1.0",
  "id": "evt_payout_fail_abc123",
  "source": "/beza/merchant/1.0",
  "type": "com.beza.merchant.payout_failed",
  "datacontenttype": "application/json",
  "subject": "merchant_42",
  "time": "2026-06-02T00:20:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "settlement_id": 1,
    "merchant_id": 42,
    "amount": 837250,
    "currency": "SYP",
    "failure_reason": "merchant_wallet_frozen",
    "failure_code": "WALLET_FROZEN",
    "attempt_count": 3,
    "next_retry_at": "2026-06-02T01:00:00Z"
  }
}
```
**Consumers**: Ops alert (Slack), Notification to merchant, Support ticket creation

## Event Flow Diagram
```
Customer scans QR → PayController
    │
    ▼
ProcessQrPaymentAction::execute()
    │
    ├── Validate QR (exists, active, not expired)
    ├── Validate amount (within merchant limits)
    ├── CFE: hold customer → post debit → credit merchant (gross)
    ├── Calculate MDR → post MDR fee debit
    ├── Save merchant_transaction record
    │
    ├── emit(QrPaymentCompleted) ──────────────────────────────┐
    │    ├── Queue: NotifyMerchantPaymentJob                   │
    │    │    ├── Push notification to merchant app            │
    │    │    ├── SMS notification                             │
    │    │    └── Voice announcement (if enabled)              │
    │    ├── Queue: DeliverWebhookJob                          │
    │    │    ├── POST to merchant webhook URL                 │
    │    │    ├── Retry 3x with exponential backoff            │
    │    │    └── Log delivery result                          │
    │    ├── Queue: UpdateMerchantAnalytics                    │
    │    └── Queue: FraudCheck (velocity, amount patterns)     │
    │                                                          │
    └── emit(WalletDebited on customer) ───────────────────────┘
         ├── Queue: CustomerReceipt (SMS/WhatsApp)
         └── Queue: SpendingAnalytics

Daily Cron (23:59) → ProcessSettlementJob
    │
    ├── For each active merchant with transactions today:
    │   ├── Calculate gross, MDR, net
    │   ├── Create settlement record
    │   ├── CFE: credit merchant wallet (net amount)
    │   └── emit(MerchantSettled)
    │        ├── Queue: NotifyMerchantSettlement
    │        ├── Queue: DeliverWebhookJob
    │        └── Queue: GenerateSettlementReport
    │
    └── If failed:
         ├── emit(MerchantPayoutFailed)
         ├── Queue: AlertOps (Slack/PagerDuty)
         └── Queue: AutoRetrySettlement (30 min later)
```
