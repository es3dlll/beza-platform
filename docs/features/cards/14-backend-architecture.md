# Cards Backend Architecture

## Module Structure (Laravel)
```
app/Modules/Cards/
├── Controllers/
│   ├── CardController.php           # CRUD + freeze/unfreeze
│   ├── CardTransactionController.php # Transaction history
│   ├── CardPinController.php         # PIN management
│   ├── CardLimitController.php       # Spending limits
│   └── CardTokenController.php       # Apple Pay / Google Pay tokens
│
├── Actions/
│   ├── CreateCardAction.php          # Virtual/physical card issuance
│   ├── FreezeCardAction.php          # Card freeze
│   ├── UnfreezeCardAction.php        # Card unfreeze
│   ├── CloseCardAction.php           # Card closure
│   ├── ReplaceCardAction.php         # Card replacement (same BIN/last4)
│   ├── ReportLostCardAction.php      # Lost card reporting
│   ├── ChangePinAction.php           # PIN change with HSM
│   ├── UpdateLimitsAction.php        # Per-category limit update
│   ├── CreateOneTimeCardAction.php   # Single-use virtual card
│   └── AuthorizeTransactionAction.php # Online auth handling
│
├── Services/
│   ├── CardService.php               # Core card lifecycle operations
│   ├── CardProcessor.php             # Transaction authorization, clearing, settlement
│   ├── CardBINService.php            # BIN management, routing, range allocation
│   ├── CardLimitService.php          # Per-card/per-user limits by category
│   ├── TokenizationService.php       # Card-on-file token management
│   ├── CardFraudService.php          # Real-time fraud scoring
│   └── CardNotificationService.php   # Transaction push/SMS
│
├── Integrations/
│   ├── LocalSwitchClient.php         # Local card scheme (Syria) ISO 8583
│   ├── InternationalBINClient.php    # BIN sponsorship partner connection
│   ├── HsmClient.php                 # HSM for PIN, CVV, key management
│   ├── TokenServiceProviderClient.php # Apple Pay / Google Pay TSP
│   └── CardPersonalizationClient.php # Physical card printing bureau
│
├── Repositories/
│   ├── CardRepository.php            # Card CRUD
│   ├── CardTransactionRepository.php # Transaction query + pagination
│   ├── CardPinRepository.php         # PIN hash storage
│   └── CardTokenRepository.php       # Token records
│
├── Models/
│   ├── Card.php                      # Card model
│   ├── CardTransaction.php           # Transaction model
│   ├── CardPin.php                   # PIN hashes
│   ├── CardToken.php                 # Digital wallet tokens
│   └── CardBIN.php                   # BIN registry
│
├── Policies/
│   ├── CardPolicy.php                # Card ownership + authorization
│   └── CardTransactionPolicy.php     # Transaction visibility
│
├── Events/
│   ├── CardCreated.php               # Card issued event
│   ├── CardFrozen.php                # Card frozen event
│   ├── CardUnfrozen.php             # Card unfrozen event
│   ├── CardClosed.php                # Card closed event
│   ├── CardTransactionAuthorized.php # Auth event
│   ├── CardTransactionSettled.php    # Settlement event
│   └── CardTransactionDeclined.php   # Decline event
│
├── Jobs/
│   ├── ProcessSettlementBatch.php    # Daily clearing/settlement
│   ├── NotifyCardTransaction.php     # Push/SMS on transaction
│   ├── ExpireOneTimeCards.php        # Cleanup expired single-use cards
│   └── SyncCardToFraudAnalytics.php
│
├── Listeners/
│   ├── SendCardTransactionNotification.php
│   ├── LogCardActivity.php
│   └── UpdateCardSpendingTotals.php
│
├── Rules/
│   ├── ValidCardLimit.php
│   ├── ValidPinFormat.php
│   └── WithinCardLimit.php
│
├── Exceptions/
│   ├── CardFrozenException.php
│   ├── CardLimitExceededException.php
│   ├── CardNotFoundException.php
│   ├── PinBlockedException.php
│   └── InsufficientCardBalanceException.php
│
└── DTOs/
    ├── CardDTO.php
    ├── AuthorizationRequestDTO.php
    ├── AuthorizationResponseDTO.php
    ├── SettlementBatchDTO.php
    └── TokenizationDTO.php
```

## Service Responsibilities

### CardService
- `create(User, CardType, Currency, limits, nickname)` — assigns BIN, generates PAN, generates CVV (delivered to HSM), sets limits, calculates fee, creates card record
- `activate(int cardId)` — activates card after issuance or first use
- `freeze(int cardId)` — sets status to frozen, rejects future auths
- `unfreeze(int cardId)` — restores to active
- `close(int cardId)` — permanent closure, settle outstanding
- `replace(int cardId)` — replaces physical card, same PAN/BIN, new CVV+expiry

### CardProcessor
- `authorize(AuthorizationRequest)` — receives ISO 8583 auth from switch, checks freeze, limits, fraud, balance → approve/decline
- `clearing(array transactions)` — batch clearing file processing from switch
- `settlement(SettlementBatch)` — net settlement posting to CFE, fee calculation

### CardBINService
- `assignBIN(CardType, Currency)` — selects next available PAN from BIN range
- `routeTransaction(pan)` — determines local switch vs international routing
- `getBINDetails(string bin)` — BIN metadata: network, issuer, card program, limits

### CardLimitService
- `getEffectiveLimits(Card, User)` — returns merged card limits + user KYC caps
- `checkAuthorization(AuthorizationRequest)` — validates against all category limits
- `updateDailyCounters(AuthorizationRequest)` — increments daily POS/Online/ATM counters

### TokenizationService
- `createToken(Card, Device, WalletType)` — creates card-on-file token for Apple Pay/Google Pay
- `revokeToken(string token)` — removes digital wallet token
- `getTokenStatus(string token)` — checks token validity
