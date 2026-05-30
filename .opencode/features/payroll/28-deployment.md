# 28 — Deployment & CI/CD

---

## Infrastructure

| Component | Technology | Location |
|-----------|-----------|----------|
| Compute | Kubernetes (on-prem, 3 masters + 5 workers) | Damascus DC, Aleppo DR |
| Database | PostgreSQL 15 (primary + async standby) | Damascus (primary), Aleppo (standby) |
| Cache | Redis 7 (sentinel for HA) | Damascus |
| Object storage | MinIO (S3-compatible, 3 nodes) | Damascus |
| Message queue | RabbitMQ 3.12 | Damascus |
| Monitoring | Prometheus + Grafana + ELK | Damascus |

## CI/CD Pipeline (GitLab)

```
┌─────────┐   ┌──────────┐   ┌─────────┐   ┌──────────┐   ┌──────────┐
│  Commit  │──>│   Lint   │──>│   Test  │──>│  Build   │──>│  Deploy  │
│   Push   │   │  ruff +  │   │ pytest  │   │  Docker  │   │  (helm)  │
│          │   │ mypy +   │   │ + cov   │   │  Image   │   │          │
│          │   │ format   │   │         │   │          │   │          │
└─────────┘   └──────────┘   └─────────┘   └──────────┘   └──────────┘
     │              │              │              │              │
     ▼              ▼              ▼              ▼              ▼
  pre-commit    GitLab CI       GitLab CI      GitLab CI      ArgoCD
```

## Environments

| Environment | URL | Database | Deploy Trigger |
|-------------|-----|----------|---------------|
| `dev` | `dev-api.beza.sy` | Ephemeral (per-branch) | Push to feature branch |
| `staging` | `staging-api.beza.sy` | Shared staging | Push to `develop` branch |
| `sandbox` | `sandbox-api.beza.sy` | Shared sandbox | Manual (partners) |
| `production` | `api.beza.sy` | Production | Push to `main` + approval |

## Docker Images

```dockerfile
# Base image
FROM python:3.12-slim AS base

# Payroll API
FROM base AS payroll-api
COPY src/payroll/api /app/api
COPY src/payroll/services /app/services
COPY src/payroll/models /app/models
EXPOSE 8000
CMD ["uvicorn", "app.api.main:app", "--host", "0.0.0.0", "--port", "8000"]

# Payroll Worker (Celery)
FROM base AS payroll-worker
COPY src/payroll/services /app/services
COPY src/payroll/tasks /app/tasks
CMD ["celery", "-A", "app.tasks.worker", "worker", "--loglevel=info"]

# Payslip Generator (standalone)
FROM node:20 AS payslip-generator
COPY src/payroll/payslip /app
RUN npm install puppeteer
CMD ["node", "/app/generate.js"]
```

## Kubernetes Manifests (Helm)

```
payroll/
  charts/
    payroll-api/
      Chart.yaml
      values.yaml
      templates/
        deployment.yaml
        service.yaml
        ingress.yaml
        configmap.yaml
        secret.yaml    (encrypted with sops)
        hpa.yaml
        pdb.yaml
    payroll-worker/
      ...
```

## Deployment Procedure

### Standard Deployment (no downtime)

```bash
# 1. Merge feature branch → develop (auto-deploy to staging)
# 2. QA validates on staging
# 3. Create MR: develop → main
# 4. CI runs full test suite + security scan
# 5. ArgoCD syncs production namespace
# 6. Rolling update: maxSurge=25%, maxUnavailable=0
# 7. Post-deploy health check (5 min)
# 8. If degraded: rollback via ArgoCD
```

### Rollback Procedure

```bash
# ArgoCD rollback to previous revision
argocd app rollback payroll-api --prune

# Or manual:
kubectl rollout undo deployment/payroll-api -n production
```

## Database Migrations

| Tool | Alembic |
|------|---------|
| Strategy | Expand-contract (backward-compatible changes only) |
| Migration check | CI runs `alembic check` to detect drift |
| Rollback | `alembic downgrade -1` (tested on staging first) |
| Zero-downtime | Add columns as nullable → backfill → make NOT NULL → remove old columns |

## Feature Flags (LaunchDarkly)

| Flag | Purpose |
|------|---------|
| `payroll.t1-settlement` | Enable T+1 settlement for specific companies |
| `payroll.salary-advance` | Enable salary advance feature (internal beta) |
| `payroll.auto-payroll` | Enable recurring schedule payroll |
| `payroll.multi-currency` | USD payroll support |
