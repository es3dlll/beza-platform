# Remittance Event Architecture

## Events Produced

### TransferSent
```json
{
  "specversion": "1.0",
  "id": "evt_rem_sent_abc123",
  "source": "/beza/remittance/1.0",
  "type": "com.beza.remittance.sent",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "remittance_id": "rem_abc123",
    "sender_id": 42,
    "sender_country": "DE",
    "beneficiary_id": 87,
    "recipient_id": 92,
    "corridor": "EUR_DE->SYP",
    "source_amount": 300.00,
    "source_currency": "EUR",
    "target_amount": 3960000,
    "target_currency": "SYP",
    "fx_rate": 13200,
    "fee": 4.50,
    "fee_currency": "EUR",
    "type": "diaspora",
    "note": "مصاريف البيت - يونيو",
    "sender_device_id": "device_abc",
    "sender_ip": "78.46.123.45",
    "risk_score": 8,
    "compliance_status": "passed",
    "created_at": "2026-06-15T10:00:00Z"
  }
}
```
**Consumers**: Notification (recipient + sender), Analytics, Fraud Detection, Compliance (AML), FX Analytics, Loyalty

### TransferReceived
```json
{
  "specversion": "1.0",
  "id": "evt_rem_recv_abc123",
  "source": "/beza/remittance/1.0",
  "type": "com.beza.remittance.received",
  "datacontenttype": "application/json",
  "subject": "user_92",
  "time": "2026-06-15T10:00:08Z",
  "tenant_id": "tenant_1",
  "data": {
    "remittance_id": "rem_abc123",
    "recipient_id": 92,
    "sender_id": 42,
    "sender_name": "Khalid",
    "amount": 3960000,
    "currency": "SYP",
    "recipient_balance_after": 4100000,
    "delivery_method": "wallet",
    "created_at": "2026-06-15T10:00:08Z"
  }
}
```
**Consumers**: Notification (push + SMS to recipient), Analytics, Sender delivery confirmation

### TransferFailed
```json
{
  "specversion": "1.0",
  "id": "evt_rem_fail_abc124",
  "source": "/beza/remittance/1.0",
  "type": "com.beza.remittance.failed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T10:00:05Z",
  "tenant_id": "tenant_1",
  "data": {
    "remittance_id": "rem_abc124",
    "sender_id": 42,
    "source_amount": 300.00,
    "source_currency": "EUR",
    "failure_reason": "insufficient_balance",
    "failure_code": "INSUFFICIENT_BALANCE",
    "attempted_at": "2026-06-15T10:00:03Z",
    "idempotency_key": "uuid",
    "fx_rate_lock_id": "fx_lock_abc123",
    "fx_rate_released": true
  }
}
```
**Consumers**: Analytics, Support (ticket creation if repeated), FX Engine (release lock)

### FXLocked
```json
{
  "specversion": "1.0",
  "id": "evt_fx_lock_abc123",
  "source": "/beza/fx/1.0",
  "type": "com.beza.fx.locked",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T10:00:02Z",
  "tenant_id": "tenant_1",
  "data": {
    "lock_id": "fx_lock_abc123",
    "corridor_id": 1,
    "corridor": "EUR_DE->SYP",
    "rate": 13200,
    "mid_market_rate": 13420,
    "spread_percent": 1.8,
    "locked_by_user_id": 42,
    "locked_at": "2026-06-15T10:00:02Z",
    "expires_at": "2026-06-15T10:01:02Z",
    "amount": 300.00,
    "source_currency": "EUR",
    "target_currency": "SYP"
  }
}
```
**Consumers**: FX Rate Analytics, Risk Management (hedging), Audit Trail

### RecurringTransferExecuted
```json
{
  "specversion": "1.0",
  "id": "evt_rec_exec_789",
  "source": "/beza/remittance/recurring/1.0",
  "type": "com.beza.remittance.recurring.executed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-07-01T08:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "recurring_id": 7,
    "remittance_id": "rem_def456",
    "sender_id": 42,
    "beneficiary_id": 87,
    "recipient_id": 92,
    "amount": 200.00,
    "source_currency": "EUR",
    "target_amount": 2640000,
    "target_currency": "SYP",
    "fx_rate": 13200,
    "fee": 3.00,
    "execution_number": 1,
    "total_sent_to_date": 200.00,
    "next_execution_at": "2026-08-01T08:00:00Z",
    "status": "completed",
    "created_at": "2026-07-01T08:00:00Z"
  }
}
```
**Consumers**: Notification (sender receipt, recipient SMS), Analytics, Loyalty

### RemittanceCompleted
```json
{
  "specversion": "1.0",
  "id": "evt_rem_complete_abc123",
  "source": "/beza/remittance/1.0",
  "type": "com.beza.remittance.completed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T10:00:08Z",
  "tenant_id": "tenant_1",
  "data": {
    "remittance_id": "rem_abc123",
    "sender_id": 42,
    "recipient_id": 92,
    "corridor": "EUR_DE->SYP",
    "source_amount": 300.00,
    "source_currency": "EUR",
    "target_amount": 3960000,
    "target_currency": "SYP",
    "fx_rate": 13200,
    "fee_charged": 4.50,
    "fx_spread_income": 540000,     // SYP equivalent of spread
    "total_revenue": 4.50,          // EUR
    "delivery_method": "wallet",
    "end_to_end_duration_ms": 8000,
    "timeline": {
      "initiated": "10:00:00.000",
      "fx_locked": "10:00:02.150",
      "debit_confirmed": "10:00:04.320",
      "fx_converted": "10:00:05.100",
      "credit_confirmed": "10:00:07.890",
      "completed": "10:00:08.000"
    },
    "created_at": "2026-06-15T10:00:08Z"
  }
}
```
**Consumers**: Analytics (revenue reporting), Finance (settlement), Compliance (audit), Operations

### MoneyRequested
```json
{
  "specversion": "1.0",
  "id": "evt_money_req_abc",
  "source": "/beza/remittance/request/1.0",
  "type": "com.beza.remittance.request.created",
  "datacontenttype": "application/json",
  "subject": "user_87",
  "time": "2026-06-15T11:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "request_id": 15,
    "requester_id": 87,
    "requestee_id": 42,
    "amount": 100.00,
    "currency": "EUR",
    "note": "مساهمة في علاج الوالدة",
    "expires_at": "2026-06-22T11:00:00Z",
    "created_at": "2026-06-15T11:00:00Z"
  }
}
```
**Consumers**: Notification (push to requestee), Analytics

## Event Flow Diagram
```
RemittanceController::send()
    │
    ▼
SendRemittanceAction::execute()
    │
    ├── Validate (corridor active, limits, balance)
    ├── Get/Lock FX rate
    ├── Screen compliance (sanctions, AML)
    ├── Debit sender wallet (source currency)
    ├── Convert via FX engine
    ├── Credit recipient wallet (target currency)
    ├── Persist remittance record
    │
    ├── emit(FXLocked) ──────────────────────────────────┐
    │    ├── Queue: FXHedgingCheck                       │
    │    └── Queue: FXRateAnalytics                      │
    │                                                     │
    ├── emit(TransferSent) ───────────────────────────────┤
    │    ├── Queue: NotifyRecipientJob                    │
    │    │    ├── Push notification to recipient          │
    │    │    └── SMS notification                        │
    │    ├── Queue: NotifySenderJob                       │
    │    │    └── Push: "تم إرسال المبلغ"                 │
    │    ├── Queue: FraudVelocityCheck                    │
    │    ├── Queue: AnalyticsIngestion                    │
    │    └── Queue: AMLScreeningAsync                     │
    │                                                     │
    ├── emit(RemittanceCompleted) ────────────────────────┤
    │    ├── Queue: RevenueAnalytics                      │
    │    ├── Queue: SettlementTrigger                     │
    │    ├── Queue: LoyaltyPointsEarn                     │
    │    └── Queue: ComplianceAuditLog                    │
    │                                                     │
    └── emit(TransferReceived) ───────────────────────────┘
         ├── Queue: NotifyRecipientDelivery               │
         ├── Queue: AnalyticsIngestion                    │
         └── Queue: RecurringContribution                 │
```

## Event Schema Registry
| Event | Version | Schema URL | Produced By |
|-------|---------|-----------|-------------|
| TransferSent | 1.0 | /schemas/remittance/transfer-sent.v1.json | SendRemittanceAction |
| TransferReceived | 1.0 | /schemas/remittance/transfer-received.v1.json | CreditRecipientAction |
| TransferFailed | 1.0 | /schemas/remittance/transfer-failed.v1.json | SendRemittanceAction |
| FXLocked | 1.0 | /schemas/fx/rate-locked.v1.json | LockFXRateAction |
| RecurringTransferExecuted | 1.0 | /schemas/remittance/recurring-executed.v1.json | ExecuteRecurringAction |
| RemittanceCompleted | 1.0 | /schemas/remittance/completed.v1.json | SendRemittanceAction |
| MoneyRequested | 1.0 | /schemas/remittance/money-requested.v1.json | RequestMoneyAction |
