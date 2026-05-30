# Beza Financial OS — Syria's Financial Super App

[![CI](https://github.com/es3dlll/beza-platform/actions/workflows/ci.yml/badge.svg)](https://github.com/es3dlll/beza-platform/actions/workflows/ci.yml)

**Beza (بزة)** is a full-stack financial operating system purpose-built for Syria: a Laravel modular monolith backend + Flutter mobile app, designed to serve the 6M Syrian diaspora and bring financial inclusion to a <30% banked population.

---

## Architecture

```
beza-platform/
├── app/                      # Laravel backend
│   ├── Domain/ValueObjects/  # Money, Currency, Rate, Percentage
│   ├── Modules/              # 24 self-contained modules
│   │   ├── Auth/             # Authentication & session
│   │   ├── IAM/              # Identity & Access Management
│   │   ├── Identity/         # KYC / identity verification
│   │   ├── Ledger/           # Double-entry accounting (WORM journal)
│   │   ├── CoreFinancialEngine/  # Posting, Fee, Hold, Reversal, Settlement
│   │   ├── Notification/     # In-app + push notifications
│   │   ├── Wallet/           # Digital wallet management
│   │   ├── Transfer/         # P2P & bank transfers
│   │   ├── Bills/            # Bill payment (Syriatel, MTN, etc.)
│   │   ├── Cards/            # Virtual/physical card management
│   │   ├── Agent/            # Cash agent (cash-in/cash-out) network
│   │   ├── Financing/        # Micro-loans & BNPL
│   │   ├── Education/        # Tuition fee payments
│   │   ├── Humanitarian/     # Aid distribution tracking (BSO, SIIB)
│   │   ├── Loyalty/          # Loyalty points & rewards
│   │   ├── Merchant/         # Merchant payment (QR code)
│   │   ├── FX/               # Currency exchange (SYP/USD)
│   │   ├── Remittance/       # International remittance
│   │   └── Payroll/          # Bulk payroll disbursement
│   └── Providers/            # Auto-discovery service provider
├── mobile/                   # Flutter app
│   └── lib/
│       ├── core/             # Theme, API client, config, routing
│       ├── features/         # Feature modules mirroring backend
│       └── app/              # App shell, router
├── database/
│   ├── migrations/           # 100+ migration files
│   └── seeders/              # Demo data seeders
├── tests/                    # 284 tests (549 assertions)
├── docs/                     # Architecture & ADR documentation
└── .github/workflows/        # CI pipeline
```

### Backend: Laravel Modular Monolith

- **Laravel 11.54** on PHP 8.5.6
- **24 modules** with unified structure (Controllers, Services, DTOs, Events, Listeners, Repositories, Tests)
- **Domain-Driven**: `Money` value object (bigint minor units, no float), `Currency`, `Rate`, `Percentage`
- **Core Financial Engine**: 5 engines — Posting, Fee, Hold, Reversal, Settlement
- **Ledger**: Append-only journal (WORM), double-entry with holds, trial balance
- **Events**: 81 events wired with Arabic notification titles/bodies
- **Zero Trust**: RBAC + ABAC + JWT rotation + device binding
- **Cross-module**: Communication via Events only
- **ULID** for all primary keys
- **SQLite** (dev) / **MySQL 8** (prod), **Redis** (cache/queue/session)

### Mobile: Flutter Super App

- **Flutter 3.41.9** on Dart SDK ^3.7.0
- **Material 3** with Syria-inspired palette (green `#1B5E20`, gold `#D4A843`, warm sand `#F8F9FA`)
- **RTL-first** — Arabic-first UI with bundled Cairo font (no Google Fonts dependency)
- **68 Dart files**, 0 analyzer issues
- **Architecture**: Riverpod (`StateNotifierProvider`) + GoRouter + Dio + `flutter_secure_storage`
- **24 screens**: Splash → Auth flow (6 screens) → ShellRoute (bottom nav) + 15 service modules + savings
- **Push notifications**: Firebase Cloud Messaging + local notifications
- **62 passing tests**

---

## Quick Start

### Prerequisites

| Tool | Version | Purpose |
|------|---------|---------|
| PHP | 8.5+ | Backend runtime |
| Composer | 2.x | PHP dependency manager |
| Flutter | 3.41+ | Mobile app framework |
| Dart | ^3.7.0 | Dart SDK |
| MySQL | 8.0+ | Production database |
| Redis | 7+ | Cache/queue/session |

### Backend Setup

```bash
git clone https://github.com/es3dlll/beza-platform
cd beza-platform
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
```

Health check: `curl http://127.0.0.1:8000/api/v1/health`

### Mobile Setup

```bash
cd mobile
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000
```

| Device | API URL |
|--------|---------|
| Android emulator | `http://10.0.2.2:8000` |
| iOS simulator | `http://localhost:8000` |
| Real device | `http://192.168.x.x:8000` |

### OTP in Development

```bash
./get_otp.bat 963xxxxxxxxx
```

OTP is stored in cache key `otp_plain_{otp_id}` for 5 minutes.

---

## Mobile Architecture

### Theme & Design

| Token | Value | Usage |
|-------|-------|-------|
| `AppTheme.primary` | `#1B5E20` | Primary actions, buttons |
| `AppTheme.primaryLight` | `#4CAF50` | Accents, badges |
| `AppTheme.gold` | `#D4A843` | Premium highlights |
| `AppTheme.warning` | `#FF9800` | Pending/warning states |
| `AppTheme.error` | `#D32F2F` | Errors, failed states |
| `AppTheme.success` | `#388E3C` | Success states |
| `AppTheme.info` | `#1976D2` | Informational |

Design helpers (see `app_theme.dart`):
- `cardDecoration` — Premium card shadow + border radius
- `cardGradient` — Gradient background for balance/hero cards
- `sectionHeader` — Green side bar + gold-accented text
- `iconContainer` / `gradientIconContainer` — Styled icon wrappers
- `shimmer` — Loading animation base color

### Routing

| Route | Screen | Auth |
|-------|--------|------|
| `/` | Splash | — |
| `/welcome` | Welcome | — |
| `/phone-entry` | Phone input | — |
| `/otp-verify` | OTP verification | — |
| `/create-pin` | PIN creation | — |
| `/confirm-pin` | PIN confirmation | — |
| `/home` | Shell (bottom nav) | Required |
| `/wallet` | Wallet | Required |
| `/bills` | Bill payment | Required |
| `/cards` | Card management | Required |
| `/agent` | Agent network | Required |
| `/financing` | Micro-loans | Required |
| `/education` | Tuition | Required |
| `/humanitarian` | Aid distribution | Required |
| `/loyalty` | Rewards | Required |
| `/merchant` | Merchant payments | Required |
| `/fx` | Currency exchange | Required |
| `/remittance` | Remittance | Required |
| `/payroll` | Payroll | Required |
| `/savings` | Savings goals | Required |
| `/transactions` | Transaction history | Required |
| `/notifications` | Notifications | Required |
| `/profile` | Profile & settings | Required |

### State Management

```
AuthProvider (StateNotifierProvider<AuthNotifier, AuthState>)
├── AuthState.initial
├── AuthState.loading
├── AuthState.phoneRegistered / otpSent / pinCreated / authenticated
└── AuthState.error

FcmProvider (StateNotifierProvider<FcmNotifier, FcmState>)
├── FcmState.initial
├── FcmState.ready (token, permissionStatus)
└── FcmState.error
```

### Auth Flow

1. **Splash** → auto-login check (token in secure storage)
2. **Welcome** → feature showcase
3. **Phone Entry** → `POST /api/v1/auth/register` (phone only)
4. **OTP Verify** → `POST /api/v1/auth/verify-otp`
5. **Create PIN** → `POST /api/v1/auth/create-pin`
6. **Confirm PIN** → match → `_autoLogin()` → `POST /api/v1/auth/login`

---

## API Reference

All endpoints under `api/v1/{module}`. Full Postman collection (185 requests, 25 folders) available at `docs/postman/beza-platform.postman_collection.json`.

**Authentication:**
- `POST /auth/register` — Submit phone number
- `POST /auth/verify-otp` — Verify OTP code
- `POST /auth/create-pin` — Create PIN
- `POST /auth/login` — Login (returns JWT)
- `POST /auth/logout` — Invalidate session
- `POST /auth/refresh` — Refresh token

**Core:**
- `GET /health` — Health check
- `GET /wallet/balance` — Get balance
- `GET /transactions` — List transactions
- `POST /notifications/fcm-token` — Register FCM token

---

## Testing

### Backend (284 tests, 549 assertions)

```bash
php artisan test
# or
php vendor/bin/phpunit
```

### Mobile (Flutter — 62 tests)

```bash
cd mobile
flutter test
```

---

## Firebase Setup (Push Notifications)

1. Create a Firebase project at [console.firebase.google.com](https://console.firebase.google.com)
2. Register Android app with package name `com.beza.platform`
3. Download `google-services.json` → `mobile/android/app/` (replaces the placeholder)
4. Register iOS app → download `GoogleService-Info.plist` → `mobile/ios/Runner/`
5. Enable **Cloud Messaging** API in Firebase Console

> A minimal `google-services.json` placeholder is already in place for development builds. Replace it with your real Firebase project file before release.

---

## OTP & Development Notes

- OTP stored in cache key `otp_plain_{otp_id}` for 5 minutes
- Retrieve via `./get_otp.bat 963xxxxxxxxx`
- PIN creation is best-effort; `_autoLogin()` fallback ensures user can proceed
- All listener exceptions are caught (`try/catch \Throwable`) to prevent breaking main request flow

---

## License & Contact

- **Repo**: [https://github.com/es3dlll/beza-platform](https://github.com/es3dlll/beza-platform)
- **Issues**: [GitHub Issues](https://github.com/es3dlll/beza-platform/issues)
