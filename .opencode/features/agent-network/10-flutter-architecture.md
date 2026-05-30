# Agent Network Flutter Architecture

## Architecture Pattern
```
Feature-first modular architecture with Clean Architecture layers, optimized for offline-first POS usage:

lib/
├── core/                              # Shared across all features
│   ├── api/                           # Dio client, retry interceptor, offline queue
│   ├── auth/                          # Auth state, token management, agent PIN
│   ├── bluetooth/                     # Thermal printer service (ESC/POS)
│   ├── design/                        # BezaTheme, agent-specific design tokens
│   ├── errors/                        # Failure types, POS-specific errors
│   ├── network/                       # Connectivity checker, offline detector
│   ├── router/                        # GoRouter configuration
│   ├── storage/                       # SQLite + secure storage (encrypted)
│   ├── sync/                          # Offline sync engine (FIFO queue)
│   └── utils/                         # Amount formatter, date utils, keypad utils
│
├── features/
│   └── agent_network/
│       ├── data/
│       │   ├── datasources/
│       │   │   ├── agent_remote_datasource.dart   # API calls
│       │   │   └── agent_local_datasource.dart    # SQLite CRUD
│       │   ├── models/
│       │   │   ├── agent_model.dart
│       │   │   ├── agent_transaction_model.dart
│       │   │   ├── agent_float_model.dart
│       │   │   ├── agent_commission_model.dart
│       │   │   └── offline_queue_item_model.dart
│       │   └── repositories/
│       │       └── agent_repository_impl.dart
│       ├── domain/
│       │   ├── entities/
│       │   │   ├── agent.dart
│       │   │   ├── agent_transaction.dart
│       │   │   ├── agent_float.dart
│       │   │   ├── agent_commission.dart
│       │   │   └── customer.dart
│       │   ├── repositories/
│       │   │   └── agent_repository.dart          # Abstract interface
│       │   └── usecases/
│       │       ├── cash_in_usecase.dart
│       │       ├── cash_out_usecase.dart
│       │       ├── get_float_usecase.dart
│       │       ├── top_up_float_usecase.dart
│       │       ├── get_transactions_usecase.dart
│       │       ├── get_commission_usecase.dart
│       │       └── sync_transactions_usecase.dart
│       └── presentation/
│           ├── providers/
│           │   ├── agent_auth_provider.dart
│           │   ├── agent_float_provider.dart
│           │   ├── agent_transaction_provider.dart
│           │   ├── agent_cash_in_provider.dart
│           │   ├── agent_cash_out_provider.dart
│           │   ├── agent_commission_provider.dart
│           │   ├── agent_sync_provider.dart
│           │   └── agent_printer_provider.dart
│           ├── screens/
│           │   ├── agent_pos_home_screen.dart
│           │   ├── cash_in_screen.dart
│           │   ├── cash_out_screen.dart
│           │   ├── float_management_screen.dart
│           │   ├── transaction_history_screen.dart
│           │   ├── commission_screen.dart
│           │   └── agent_profile_screen.dart
│           ├── widgets/
│           │   ├── float_display_card.dart
│           │   ├── action_button.dart (Cash-in / Cash-out)
│           │   ├── numeric_keypad.dart
│           │   ├── phone_input_field.dart
│           │   ├── verification_code_input.dart
│           │   ├── transaction_tile.dart
│           │   ├── receipt_preview_widget.dart
│           │   ├── step_indicator.dart
│           │   ├── alert_banner.dart
│           │   └── offline_queue_badge.dart
│           └── state/
│               ├── cash_in_state.dart
│               ├── cash_out_state.dart
│               └── float_state.dart
│
├── app.dart                           # MaterialApp.router setup
└── main.dart                          # Entry point, providers, SQLite init
```

## Offline-First Strategy
```
Offline Sync Architecture:
  ┌─────────────────────────────────────────────┐
  │             Agent POS App                    │
  │  ┌───────────────────────────────────────┐   │
  │  │         UI Layer (Flutter)            │   │
  │  └──────────────┬────────────────────────┘   │
  │                 │                            │
  │  ┌──────────────▼────────────────────────┐   │
  │  │      Repository Layer                  │   │
  │  │  Writes: Local first → try Remote     │   │
  │  │  Reads: Local cache → background sync │   │
  │  └──────────────┬────────────────────────┘   │
  │                 │                            │
  │  ┌──────────────▼────────────────────────┐   │
  │  │    Offline Queue (SQLite)              │   │
  │  │  Pending transactions → FIFO queue    │   │
  │  │  Max 50 items → alert when full       │   │
  │  │  Retry 3x → move to failed queue      │   │
  │  └──────────────┬────────────────────────┘   │
  │                 │                            │
  │  ┌──────────────▼────────────────────────┐   │
  │  │    Sync Engine                         │   │
  │  │  Triggers: foreground, connectivity,  │   │
  │  │  pull-to-refresh, periodic (5 min)    │   │
  │  │  Strategy: FIFO with idempotency keys  │   │
  │  └───────────────────────────────────────┘   │
  └─────────────────────────────────────────────┘

Cached Data (SQLite tables):
  - agent_profile (single row, encrypted)
  - agent_float_history (last 1000 entries)
  - agent_transactions (last 2000, full text search)
  - offline_queue (pending + failed)
  - customer_verification_cache (recent customers, 100)

Encrypted Storage (flutter_secure_storage):
  - Agent PIN hash (bcrypt)
  - Auth token
  - Device certificate (X.509)
```

## Bluetooth Printer Integration
```
Thermal Printer Service:
  Prerequisites:
    - Android device with Bluetooth
    - 58mm thermal printer (e.g., Bixolon SRP-275, Epson TM-T20)
    - Paired via Android Bluetooth settings

  Flow:
    1. App checks Bluetooth is enabled
    2. Auto-connect to last used printer (MAC stored in secure storage)
    3. If not found: show list of paired printers
    4. Generate ESC/POS commands for receipt
    5. Send via Bluetooth SPP socket
    6. On success: show "تمت الطباعة"
    7. On failure: "فشلت الطباعة — حاول مرة أخرى" + retry

  Receipt Format (ESC/POS):
    - Initialize printer
    - Set Arabic charset (CP-1256 or UTF-8 with proper encoding)
    - Print header: "Beza" logo (ASCII art or bitmap)
    - Print transaction details (Arabic right-aligned)
    - Print QR code with transaction data
    - Cut paper (GS V m)
```

## Biometric Verification
```
High-Value Transaction (>500K SYP cash-out):
  1. App checks device biometric capability
  2. If available: show fingerprint prompt
  3. Customer places finger on POS device sensor
  4. On match: proceed with transaction
  5. On fail: fallback to customer PIN (3 attempts)
  6. If both fail: transaction cancelled, "فشل التحقق — حاول مرة أخرى لاحقاً"
  7. For Platinum agents (>2M SYP cash-out): require biometric + PIN
```
