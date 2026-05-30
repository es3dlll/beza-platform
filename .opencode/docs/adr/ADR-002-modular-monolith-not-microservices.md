# ADR-002: Modular Monolith (Not Microservices)

## Status
Accepted

## Context
Beza Financial OS V1 must choose an architecture style that balances delivery speed, operational simplicity, and future scalability. Three options were considered:

1. **Laravel Modular Monolith** — single Laravel application with module boundaries enforced via directory structure, service interfaces, and event-driven module communication.

2. **Microservices** — each bounded context deployed as an independent service (Laravel or Go/Lumen), communicating via HTTP/gRPC or message queue.

3. **Serverless** — AWS Lambda or equivalent, which is immediately ruled out due to sanctions blocking AWS, GCP, and Azure. No viable Syrian-hosted serverless platform exists.

Syria-specific constraints drive this decision:

- **Infrastructure limitations:** Beza will run on servers hosted by Syrian Telecom, private data centers in Damascus and Aleppo, or colocation within SYsteam facilities. These environments lack Kubernetes, service meshes, or container orchestration tooling. Operations staff are comfortable with LAMP-stack administration (Apache/Nginx + MySQL + PHP) but not with Docker Swarm, Consul, or Envoy.

- **DevOps capacity:** The initial platform team is 3-4 engineers. Maintaining a microservices deployment (CI/CD pipelines per service, service discovery, distributed tracing, log aggregation) would consume 40-50% of engineering time.

- **Deployment reality:** Servers are provisioned manually or via basic Ansible playbooks. A monolith can be deployed as a single artifact; microservices would require coordinated rollouts that exceed operational maturity in year one.

- **Module count:** V1 bounded contexts are Core (user/auth), Agent, Wallet, Ledger, FX, Bill Pay, Transfer, Reporting — 8 modules. This is tractable as a monolith. Splitting into 8 services would multiply operational complexity without proportional benefit.

## Decision
Adopt a Laravel Modular Monolith for V1, with explicit rules for module communication:

**Module structure:**
```
app/Modules/
  Core/
    Interfaces/       # Service contracts (interfaces)
    Models/
    Services/         # Implementation
  Agent/
    Interfaces/
    Services/
  Wallet/
  Ledger/
  ...
```

**Communication rules:**
1. **Synchronous reads:** Module A calls Module B via `ModuleBServiceInterface`. Module A depends on the interface only (`app/Modules/Core/Interfaces/`). Implementation is injected via Laravel's service container binding.
2. **Asynchronous writes:** Module A publishes events via `Illuminate\Contracts\Events\Dispatcher`. Module B subscribes via listeners. All write-side cross-module operations use events, never direct method calls.
3. **Data isolation:** Each module has designated table name prefixes: `core_`, `agent_`, `wallet_`, `ledger_`, `fx_`, `bill_pay_`, `transfer_`, `reporting_`. No module reads another module's tables directly — always through the interface or event.

**Extraction criteria (2 of 3 must be met):**
- Different scaling needs: one module requires 3x the resources of others
- Data isolation requirements: regulatory mandate for separate database instance
- Independent deploy frequency: one module releases at 2x the rate of the monolith

## Consequences
**Positive:**
- One codebase to deploy, one supervisor process to monitor — achievable by Syrian ops teams today
- Single MySQL database simplifies backup, restore, and disaster recovery (critical given on-premise constraints)
- Laravel's service container makes interface-based module boundaries natural and testable
- Events via Laravel's `Event::dispatch()` are synchronous by default, but can be queued later without code changes
- Full Laravel ecosystem (Horizon for queues, Telescope for debugging, Pulse for monitoring) works out of the box

**Negative / Trade-offs:**
- All modules scale together — high-traffic modules (e.g., FX rate lookups) cannot independently scale without extraction
- PHP process model means no true parallelism within a single request — long-running operations must be queued
- Module boundary enforcement is convention-based (no compiler-enforced boundary) — code review is critical
- Extraction to microservices later requires unbundling interface contracts and event payloads, which carries migration cost

## Compliance
Enforced via:
1. `php artisan make:module` artisan command generates the correct directory structure with interface contract
2. PHPStan at level 6 with custom rule: prohibits `use` statements across module boundaries for Models and Eloquent queries
3. CI pipeline: PHPStan and architectural testing (using `dms/phpunit-arquitect`) ensures no direct cross-module table access
4. Extraction criteria evaluated quarterly at architecture review
5. New modules require ADR approval before creation to prevent module sprawl
