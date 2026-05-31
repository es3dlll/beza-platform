# Authorization Patterns

> Single source of truth for RBAC, permissions, and policy enforcement across ALL Beza Platform features.

## Role Hierarchy

```
super_admin (system-wide)
  └── ops (platform operations)
  └── compliance (regulatory)
  └── loan (lending ops)
  └── support (customer service)
  └── agent (field agent / cash-in-out)
  └── merchant (business accepting payments)
  └── user (end consumer)
```

## Role Definitions

| Role | Scope | Max Sessions | Description |
|------|-------|-------------|-------------|
| `super_admin` | Global | 2 | Full system access, all tenants, all operations |
| `ops` | Tenant | 2 | Daily operations, settlements, user management |
| `compliance` | Tenant | 2 | AML reviews, KYC verification, STR filing, reporting |
| `loan` | Tenant | 2 | Loan origination, underwriting, collections |
| `support` | Tenant | 3 | User support, ticket management, limited read access |
| `agent` | Agent location | 3 | Cash-in, cash-out, agent float management |
| `merchant` | Merchant account | 3 | Payment acceptance, settlement reconciliation |
| `user` | Self | 5 | Personal wallet, transfers, bill pay |

## Permission Matrix

### Domain Resources
| Resource | Actions |
|----------|---------|
| `wallet` | `create`, `read`, `update`, `delete`, `transfer`, `fund`, `withdraw` |
| `user` | `create`, `read`, `update`, `delete`, `verify`, `suspend`, `ban` |
| `kyc` | `read`, `update`, `verify`, `reject`, `request_review` |
| `transaction` | `read`, `reverse`, `refund`, `export` |
| `settlement` | `read`, `create`, `approve`, `reconcile` |
| `agent` | `create`, `read`, `update`, `fund_float`, `reconcile` |
| `merchant` | `create`, `read`, `update`, `settle` |
| `loan` | `create`, `read`, `update`, `approve`, `disburse`, `collect` |
| `compliance` | `read`, `report`, `file_str`, `manage_sanctions` |
| `admin` | `read_logs`, `manage_roles`, `manage_tenants`, `manage_system_config` |

### Role → Permission Mapping

| Permission | super_admin | ops | compliance | loan | support | agent | merchant | user |
|-----------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `wallet:create` | ✓ | ✓ | - | ✓ | - | - | - | ✓ |
| `wallet:read` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `wallet:transfer` | ✓ | ✓ | - | ✓ | - | ✓ | - | ✓ |
| `wallet:fund` | ✓ | ✓ | - | - | - | ✓ | - | - |
| `wallet:withdraw` | ✓ | ✓ | - | - | - | ✓ | ✓ | ✓ |
| `user:create` | ✓ | ✓ | - | - | - | ✓ | - | - |
| `user:read` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | - |
| `user:update` | ✓ | ✓ | ✓ | - | ✓ | - | - | ✓ |
| `user:suspend` | ✓ | ✓ | - | - | - | - | - | - |
| `user:ban` | ✓ | ✓ | ✓ | - | - | - | - | - |
| `kyc:read` | ✓ | ✓ | ✓ | ✓ | ✓ | - | - | - |
| `kyc:update` | ✓ | - | ✓ | - | - | - | - | - |
| `kyc:verify` | ✓ | - | ✓ | - | - | - | - | - |
| `transaction:read` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `transaction:reverse` | ✓ | ✓ | - | ✓ | - | - | - | - |
| `transaction:refund` | ✓ | ✓ | - | ✓ | ✓ | - | - | - |
| `settlement:read` | ✓ | ✓ | ✓ | - | - | ✓ | ✓ | - |
| `settlement:approve` | ✓ | ✓ | - | - | - | - | - | - |
| `agent:create` | ✓ | ✓ | - | - | - | - | - | - |
| `agent:fund_float` | ✓ | ✓ | - | - | - | - | - | - |
| `merchant:create` | ✓ | ✓ | - | - | - | - | - | - |
| `merchant:settle` | ✓ | ✓ | - | - | - | - | ✓ | - |
| `loan:approve` | ✓ | - | - | ✓ | - | - | - | - |
| `loan:disburse` | ✓ | ✓ | - | ✓ | - | - | - | - |
| `compliance:file_str` | ✓ | - | ✓ | - | - | - | - | - |
| `compliance:manage_sanctions` | ✓ | - | ✓ | - | - | - | - | - |
| `admin:manage_roles` | ✓ | ✓ | - | - | - | - | - | - |
| `admin:manage_tenants` | ✓ | ✓ | - | - | - | - | - | - |
| `admin:read_logs` | ✓ | ✓ | ✓ | - | - | - | - | - |

## Policy Pattern

Every authorization decision uses a **Policy** class implementing `AuthorizationPolicy`:

```php
interface AuthorizationPolicy {
    public function authorize(User $user, string $action, $resource): AuthorizationResult;
}
```

### Policy Resolution
```
Request → Middleware extracts user + resource
       → PolicyResolver matches ResourceType → Policy class
       → Policy.authorize(user, action, resource)
       → Returns Allow / Deny with reason code
       → If Deny, HTTP 403 with error code matching catalog
```

### Built-in Policies

#### WalletPolicy
```php
class WalletPolicy implements AuthorizationPolicy {
    public function authorize(User $user, string $action, $resource): AuthorizationResult {
        $wallet = $resource; // Wallet model

        return match ($action) {
            'read' => $this->canRead($user, $wallet),
            'transfer' => $this->canTransfer($user, $wallet),
            'withdraw' => $this->canWithdraw($user, $wallet),
            default => AuthorizationResult::deny('AUTH_003', 'Action not permitted')
        };
    }

    private function canRead(User $user, Wallet $wallet): AuthorizationResult {
        if ($user->hasPermission('wallet:read')) {
            return AuthorizationResult::allow();
        }
        return AuthorizationResult::deny('AUTH_003', 'Insufficient permissions');
    }

    private function canTransfer(User $user, Wallet $wallet): AuthorizationResult {
        if (!$user->hasPermission('wallet:transfer')) {
            return AuthorizationResult::deny('AUTH_003', 'Transfer not permitted');
        }
        // ABAC: daily limit check
        if (!$this->checkDailyLimit($user, $wallet)) {
            return AuthorizationResult::deny('WAL_002', 'Daily limit exceeded');
        }
        // ABAC: KYC level check
        if ($user->kyc_level < 1) {
            return AuthorizationResult::deny('AUTH_003', 'KYC level 1 required');
        }
        return AuthorizationResult::allow();
    }
}
```

## ABAC Rules (Attribute-Based Access Control)

### Amount Thresholds by Role & KYC

| KYC Level | Daily Transfer (user) | Daily Transfer (agent) | Daily Transfer (merchant) |
|-----------|----------------------|----------------------|--------------------------|
| 0 (unverified) | 0 SYP | 200,000 SYP | N/A |
| 1 (basic) | 50,000 SYP | 500,000 SYP | 500,000 SYP |
| 2 (verified) | 500,000 SYP | 2,000,000 SYP | 5,000,000 SYP |
| 3 (full) | 5,000,000 SYP | 10,000,000 SYP | 50,000,000 SYP |

### ABAC Rule Evaluation Order
1. **Role check** — Does user have the required permission?
2. **Resource ownership** — Does user own the resource? (or has `*:read`)
3. **KYC level** — Does user's KYC level meet the minimum?
4. **Daily/monthly limits** — Has user exceeded transaction limits?
5. **Geographic check** — Is the transaction within allowed corridors?
6. **Time-based restrictions** — Is the action allowed at this hour/day?
7. **Risk score** — Does the user's current risk score permit this action?

### ABAC Rule Definition Format
```php
class AbacRule {
    public readonly string $name;
    public readonly string $resource;
    public readonly string $action;
    public readonly array $conditions; // Condition[]
    public readonly string $effect;    // 'allow' | 'deny'

    public function evaluate(array $attributes): bool;
}

// Condition example
[
    'field' => 'transaction.amount',
    'operator' => 'lte',
    'value' => '$user.daily_remaining_limit'
]
```

## Implementation Reference

### Middleware
```php
// routes/api.php
Route::middleware(['auth:api', 'authorize:wallet:transfer,wallet'])->post('/transfer');
```

### Policy Registration
```php
// AppServiceProvider or AuthServiceProvider
PolicyResolver::register(Wallet::class, WalletPolicy::class);
PolicyResolver::register(User::class, UserPolicy::class);
PolicyResolver::register(Transaction::class, TransactionPolicy::class);
```

### Permission Caching
- Role-permission mappings cached in Redis (TTL: 300s)
- User permission set cached on login (TTL: 900s)
- Cache invalidation on: role change, permission change, user suspension
