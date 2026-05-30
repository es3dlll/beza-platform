# Git Strategy — Beza Platform

## Repository Structure

Single monorepo containing all Beza Platform code:

```
beza-platform/
├── app/                    # Laravel backend (PHP)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── tests/
│   ├── artisan
│   ├── composer.json
│   └── phpunit.xml
├── mobile/                 # Flutter mobile app (Dart)
│   ├── lib/
│   ├── test/
│   ├── pubspec.yaml
│   ├── analysis_options.yaml
│   └── l10n/
├── admin/                  # React admin panel (TypeScript)
│   ├── src/
│   ├── public/
│   ├── package.json
│   ├── tsconfig.json
│   ├── vite.config.ts
│   └── tailwind.config.ts
├── docs/                   # Documentation
│   ├── engineering/
│   ├── architecture/
│   ├── api/
│   └── compliance/
├── infra/                  # Infrastructure
│   ├── docker/
│   │   ├── php/
│   │   ├── nginx/
│   │   └── docker-compose.yml
│   ├── kubernetes/
│   ├── terraform/
│   ├── .github/
│   │   ├── workflows/
│   │   │   ├── backend-ci.yml
│   │   │   ├── mobile-ci.yml
│   │   │   ├── admin-ci.yml
│   │   │   └── deploy.yml
│   │   └── CODEOWNERS
│   └── scripts/
└── .gitignore
```

## Commit Message Convention

### Format
```
type(scope): brief description in imperative present tense

Optional body with additional context, justification, or migration notes.
```

### Types
| Type | When to Use |
|------|-------------|
| feat | New feature or significant enhancement |
| fix | Bug fix |
| docs | Documentation changes only |
| style | Code formatting, lint fixes, no logic change |
| refactor | Code restructuring without behavior change |
| test | Adding or modifying tests |
| chore | Build config, CI, dependencies, tooling |
| security | Security vulnerability fix |
| perf | Performance improvement |
| revert | Reverting a previous commit |

### Scopes
| Scope | Module |
|-------|--------|
| wallet | Wallet management, transfers, balance |
| agent | Agent operations, commissions, cash-in/out |
| auth | Authentication, OTP, session management |
| fx | Foreign exchange rates, conversions |
| ledger | Accounting, journal entries, GL |
| fraud | Fraud detection, rules engine, monitoring |
| kyc | KYC verification, document upload |
| compliance | Regulatory reporting, AML checks |
| notification | SMS, push, in-app notifications |
| admin | React admin panel |
| mobile | Flutter mobile app |
| infra | Docker, CI/CD, deployment, terraform |
| docs | Documentation updates |
| api | API contracts, versioning |
| security | Auth, encryption, audit |

### Examples

```
feat(wallet): add P2P transfer by phone number

Implement wallet-to-wallet transfer using receiver's phone number.
- Add TransferRequest DTO with sender/receiver/amount/currency
- Add TransferService with balance check, debit/credit, event dispatch
- Add TransferController with Form Request validation
- Add FeatureTest covering success, insufficient balance, invalid phone
- Add TransferCompleted event for Ledger module consumption

BREAKING: TransferRequestDto signature changed (removed receiver_wallet_id)
```

```
fix(agent): correct cash-out commission calculation

Commission was calculated on gross amount instead of net.
Fix: calculate 2% commission on amount after fees, not before.

Closes FX-412
```

```
security(auth): fix JWT token validation

Tokens with expired 'nbf' claim were incorrectly accepted.
Added nbf validation with leeway of 60 seconds.

Affects: app/Modules/Auth/Services/TokenService.php:45
```

```
docs(architecture): add event versioning strategy

Document how events are versioned, deprecated, and migrated.
Includes schema evolution rules and backward compatibility guidelines.
```

```
test(ledger): add integration test for CFE posting flow

Tests end-to-end: CFE creation → journal entry → GL posting → balance update.
Covers both debit and credit scenarios with reversal.
```

```
chore(infra): add GitHub Actions workflow for mobile CI

Flutter analyze, format, test, and build on PR to develop.
APK artifact uploaded for manual testing.
```

## What NOT to Commit

The following are BLOCKED by pre-commit hooks and will cause CI to fail:

- Debug functions: `dd()`, `var_dump()`, `print_r()`, `ray()`, `dump()`
- `.env` files with real credentials
- Large binary files (>5MB) unless explicitly approved
- `node_modules/`, `vendor/`, `.dart_tool/` directories
- IDE configuration files (`.idea/`, `.vscode/`, `*.swp`)
- Log files, cache files, build artifacts
- Accidentally committed secrets (keys, passwords, tokens)
- `composer.lock` changes unrelated to the PR scope
- Merge conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`)

## Monorepo Specifics

### Partial Checkout
Developers may use sparse checkout for working on specific modules:
```bash
git sparse-checkout set app mobile/docs
```

### Dependency Management
- Backend dependencies in `app/composer.json` only.
- Admin dependencies in `admin/package.json` only.
- Mobile dependencies in `mobile/pubspec.yaml` only.
- Root-level `package.json` (if any) for shared tooling only.

### Tagging Strategy
```
v{version}                      # Production release, e.g., v1.2.3
v{version}-rc.{n}               # Release candidate, e.g., v1.2.3-rc.1
v{version}-alpha.{n}            # Alpha release
```

## Syria Context Considerations
- Timestamps in commit messages reference Syrian time (AST, UTC+3).
- Friday commits are discouraged (day of rest). Emergency hotfixes exempted.
- Arabic characters allowed in commit bodies for documentation context.
- All commit messages must be in English (machine-readable, CI-parsed).
