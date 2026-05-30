# Beza Platform — Knowledge Base

```
.opencode/
├── product/             Vision, roadmap, prioritization
├── architecture/        Cross-cutting tech architecture
├── engineering/         Standards, matrices, processes
├── features/            18 feature specs (alphabetical)
├── financial-core/      CFE, reconciliation, treasury, accounting
├── journeys/            9 user journeys
├── shared/              Cross-cutting concerns
│   ├── compliance/      AML, KYC, Sharia
│   ├── data-governance/ Classification, retention, ownership
│   ├── design-system/   Brand, components, motion
│   ├── notifications/   Push, SMS, email
│   ├── observability/   Logging, metrics, alerting, KPIs
│   ├── security/        Auth, authorization, encryption
│   └── testing/         Patterns, data factories
├── workflows/           4-phase AI workflow loop + agent configs
├── operations/          Runbooks (incident response)
├── tasks/               Task tracking by domain
└── plans/               Session .plan files
```

## Pipeline

A 4-phase automation script runs tasks from `tasks/` through the full lifecycle.

```bash
npm run pipeline          # Full loop over all tasks
npm run pipeline:phase1   # Planning only
npm run pipeline:phase2   # Execution only
```

## Quick Nav

- **Vision**: `product/vision-2026.md`
- **Build Order**: `engineering/build-order.md`
- **API Matrix**: `engineering/api-matrix.md`
- **Architecture**: `architecture/`
- **Features**: `features/<name>/`
