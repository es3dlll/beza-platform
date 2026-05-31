# Payment Integration

## Payment Method: Beza Wallet

The primary (and initial) payment method for Marketplace is the Beza Wallet.

### Two-Phase Wallet Transaction

```
Phase 1: HOLD
  - Deduct amount from user's available balance
  - Mark as "held" (not available for withdrawal)
  - Wallet balance display: available = total - held
  - Hold expires after 30 min if not confirmed

Phase 2: RELEASE / CONFIRM
  - On successful fulfillment: hold becomes permanent deduction
  - On cancellation/refund: hold is released back to available balance
  - On failure: automatic release within 1 hour
```

### Wallet API Integration

The Marketplace service interacts with the Wallet service via internal gRPC:

```protobuf
service WalletService {
  rpc PlaceHold(PlaceHoldRequest) returns (PlaceHoldResponse);
  rpc ConfirmHold(ConfirmHoldRequest) returns (ConfirmHoldResponse);
  rpc ReleaseHold(ReleaseHoldRequest) returns (ReleaseHoldResponse);
  rpc GetBalance(GetBalanceRequest) returns (GetBalanceResponse);
  rpc Transfer(TransferRequest) returns (TransferResponse);
}

message PlaceHoldRequest {
  string user_id = 1;
  double amount = 2;
  string currency = 3;
  string idempotency_key = 4;
  string description = 5;
}

message PlaceHoldResponse {
  string hold_id = 1;
  string status = 2;  // HELD / INSUFFICIENT_FUNDS / ERROR
  double balance_after = 3;
}
```

## Future Payment Methods

| Method | Priority | Timeline |
|---|---|---|
| Cash on delivery (physical goods) | Medium | v1.3 |
| Credit/debit card (via payment gateway) | Low | v2.0 |
| Vendor wallet-to-wallet | High (v1.0) | Launch |
| Beza Pay (QR scan at partner stores) | Low | v2.0 |

## Transaction Idempotency

Every payment operation uses an idempotency key derived from:
- Top-up: SHA256(user_id + phone_number + amount + timestamp_bucket)
- Order: SHA256(cart_id + user_id + version)
- Gift card: UUID v4 (generated per purchase)

Idempotency keys stored in Redis with 24h TTL.
