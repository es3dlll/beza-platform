# Savings Infrastructure

## Deployment Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                          Load Balancer                           │
└────────┬────────────────────┬──────────────────────┬─────────────┘
         │                    │                      │
┌────────▼──────┐   ┌────────▼──────┐   ┌────────────▼──────────┐
│  App Server 1  │   │  App Server 2  │   │   App Server N        │
│  (Laravel)    │   │  (Laravel)    │   │   (Laravel)            │
├────────────────┤   ├────────────────┤   ├───────────────────────┤
│ SavingsModule  │   │ SavingsModule  │   │  SavingsModule         │
└────────┬───────┘   └────────┬───────┘   └───────────┬───────────┘
         │                    │                        │
         └────────────────────┼────────────────────────┘
                              │
         ┌────────────────────┼────────────────────────┐
         │                    │                        │
┌────────▼───────┐   ┌───────▼────────┐   ┌───────────▼───────────┐
│   MySQL        │   │   Redis        │   │   RabbitMQ / SQS      │
│   Primary      │   │   - Cache      │   │   - Events            │
│   + Replica    │   │   - Locks      │   │   - Queue             │
│                │   │   - Sessions   │   │   - Scheduled jobs    │
└────────────────┘   └────────────────┘   └───────────────────────┘
                                                    │
         ┌──────────────────────────────────────────┘
         │
┌────────▼─────────────────────────────────────────────────────────┐
│                        CFE (Core Financial Engine)                │
│  - Account management (main + sub-wallets)                       │
│  - Transaction processing (hold, post, settle)                    │
│  - Balance queries                                                │
│  - Pool investment engine                                         │
│  - Profit/return calculation                                      │
└───────────────────────────────────────────────────────────────────┘
```

## Cron Jobs

```php
// Console/Kernel.php — Schedule definitions

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Auto-save: runs every hour, processes due goals
        $schedule->command('savings:process-autosave')
            ->hourly()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/savings-autosave.log'));

        // Profit share calculation: 1st of month at 00:00
        $schedule->command('savings:calculate-profit')
            ->monthlyOn(1, '00:00')
            ->withoutOverlapping(60)
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/savings-profit.log'));

        // Goal completion check: every 30 minutes
        $schedule->command('savings:check-completions')
            ->everyThirtyMinutes()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/savings-completions.log'));

        // Clear expired invite codes: daily at 03:00
        $schedule->command('savings:cleanup-invites')
            ->dailyAt('03:00')
            ->appendOutputTo(storage_path('logs/savings-cleanup.log'));

        // Reconciliation check: daily at 02:00
        $schedule->command('savings:reconcile')
            ->dailyAt('02:00')
            ->withoutOverlapping(30)
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/savings-reconcile.log'));
    }
}
```

## Queue Workers

```php
// supervisor.conf — Queue worker configuration

[program:savings-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/beza/artisan queue:work redis --queue=savings_high --sleep=3 --tries=1 --timeout=60
numprocs=4
autostart=true
autorestart=true

[program:savings-low]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/beza/artisan queue:work redis --queue=savings_low --sleep=3 --tries=3 --timeout=120
numprocs=2
autostart=true
autorestart=true

[program:savings-bulk]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/beza/artisan queue:work redis --queue=savings_bulk --sleep=5 --tries=3 --timeout=600
numprocs=1
autostart=true
autorestart=true
```

## Redis Usage

```php
// Cache keys
Cache Key Pattern                        TTL      Purpose
────────────────────────────             ─────    ─────────────────────
savings:goal:{id}:progress               300s     Goal progress cache
savings:user:{id}:summary               300s     User savings summary
savings:pool:total                       60s      Pool total (frequently updated)
savings:profit:last:{goal_id}           86400s   Last profit distribution
savings:roundup:daily:{user_id}         86400s   Daily round-up counter
savings:roundup:monthly:{user_id}       2592000s Monthly round-up counter
savings:autosave:lock:{goal_id}         30s      Auto-save execution lock
savings:deposit:lock:{goal_id}          10s      Deposit lock (prevent race)

// Lock configuration
Cache::lock("savings:autosave:lock:{$goal->id}", 30)
    ->block(5, function () use ($goal) {
        $this->autoSaveService->execute($goal);
    });
```

## Database Sharding Strategy

```sql
-- Phase 1: Single DB (up to 100K goals)
-- Phase 2: Read replicas for goal queries
-- Phase 3: Shard by user_id modulo N

-- Goal partitioning strategy:
CREATE TABLE savings_goals (
    -- ...
) PARTITION BY HASH(user_id) PARTITIONS 8;

-- Transaction partitioning by month:
CREATE TABLE savings_transactions (
    -- ...
) PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p_2026_01 VALUES LESS THAN (UNIX_TIMESTAMP('2026-02-01')),
    PARTITION p_2026_02 VALUES LESS THAN (UNIX_TIMESTAMP('2026-03-01')),
    PARTITION p_2026_03 VALUES LESS THAN (UNIX_TIMESTAMP('2026-04-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```
