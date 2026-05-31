# Bill Payment Event Architecture

## Events Produced

### BillFetched
```json
{
  "specversion": "1.0",
  "id": "evt_bill_fetch_abc123",
  "source": "/beza/bill-payment/1.0",
  "type": "com.beza.bill.fetched",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-10T09:30:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "user_id": 42,
    "biller_type": "peed",
    "biller_name": "الشركة العامة للكهرباء",
    "customer_id": "123456789012345678901234",
    "customer_name": "أحمد خالد",
    "invoice_number": "PE-2026-789012",
    "amount": 42500,
    "late_fee": 2125,
    "total_due": 44625,
    "due_date": "2026-06-15",
    "biller_reference": "PE1234567890",
    "fetch_duration_ms": 320,
    "created_at": "2026-06-10T09:30:00Z"
  }
}
```
**Consumers**: Analytics (biller usage metrics), Fraud Detection (unusual fetch patterns)

### BillPaid
```json
{
  "specversion": "1.0",
  "id": "evt_bill_paid_abc123",
  "source": "/beza/bill-payment/1.0",
  "type": "com.beza.bill.paid",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-10T09:30:05Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": "bptxn_abc123",
    "user_id": 42,
    "wallet_id": 1,
    "biller_type": "peed",
    "biller_name": "الشركة العامة للكهرباء",
    "biller_id": 1,
    "customer_id": "123456789012345678901234",
    "customer_name": "أحمد خالد",
    "invoice_number": "PE-2026-789012",
    "bill_amount": 42500,
    "late_fee": 2125,
    "fee": 224,
    "total": 44849,
    "beza_reference": "BILL-PEED-20260610-ABCDEFGHIJ",
    "biller_reference": "PE1234567890-CONFIRM",
    "wallet_balance_before": 124849,
    "wallet_balance_after": 80000,
    "paid_at": "2026-06-10T09:30:05Z",
    "receipt_url": "https://cdn.beza.com/receipts/bptxn_abc123.pdf",
    "payment_duration_ms": 2800,
    "device_id": "device_abc",
    "ip_address": "176.203.12.34",
    "location": {"lat": 33.5138, "lng": 36.2765},
    "created_at": "2026-06-10T09:30:05Z"
  }
}
```
**Consumers**: Analytics (revenue, usage), Notification (receipt), Wallet (balance sync), Savings (round-up), Fraud Detection, Compliance (AML), BillingScheduler (update schedule)

### BillPaymentFailed
```json
{
  "specversion": "1.0",
  "id": "evt_bill_fail_abc124",
  "source": "/beza/bill-payment/1.0",
  "type": "com.beza.bill.payment_failed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-10T09:30:05Z",
  "tenant_id": "tenant_1",
  "data": {
    "user_id": 42,
    "biller_type": "peed",
    "biller_name": "الشركة العامة للكهرباء",
    "customer_id": "123456789012345678901234",
    "invoice_number": "PE-2026-789012",
    "amount": 44625,
    "failure_reason": "insufficient_balance",
    "failure_code": "INSUFFICIENT_BALANCE",
    "attempted_at": "2026-06-10T09:30:03Z",
    "idempotency_key": "uuid"
  }
}
```
**Consumers**: Notification (alert user), Analytics, Support (ticket if repeated), Wallet (release hold if held)

### BillReminderDue
```json
{
  "specversion": "1.0",
  "id": "evt_bill_remind_abc",
  "source": "/beza/bill-payment/1.0",
  "type": "com.beza.bill.reminder_due",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-12T06:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "schedule_id": 1,
    "user_id": 42,
    "biller_type": "peed",
    "biller_name": "الشركة العامة للكهرباء",
    "customer_id": "123456789012345678901234",
    "amount": null,
    "next_due": "2026-06-15",
    "days_until_due": 3,
    "reminder_days": 3,
    "auto_pay_enabled": true,
    "created_at": "2026-06-12T06:00:00Z"
  }
}
```
**Consumers**: Notification (push + SMS reminder)

### AutoPayCompleted
```json
{
  "specversion": "1.0",
  "id": "evt_autopay_abc",
  "source": "/beza/bill-payment/1.0",
  "type": "com.beza.bill.auto_pay_completed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T08:00:05Z",
  "tenant_id": "tenant_1",
  "data": {
    "schedule_id": 1,
    "transaction_id": "bptxn_abc789",
    "user_id": 42,
    "biller_type": "peed",
    "biller_name": "الشركة العامة للكهرباء",
    "customer_id": "123456789012345678901234",
    "amount": 42500,
    "fee": 224,
    "total": 44849,
    "beza_reference": "BILL-PEED-20260615-XXXXXXXXXX",
    "biller_reference": "PE1234567890-AUTOPAY",
    "paid_at": "2026-06-15T08:00:05Z",
    "created_at": "2026-06-15T08:00:05Z"
  }
}
```
**Consumers**: Notification (confirmation), Analytics, Schedule (update next_due)

### AutoPayFailed
```json
{
  "specversion": "1.0",
  "id": "evt_autopay_fail_abc",
  "source": "/beza/bill-payment/1.0",
  "type": "com.beza.bill.auto_pay_failed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T08:00:05Z",
  "tenant_id": "tenant_1",
  "data": {
    "schedule_id": 1,
    "user_id": 42,
    "biller_type": "peed",
    "customer_id": "123456789012345678901234",
    "failure_reason": "insufficient_balance",
    "auto_pay_failures": 1,
    "next_retry_at": "2026-06-15T12:00:00Z",
    "created_at": "2026-06-15T08:00:05Z"
  }
}
```
**Consumers**: Notification (alert user to fund wallet), Schedule (update failure count)

### CsvBatchReady
```json
{
  "specversion": "1.0",
  "id": "evt_csv_batch_abc",
  "source": "/beza/bill-payment/1.0",
  "type": "com.beza.bill.csv_batch_ready",
  "datacontenttype": "application/json",
  "subject": "system",
  "time": "2026-06-10T04:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "batch_id": 15,
    "biller_type": "government_fees",
    "biller_name": "الرسوم الحكومية",
    "total_records": 12500,
    "processed_at": "2026-06-10T04:00:00Z"
  }
}
```
**Consumers**: Notification (match users with pending items), Analytics

## Event Flow Diagram
```
BillController::fetch()
    │
    ├── Validate customer ID format
    ├── BillerProviderService::getBiller()
    ├── BillerInterface::fetchBill()
    │
    ├── emit(BillFetched) ──────────────
    │    ├── Analytics: biller usage stats
    │    └── Fraud: unusual fetch patterns
    │
    └── Return BillDTO to user

PaymentController::pay()
    │
    ├── Validate PIN
    ├── BillPaymentService::payBill()
    │   ├── Re-fetch bill (fresh data)
    │   ├── Calculate fee
    │   ├── Check wallet balance
    │   ├── CFE: hold → post
    │   ├── BillerInterface::confirmPayment()
    │   ├── Persist transaction
    │   ├── Generate receipt
    │   │
    │   ├── emit(BillPaid) ──────────────────────────────────────────┐
    │   │    ├── Queue: SendBillReceiptNotification                  │
    │   │    │    ├── Push notification to user                      │
    │   │    │    └── SMS receipt                                    │
    │   │    ├── Queue: SavingsRoundupCheck                          │
    │   │    ├── Queue: FraudVelocityCheck                           │
    │   │    ├── Queue: AnalyticsIngestion                           │
    │   │    ├── Queue: AMLScreening                                 │
    │   │    └── Queue: UpdateBillingSchedule                        │
    │   │                                                             │
    │   └── emit(WalletDebited) ──────────────────────────────────── │
    │        ├── Queue: SpendingAnalytics                             │
    │        └── Queue: LoyaltyPointsEarn                             │
    │
    └── Return PaymentResult with receipt

BillingScheduler::processDueReminders() [Hourly Cron]
    │
    ├── Find schedules due for reminder
    ├── For each: emit(BillReminderDue) ─── Queue: SendReminder

BillingScheduler::processAutoPay() [Daily 08:00]
    │
    ├── Find schedules due for auto-pay
    ├── For each:
    │   ├── Fetch bill
    │   ├── If already paid → skip
    │   ├── If not paid → execute payment
    │   │   ├── Success → emit(AutoPayCompleted)
    │   │   └── Failure → emit(AutoPayFailed) → retry 3x
```
