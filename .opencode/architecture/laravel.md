# Backend Engineering Spec — Laravel Modular Monolith

## Technology Stack

| Component  | Choice               | Version |
| ---------- | -------------------- | ------- |
| Framework  | Laravel              | 11.x    |
| PHP        | PHP                  | 8.3+    |
| Database   | MySQL (Percona)      | 8.0     |
| Cache      | Redis                | 7.x     |
| Queue      | RabbitMQ             | 3.12+   |
| Search     | Elasticsearch        | 8.x     |
| Monitoring | Prometheus + Grafana | Latest  |

## Module Structure (25+ Modules)

```
app/Modules/
├── Auth/           # Registration, login, OAuth, MFA, sessions
├── Wallet/         # Multi-currency wallet management
├── Transfer/       # P2P transfers, request money
├── Agent/          # Agent network, cash-in/out, float
├── Merchant/       # QR, POS, payment links, settlement
├── BillPayment/    # Biller integrations, recurring
├── Payroll/        # Bulk salary processing
├── Government/     # Tax, fees, fine payments
├── Remittance/     # Diaspora corridors, FX, recurring
├── FX/             # Rate engine, provider management
├── Savings/        # Goals, auto-save, round-up
├── Financing/      # Credit scoring, loans, collections
├── Cards/          # Virtual/physical card management
├── Settlement/     # Batch settlement, reconciliation
├── Education/      # School/university fee management
├── Humanitarian/   # NGO aid distribution
├── Loyalty/       # Rewards, cashback
├── OpenFinance/    # API gateway for third parties
├── Loyalty/        # Points, tiers, rewards
├── Compliance/     # KYC, AML, sanctions screening
├── Fraud/          # Rule engine, ML scoring
├── Audit/          # Immutable event log
├── Notification/   # Push, SMS, email, WhatsApp
├── Reporting/      # Analytics, reports, exports
└── Admin/          # Admin panel, user management
```

## Module Architecture Pattern

Each module follows a consistent internal structure:

```
app/Modules/{ModuleName}/
├── Controllers/
│   ├── {ModuleName}Controller.php
│   └── Admin{ModuleName}Controller.php
├── Actions/
│   ├── Create{Entity}Action.php
│   ├── Update{Entity}Action.php
│   └── Process{Entity}Action.php
├── Services/
│   ├── {ModuleName}Service.php
│   └── {ModuleName}Factory.php
├── Repositories/
│   ├── {Entity}Repository.php
│   └── {Entity}ReadRepository.php
├── Models/
│   ├── {Entity}.php
│   └── {Entity}Scopes.php
├── Policies/
│   └── {Entity}Policy.php
├── Events/
│   ├── {Entity}Created.php
│   ├── {Entity}Updated.php
│   └── {Entity}Deleted.php
├── Listeners/
│   └── {Entity}EventListener.php
├── Jobs/
│   ├── Process{Entity}Job.php
│   └── Sync{Entity}Job.php
├── Enums/
│   ├── {Entity}Status.php
│   └── {Entity}Type.php
├── Exceptions/
│   └── {ModuleName}Exception.php
├── Rules/
│   └── {Entity}Rule.php
├── Providers/
│   └── {ModuleName}ServiceProvider.php
└── routes/
    └── api.php
```

## Action Pattern (Domain Orchestration)

Every "Action" class is a single-responsibility orchestrator that coordinates services, repositories, and external dependencies:

```php
class SendMoneyAction
{
    public function __construct(
        private TransferService $transferService,
        private WalletService $walletService,
        private FeeService $feeService,
        private LimitService $limitService,
        private FraudCheckService $fraudService,
        private EventService $eventService,
    ) {}

    public function execute(SendMoneyRequest $request): TransactionResult
    {
        // 1. Fraud check
        $fraudResult = $this->fraudService->score($request);
        if ($fraudResult->isBlocked()) {
            throw new FraudBlockedException($fraudResult->reasons);
        }

        // 2. Validate limits
        $this->limitService->validate($request->sender, $request->amount);

        // 3. Calculate fee
        $fee = $this->feeService->calculate($request->amount, $request->currency);

        // 4. Execute transfer
        $result = $this->transferService->process(
            sender: $request->sender,
            recipient: $request->recipientPhone,
            amount: $request->amount,
            fee: $fee,
        );

        // 5. Emit events
        $this->eventService->emitTransferSent($result);
        $this->eventService->emitWalletDebited($result);

        return $result;
    }
}
```

## Service Layer Patterns

### Repository Pattern

```php
interface WalletRepositoryInterface
{
    public function find(int $id): ?Wallet;
    public function findByUser(int $userId): Collection;
    public function findByUserAndCurrency(int $userId, Currency $currency): ?Wallet;
    public function create(array $data): Wallet;
    public function update(Wallet $wallet, array $data): bool;
    public function lockForUpdate(int $id): Wallet;  // Pessimistic lock
}
```

### Event Pattern

```php
// All financial events use CloudEvents schema
class TransferSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $eventId,
        public readonly string $source,
        public readonly string $type,
        public readonly array $data,
        public readonly Carbon $time,
        public readonly int $tenantId,
    ) {
        $this->eventId = Str::ulid();
        $this->time = now();
        $this->source = '/beza/transfer/1.0';
        $this->type = 'com.beza.transfer.sent';
    }

    public function broadcastOn(): array
    {
        return [
            new RabbitMQExchange('beza-events', 'topic'),
        ];
    }
}
```

## Multi-Tenancy

```php
// Every table has tenant_id
// TenantResolver middleware sets current tenant from JWT
// Global scope applies tenant_id to all queries

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantResolver::class)->current();
        if ($tenantId) {
            $builder->where("{$model->getTable()}.tenant_id", $tenantId);
        }
    }
}
```

## Idempotency

```php
// Every write endpoint accepts Idempotency-Key header
// Stored in Redis for 24h, returns cached response on duplicate

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        if (!$key) return $next($request);

        $cached = Cache::get("idempotency:{$key}");
        if ($cached) {
            return response()->json($cached['data'], $cached['status']);
        }

        $response = $next($request);
        if ($response->status() < 500) {  // Cache only successful responses
            Cache::put("idempotency:{$key}", [
                'data' => $response->getData(),
                'status' => $response->status(),
            ], 86400);  // 24 hours
        }

        return $response;
    }
}
```

## Testing Strategy

```bash
# Run module tests
php artisan test app/Modules/Wallet/
php artisan test app/Modules/Agent/
php artisan test app/Modules/FX/

# Run with coverage
php artisan test --coverage --min=80

# Run parallel
php artisan test --parallel
```
