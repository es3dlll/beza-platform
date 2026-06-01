# Backend Tasks — Wallet Module Implementation

## Sprint 1: Foundation (Week 1-2)
| Task ID | Description | Est. Hours | Dependencies |
|---------|-------------|------------|-------------|
| BE-WAL-001 | Create Wallet module directory structure | 2 | None |
| BE-WAL-002 | Create migrations: wallets, wallet_transactions, wallet_balance_history, wallet_daily_limits, transfer_requests | 4 | None |
| BE-WAL-003 | Create enums: TransactionType, TransactionStatus, WalletCurrency, WalletType, WalletStatus, FundingSource | 2 | None |
| BE-WAL-004 | Create models: Wallet, WalletTransaction, WalletBalance with relationships and scopes | 6 | BE-WAL-002 |
| BE-WAL-005 | Create WalletRepository with caching | 4 | BE-WAL-004 |
| BE-WAL-006 | Create WalletService (getOrCreate, getBalance, getBalances) | 4 | BE-WAL-005 |
| BE-WAL-007 | Create FeeService (calculateTransferFee, premium fee logic) | 3 | BE-WAL-003 |
| BE-WAL-008 | Create LimitService (KYC-based limits, daily/monthly tracking) | 3 | BE-WAL-002 |
| BE-WAL-009 | Seed wallet_daily_limits for KYC levels 0, 1, 2 | 1 | BE-WAL-002 |

## Sprint 2: Transfer Logic (Week 3-4)
| Task ID | Description | Est. Hours | Dependencies |
|---------|-------------|------------|-------------|
| BE-WAL-010 | Create CFE service integration (hold/post/release) | 8 | BE-WAL-006 |
| BE-WAL-011 | Create TransferService (send money orchestration) | 8 | BE-WAL-007, BE-WAL-008, BE-WAL-010 |
| BE-WAL-012 | Create WalletController (balance, history) | 3 | BE-WAL-006 |
| BE-WAL-013 | Create TransferController (send, request, respond) | 4 | BE-WAL-011 |
| BE-WAL-014 | Create RequestMoneyAction (request, accept, decline) | 4 | BE-WAL-011 |
| BE-WAL-015 | Create API routes for wallet module | 2 | BE-WAL-012, BE-WAL-013 |
| BE-WAL-016 | Create validation rules (SufficientBalance, ValidTransferAmount, WithinDailyLimit) | 3 | BE-WAL-008 |
| BE-WAL-017 | Create exceptions (InsufficientBalanceException, DailyLimitExceededException, etc.) | 2 | None |
| BE-WAL-018 | Create WalletPolicy (authorization rules for wallet operations) | 3 | BE-WAL-004 |

## Sprint 3: Events & Notifications (Week 5)
| Task ID | Description | Est. Hours | Dependencies |
|---------|-------------|------------|-------------|
| BE-WAL-019 | Create events: WalletCredited, WalletDebited, TransferSent, TransferReceived, TransferFailed | 3 | BE-WAL-011 |
| BE-WAL-020 | Create listeners: SendTransferNotification, LogWalletActivity, UpdateSavingsRoundup | 4 | BE-WAL-019 |
| BE-WAL-021 | Create RabbitMQ producer for wallet events | 3 | BE-WAL-019 |
| BE-WAL-022 | Create notification consumers (push + SMS) | 4 | BE-WAL-021 |
| BE-WAL-023 | Create SendMoneyAction (complete orchestration class) | 4 | BE-WAL-011, BE-WAL-019 |

## Sprint 4: Admin & Testing (Week 6-8)
| Task ID | Description | Est. Hours | Dependencies |
|---------|-------------|------------|-------------|
| BE-WAL-024 | Create AdminWalletController (admin panel endpoints) | 4 | BE-WAL-015 |
| BE-WAL-025 | Create balance reconciliation command | 4 | BE-WAL-010 |
| BE-WAL-026 | Implement idempotency middleware | 3 | None |
| BE-WAL-027 | Unit tests: TransferService | 6 | BE-WAL-011 |
| BE-WAL-028 | Unit tests: FeeService, LimitService | 4 | BE-WAL-007, BE-WAL-008 |
| BE-WAL-029 | Integration tests: Wallet API endpoints | 6 | BE-WAL-015 |
| BE-WAL-030 | Integration tests: CFE integration | 4 | BE-WAL-010 |
| BE-WAL-031 | Performance tests: transfer endpoint (100 req/s) | 4 | BE-WAL-013 |
| BE-WAL-032 | Documentation: API docs, architecture notes | 3 | All |
