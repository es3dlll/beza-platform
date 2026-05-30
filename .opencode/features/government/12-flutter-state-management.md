# Government Collections Flutter State Management

## Provider Architecture (Riverpod)

```
Provider Hierarchy:
  ┌─────────────────────────────────────────────────────────────┐
  │              GovernmentRepositoryProvider                    │
  │  (Dio client + remote/local data sources + receipt gen)      │
  └─────────────────────┬───────────────────────────────────────┘
                        │
  ┌─────────────────────▼───────────────────────────────────────┐
  │              GovernmentHubProvider                           │
  │  State: AsyncValue<GovernmentHubState>                      │
  │  Fields: services, recentPayments, reminders, savedPayers   │
  │  Methods:                                                   │
  │    - Future<void> refresh()                                  │
  │    - Future<List<GovernmentService>> searchServices(String)  │
  │    - void setFavouriteService(String serviceId)              │
  └──────┬──────────┬──────────┬──────────┬────────────────────┘
         │          │          │          │
         │          │          │          │
  ┌──────▼────┐ ┌───▼────┐ ┌──▼─────┐ ┌─▼─────────────┐
  │TaxPay     │ │FinePay │ │Passport│ │TuitionPay     │
  │Provider   │ │Provider│ │Pay     │ │Provider        │
  │(family)   │ │(family)│ │Provider│ │(family)        │
  │           │ │        │ │(family)│ │                │
  │State:     │ │State:  │ │State:  │ │State:          │
  │AsyncValue │ │AsyncV. │ │AsyncV. │ │AsyncValue      │
  │<TaxState> │ │<FineSt>│ │<PassSt>│ │<TuitState>     │
  └───────────┘ └────────┘ └────────┘ └────────────────┘
                        │
  ┌─────────────────────▼───────────────────────────────────────┐
  │              PaymentHistoryProvider                          │
  │  State: AsyncValue<PaymentHistoryState>                      │
  │  Fields: transactions (paginated), filters, search           │
  │  Methods:                                                    │
  │    - Future<void> loadPage(int page)                         │
  │    - Future<void> filterByService(String serviceId)          │
  │    - Future<void> filterByDateRange(DateTime, DateTime)      │
  │    - Future<void> search(String query)                       │
  └──────────────────────────────────────────────────────────────┘
```

## Payment Flow State Machine

Each payment provider (`TaxPaymentProvider`, `FinePaymentProvider`, etc.) follows this state machine:

```
PaymentState: initial
     │
     ▼
PaymentState: querying
  ── on success ──► PaymentState: displaying_obligations
  ── on error   ──► PaymentState: error(message, retry)
     │
     ▼
PaymentState: confirming_payment
  (user taps confirm, enters PIN)
     │
     ▼
PaymentState: processing
  ── on success ──► PaymentState: completed(receipt)
  ── on error   ──► PaymentState: error(message, retry_options)
     │
     ▼
PaymentState: completed
  (receipt displayed, share/download actions)
```

### Shared State
```dart
class GovernmentPaymentMixin {
  // Common fields across all payment providers
  final String idempotencyKey;  // UUID generated per payment attempt
  final double bezaFeePct;      // Configurable per service
  final bool isGuestPayment;    // Without full login
  final List<SavedPayer> savedPayers;
  final Receipt? lastReceipt;
}
```
