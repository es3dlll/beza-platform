# Flutter Tasks — Wallet UI Implementation

## Sprint 1: Foundation (Week 1-2)
| Task ID | Description | Est. Hours | Dependencies |
|---------|-------------|------------|-------------|
| FL-WAL-001 | Create wallet feature directory structure | 2 | None |
| FL-WAL-002 | Create BalanceState class (balances, hidden, lastUpdated) | 2 | None |
| FL-WAL-003 | Create TransferFormState class (phone, amount, fee, total, note, isValid) | 3 | None |
| FL-WAL-004 | Create Transaction model (id, type, status, amount, counterparty, etc.) | 2 | None |
| FL-WAL-005 | Create WalletRemoteDataSource (Dio API calls) | 4 | None |
| FL-WAL-006 | Create WalletLocalDataSource (SQLite) | 4 | FL-WAL-004 |
| FL-WAL-007 | Create WalletRepository implementation | 3 | FL-WAL-005, FL-WAL-006 |
| FL-WAL-008 | Create BalanceProvider (Riverpod) | 3 | FL-WAL-002, FL-WAL-007 |
| FL-WAL-009 | Create TransferFormProvider | 4 | FL-WAL-003, FL-WAL-007 |
| FL-WAL-010 | Create TransactionListProvider | 3 | FL-WAL-004, FL-WAL-007 |

## Sprint 2: Core Screens (Week 3-4)
| Task ID | Description | Est. Hours | Dependencies |
|---------|-------------|------------|-------------|
| FL-WAL-011 | Create BalanceCardWidget (balance display, eye toggle, quick actions) | 4 | FL-WAL-008 |
| FL-WAL-012 | Create WalletHomeScreen (scrollable, RefreshIndicator, slivers) | 6 | FL-WAL-011, FL-WAL-010 |
| FL-WAL-013 | Create AmountInputWidget (large font, currency prefix, formatting) | 3 | None |
| FL-WAL-014 | Create SendMoneyScreen (form with validation) | 6 | FL-WAL-009, FL-WAL-013 |
| FL-WAL-015 | Create ConfirmTransferSheet (bottom sheet, summary, PIN input) | 4 | FL-WAL-009 |
| FL-WAL-016 | Create TransferResultScreen (success/failure animations) | 3 | None |
| FL-WAL-017 | Create TransactionItemTile (icon, label, amount, status, timestamp) | 3 | FL-WAL-004 |
| FL-WAL-018 | Create TransactionHistoryScreen (filter tabs, search, pagination) | 5 | FL-WAL-010, FL-WAL-017 |
| FL-WAL-019 | Create TransactionDetailScreen (status hero, breakdown, actions) | 4 | FL-WAL-004 |
| FL-WAL-020 | Create FeeBreakdownCard widget | 2 | FL-WAL-009 |

## Sprint 3: Advanced UI (Week 5-6)
| Task ID | Description | Est. Hours | Dependencies |
|---------|-------------|------------|-------------|
| FL-WAL-021 | Create QuickActionsGrid widget (4-column grid) | 3 | None |
| FL-WAL-022 | Create FXTickerWidget | 2 | FL-WAL-008 |
| FL-WAL-023 | Create SavingsGoalCard widget | 3 | None |
| FL-WAL-024 | Create Skeleton loading for BalanceCard and TransactionList | 3 | None |
| FL-WAL-025 | Create EmptyStateWidget for transactions, contacts, cards | 2 | None |
| FL-WAL-026 | Create ErrorStateWidget with retry button | 2 | None |
| FL-WAL-027 | Create offline banner widget | 2 | None |
| FL-WAL-028 | Implement GoRouter wallet routes | 4 | FL-WAL-012, FL-WAL-014, FL-WAL-018, FL-WAL-019 |
| FL-WAL-029 | Implement deep links (beza-app://send, beza-app://transaction/:id) | 3 | FL-WAL-028 |

## Sprint 4: Polish & Testing (Week 7-8)
| Task ID | Description | Est. Hours | Dependencies |
|---------|-------------|------------|-------------|
| FL-WAL-030 | Implement pull-to-refresh with pull-down animation | 3 | FL-WAL-012 |
| FL-WAL-031 | Add Lottie animations (confetti on success, cash flying on send) | 3 | None |
| FL-WAL-032 | Implement biometric integration (Face ID / Fingerprint for send confirmation) | 4 | FL-WAL-015 |
| FL-WAL-033 | Build offline sync service (SQLite queue, connectivity listener) | 5 | FL-WAL-006 |
| FL-WAL-034 | Implement optimistic updates for transfers | 4 | FL-WAL-009, FL-WAL-033 |
| FL-WAL-035 | Widget tests: BalanceCard, AmountInput, TransactionTile | 4 | FL-WAL-011, FL-WAL-013, FL-WAL-017 |
| FL-WAL-036 | Widget tests: SendMoneyScreen, TransactionHistoryScreen | 6 | FL-WAL-014, FL-WAL-018 |
| FL-WAL-037 | Integration tests: transfer flow end-to-end | 6 | FL-WAL-028 |
| FL-WAL-038 | Performance optimization: list virtualization, image caching | 4 | All |
| FL-WAL-039 | Accessibility audit: screen reader support, contrast, touch targets | 3 | All |
| FL-WAL-040 | RTL verification: all screens in Arabic layout | 3 | All |
