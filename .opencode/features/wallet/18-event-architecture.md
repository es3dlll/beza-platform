# Wallet Event Architecture

## Events Produced

### WalletCredited
```json
{
  "specversion": "1.0",
  "id": "evt_wallet_cred_abc123",
  "source": "/beza/wallet/1.0",
  "type": "com.beza.wallet.credited",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "wallet_id": 1,
    "user_id": 42,
    "amount": 50000,
    "currency": "SYP",
    "balance_after": 150000,
    "transaction_id": "txn_fund_789",
    "source": "agent_cash_in",
    "agent_id": 15,
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Analytics, Savings (round-up check), Loyalty, Notification, Compliance

### WalletDebited
```json
{
  "specversion": "1.0",
  "id": "evt_wallet_deb_abc123",
  "source": "/beza/wallet/1.0",
  "type": "com.beza.wallet.debited",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "wallet_id": 1,
    "user_id": 42,
    "amount": 25125,
    "currency": "SYP",
    "fee": 125,
    "balance_after": 74875,
    "transaction_id": "txn_abc123",
    "destination": "p2p_transfer",
    "recipient_id": 87,
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Analytics, Notification, Fraud Detection, Compliance (AML)

### TransferSent
```json
{
  "specversion": "1.0",
  "id": "evt_txn_sent_abc123",
  "source": "/beza/transfer/1.0",
  "type": "com.beza.transfer.sent",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": "txn_abc123",
    "sender_id": 42,
    "recipient_id": 87,
    "amount": 25000,
    "fee": 125,
    "currency": "SYP",
    "fx_rate": null,
    "note": "Rent for June",
    "sender_balance_after": 74875,
    "recipient_balance_after": 109850,
    "sender_device_id": "device_abc",
    "sender_ip": "176.203.12.34",
    "sender_location": {"lat": 33.5138, "lng": 36.2765},
    "risk_score": 12,
    "fraud_check_passed": true,
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Notification (recipient), Analytics, Fraud (velocity check), Compliance

### TransferReceived
```json
{
  "specversion": "1.0",
  "id": "evt_txn_recv_abc123",
  "source": "/beza/transfer/1.0",
  "type": "com.beza.transfer.received",
  "datacontenttype": "application/json",
  "subject": "user_87",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": "txn_abc123",
    "recipient_id": 87,
    "sender_id": 42,
    "amount": 25000,
    "currency": "SYP",
    "recipient_balance_after": 109850,
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Notification (push + SMS), Analytics

### TransferFailed
```json
{
  "specversion": "1.0",
  "id": "evt_txn_fail_abc124",
  "source": "/beza/transfer/1.0",
  "type": "com.beza.transfer.failed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:05Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": null,
    "sender_id": 42,
    "amount": 25125,
    "currency": "SYP",
    "failure_reason": "insufficient_balance",
    "failure_code": "INSUFFICIENT_BALANCE",
    "attempted_at": "2026-06-01T10:00:03Z",
    "idempotency_key": "uuid"
  }
}
```
**Consumers**: Analytics, Support (ticket creation if repeated)

### WalletDailyLimitExceeded
```json
{
  "specversion": "1.0",
  "id": "evt_limit_exceeded_abc",
  "source": "/beza/wallet/1.0",
  "type": "com.beza.wallet.daily_limit_exceeded",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "user_id": 42,
    "wallet_id": 1,
    "currency": "SYP",
    "attempted_amount": 300000,
    "daily_limit": 500000,
    "daily_used": 475000,
    "remaining": 25000,
    "kyc_level": 1,
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Notification (suggest KYC upgrade), Analytics

## Event Flow Diagram
```
TransferController::send()
    │
    ▼
SendMoneyAction::execute()
    │
    ├── Validate (limits, balance, recipient)
    ├── Calculate fee
    ├── CFE: hold → post → settle
    ├── Persist transaction
    │
    ├── emit(TransferSent) ─────────────────────────────┐
    │    ├── Queue: NotifyRecipientJob                  │
    │    │    ├── Push notification to recipient app    │
    │    │    └── SMS notification                      │
    │    ├── Queue: FraudVelocityCheck                  │
    │    ├── Queue: AnalyticsIngestion                  │
    │    └── Queue: AMLScreening                        │
    │                                                   │
    ├── emit(WalletDebited) ────────────────────────────┤
    │    ├── Queue: SavingsRoundupCheck                 │
    │    ├── Queue: SpendingAnalytics                   │
    │    └── Queue: LoyaltyPointsEarn                   │
    │                                                   │
    └── emit(WalletCredited on recipient) ──────────────┤
         ├── Queue: NotifyRecipientJob                  │
         ├── Queue: AnalyticsIngestion                  │
         └── Queue: ComplianceCheck                     │
```
