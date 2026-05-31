# Cards Event Architecture

## Events Produced

### CardCreated
```json
{
  "specversion": "1.0",
  "id": "evt_card_created_abc123",
  "source": "/beza/cards/1.0",
  "type": "com.beza.cards.created",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "card_id": 1,
    "user_id": 42,
    "bin": "639123",
    "last_four": "4567",
    "card_type": "virtual",
    "card_network": "mastercard",
    "currency": "SYP",
    "card_program": "beza_standard_syp",
    "limits": {
      "online": 500000,
      "pos": 200000,
      "atm": 0,
      "international": 0
    },
    "fee": 5000,
    "issued_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Analytics, Notification (welcome message), Wallet (card wallet creation), Compliance

### CardFrozen
```json
{
  "specversion": "1.0",
  "id": "evt_card_frozen_abc123",
  "source": "/beza/cards/1.0",
  "type": "com.beza.cards.frozen",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T19:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "card_id": 1,
    "user_id": 42,
    "status": "frozen",
    "reason": "suspicious_charge",
    "frozen_at": "2026-06-15T19:00:00Z"
  }
}
```
**Consumers**: Notification (push/SMS to user), Fraud detection (add context), Token service (suspend tokens)

### CardUnfrozen
```json
{
  "specversion": "1.0",
  "id": "evt_card_unfrozen_abc123",
  "source": "/beza/cards/1.0",
  "type": "com.beza.cards.unfrozen",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-16T08:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "card_id": 1,
    "user_id": 42,
    "status": "active",
    "unfrozen_at": "2026-06-16T08:00:00Z"
  }
}
```

### CardTransactionAuthorized
```json
{
  "specversion": "1.0",
  "id": "evt_card_auth_abc123",
  "source": "/beza/cards/1.0",
  "type": "com.beza.cards.transaction.authorized",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T18:30:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": 1,
    "card_id": 1,
    "user_id": 42,
    "type": "purchase",
    "amount": 125000,
    "currency": "SYP",
    "merchant_name": "AliExpress",
    "merchant_category": "ecommerce",
    "merchant_country": "CN",
    "auth_code": "AUTH-ABC123",
    "rrn": "RRN-987654",
    "stan": "123456",
    "card_present": false,
    "tokenized": false,
    "fraud_score": 12.5,
    "authorized_at": "2026-06-15T18:30:00Z"
  }
}
```
**Consumers**: Notification (push to user), Spending totals updater, Analytics, Fraud feedback loop

### CardTransactionSettled
```json
{
  "specversion": "1.0",
  "id": "evt_card_settled_abc123",
  "source": "/beza/cards/1.0",
  "type": "com.beza.cards.transaction.settled",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T23:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": 1,
    "card_id": 1,
    "amount": 125000,
    "fee": 1500,
    "interchange": 1500,
    "settled_at": "2026-06-15T23:00:00Z",
    "settlement_batch": "BATCH-20260615-001"
  }
}
```
**Consumers**: Ledger (post to CFE), Revenue recognition, Fee calculation

### CardTransactionDeclined
```json
{
  "specversion": "1.0",
  "id": "evt_card_declined_abc123",
  "source": "/beza/cards/1.0",
  "type": "com.beza.cards.transaction.declined",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-15T19:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "card_id": 1,
    "user_id": 42,
    "amount": 500000,
    "currency": "SYP",
    "merchant_name": "Amazon",
    "decline_reason": "limit_exceeded",
    "merchant_category": "ecommerce",
    "fraud_score": 15.0,
    "declined_at": "2026-06-15T19:00:00Z"
  }
}
```
**Consumers**: Notification (push to user), Fraud analytics (false positive tracking), Limit suggestion engine

### CardClosed
```json
{
  "specversion": "1.0",
  "id": "evt_card_closed_abc123",
  "source": "/beza/cards/1.0",
  "type": "com.beza.cards.closed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-20T12:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "card_id": 1,
    "user_id": 42,
    "reason": "user_request",
    "closed_at": "2026-06-20T12:00:00Z"
  }
}
```

## Event Consumer Mapping
| Event | Push Notif | SMS | Analytics | Ledger | Fraud | Compliance | Spending |
|-------|-----------|-----|-----------|--------|-------|-----------|----------|
| CardCreated | ✓ | ✓ | ✓ | ✓ | | ✓ | |
| CardFrozen | ✓ | ✓ | ✓ | | ✓ | | |
| CardUnfrozen | ✓ | ✓ | ✓ | | | | |
| TransactionAuthorized | ✓ | ✓ | ✓ | | ✓ | | ✓ |
| TransactionSettled | | | ✓ | ✓ | ✓ | ✓ | |
| TransactionDeclined | ✓ | | ✓ | | ✓ | | |
| CardClosed | ✓ | | ✓ | ✓ | | ✓ | |
