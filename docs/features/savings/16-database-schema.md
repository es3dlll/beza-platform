# Savings Database Schema

## Tables

### savings_goals
```sql
CREATE TABLE savings_goals (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    name                VARCHAR(100) NOT NULL,
    icon                VARCHAR(50) NULL DEFAULT 'default',
    target_amount       BIGINT NOT NULL,                    -- In SYP piasters or USD cents
    current_amount      BIGINT NOT NULL DEFAULT 0,
    currency            ENUM('SYP', 'USD') NOT NULL DEFAULT 'SYP',
    type                ENUM('individual', 'team') NOT NULL DEFAULT 'individual',

    -- Auto-save
    auto_save_enabled   TINYINT(1) NOT NULL DEFAULT 0,
    auto_save_frequency ENUM('daily', 'weekly') NULL,
    auto_save_amount    BIGINT NULL,
    auto_save_time      TIME NULL DEFAULT '10:00:00',

    -- Round-up
    round_up_enabled    TINYINT(1) NOT NULL DEFAULT 0,

    -- Goal lock
    goal_locked         TINYINT(1) NOT NULL DEFAULT 0,
    lock_release_date   DATE NULL,                           -- Release on date
    lock_release_amount BIGINT NULL,                          -- Release when amount reached

    -- Status
    status              ENUM('active', 'completed', 'cancelled', 'awaiting_release')
                        NOT NULL DEFAULT 'active',
    target_date         DATE NULL,
    completed_at        TIMESTAMP NULL,
    cancelled_at        TIMESTAMP NULL,
    cancelled_reason    VARCHAR(500) NULL,

    -- CFE integration
    cfe_sub_account_id  VARCHAR(64) NOT NULL UNIQUE,

    -- Metadata
    metadata            JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,

    INDEX idx_sg_user (user_id),
    INDEX idx_sg_status (status),
    INDEX idx_sg_type (type),
    INDEX idx_sg_autosave_due (auto_save_enabled, status, auto_save_frequency, auto_save_time),
    INDEX idx_sg_roundup (round_up_enabled, status),
    INDEX idx_sg_target_date (target_date),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### savings_transactions
```sql
CREATE TABLE savings_transactions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    goal_id           BIGINT UNSIGNED NOT NULL,
    user_id           BIGINT UNSIGNED NOT NULL,
    uuid              CHAR(36) NOT NULL UNIQUE,             -- For idempotency

    type              ENUM('deposit', 'withdrawal', 'profit', 'roundup',
                          'penalty', 'transfer_in', 'transfer_out') NOT NULL,
    sub_type          ENUM('manual', 'auto_save', 'roundup', 'profit_distribution',
                          'early_withdrawal', 'goal_completion', 'team_contribution',
                          'team_member_leave') NULL,

    amount            BIGINT NOT NULL,                       -- Positive for in, negative for out
    fee               BIGINT NOT NULL DEFAULT 0,
    penalty           BIGINT NOT NULL DEFAULT 0,
    net_amount        BIGINT GENERATED ALWAYS AS (amount - fee - penalty) STORED,

    currency          ENUM('SYP', 'USD') NOT NULL DEFAULT 'SYP',
    balance_before    BIGINT NOT NULL,
    balance_after     BIGINT NOT NULL,

    -- References
    reference         VARCHAR(128) NULL,                     -- External reference
    source_transaction_id BIGINT UNSIGNED NULL,              -- Source wallet transaction for round-ups
    cfe_reference     VARCHAR(64) NULL,
    idempotency_key   VARCHAR(64) NULL,

    -- Notes
    note              VARCHAR(500) NULL,

    -- Timestamps
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_st_goal (goal_id),
    INDEX idx_st_user (user_id),
    INDEX idx_st_type (type),
    INDEX idx_st_created (created_at),
    INDEX idx_st_reference (reference),
    INDEX idx_st_idempotency (idempotency_key),
    INDEX idx_st_subtype (sub_type),
    FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### auto_save_logs
```sql
CREATE TABLE auto_save_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id         BIGINT UNSIGNED NOT NULL,
    amount          BIGINT NOT NULL,
    status          ENUM('pending', 'completed', 'skipped', 'failed') NOT NULL DEFAULT 'pending',
    skip_reason     VARCHAR(500) NULL,
    failure_reason  VARCHAR(500) NULL,
    reference       VARCHAR(128) NULL,
    executed_at     TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_asl_goal (goal_id),
    INDEX idx_asl_status (status),
    INDEX idx_asl_executed (executed_at),
    FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE
);
```

### round_up_configs
```sql
CREATE TABLE round_up_configs (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           BIGINT UNSIGNED NOT NULL UNIQUE,
    enabled           TINYINT(1) NOT NULL DEFAULT 0,
    primary_goal_id   BIGINT UNSIGNED NULL,                 -- Which goal receives round-ups
    round_to_nearest  BIGINT NOT NULL DEFAULT 1000,          -- Round to nearest 1000 SYP
    min_round_amount  BIGINT NOT NULL DEFAULT 100,           -- Minimum round-up to execute
    daily_max         BIGINT NOT NULL DEFAULT 50000,         -- Max round-up per day
    monthly_max       BIGINT NOT NULL DEFAULT 500000,        -- Max round-up per month
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_rc_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (primary_goal_id) REFERENCES savings_goals(id) ON DELETE SET NULL
);
```

### round_up_executions
```sql
CREATE TABLE round_up_executions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id           BIGINT UNSIGNED NOT NULL,
    user_id           BIGINT UNSIGNED NOT NULL,
    source_transaction_id BIGINT UNSIGNED NOT NULL,
    original_amount   BIGINT NOT NULL,
    rounded_amount    BIGINT NOT NULL,
    round_up_amount   BIGINT NOT NULL,
    status            ENUM('completed', 'skipped', 'failed') NOT NULL DEFAULT 'completed',
    skip_reason       VARCHAR(500) NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_rue_goal (goal_id),
    INDEX idx_rue_source (source_transaction_id),
    INDEX idx_rue_created (created_at),
    FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE,
    FOREIGN KEY (source_transaction_id) REFERENCES wallet_transactions(id)
);
```

### profit_distributions
```sql
CREATE TABLE profit_distributions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    goal_id         BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    amount          BIGINT NOT NULL,
    period          ENUM('monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'monthly',
    period_start    DATE NOT NULL,
    period_end      DATE NOT NULL,
    weight          DECIMAL(10, 8) NOT NULL,                -- Proportional weight (goal/pool)
    pool_total      BIGINT NOT NULL,                         -- Total pooled savings
    pool_return     BIGINT NOT NULL,                          -- Total return generated
    management_fee  BIGINT NOT NULL DEFAULT 0,
    net_profit      BIGINT NOT NULL,                          -- amount after fee
    distributed_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_pd_goal (goal_id),
    INDEX idx_pd_user (user_id),
    INDEX idx_pd_period (period_start, period_end),
    INDEX idx_pd_distributed (distributed_at),
    FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### savings_teams
```sql
CREATE TABLE savings_teams (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    goal_id         BIGINT UNSIGNED NOT NULL,
    created_by      BIGINT UNSIGNED NOT NULL,
    invite_code     VARCHAR(32) NOT NULL UNIQUE,
    invite_code_expires_at TIMESTAMP NULL,
    max_members     TINYINT UNSIGNED NOT NULL DEFAULT 20,
    status          ENUM('active', 'completed', 'disbanded') NOT NULL DEFAULT 'active',
    disbanded_at    TIMESTAMP NULL,
    disband_reason  VARCHAR(500) NULL,
    metadata        JSON NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    INDEX idx_steam_goal (goal_id),
    INDEX idx_steam_creator (created_by),
    INDEX idx_steam_code (invite_code),
    INDEX idx_steam_status (status),
    FOREIGN KEY (goal_id) REFERENCES savings_goals(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### savings_team_members
```sql
CREATE TABLE savings_team_members (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id         BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    contribution    BIGINT NOT NULL DEFAULT 0,               -- Total contributed by this member
    role            ENUM('owner', 'admin', 'member') NOT NULL DEFAULT 'member',
    joined_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    left_at         TIMESTAMP NULL,
    status          ENUM('active', 'inactive', 'removed', 'left') NOT NULL DEFAULT 'active',

    UNIQUE INDEX idx_stm_team_user (team_id, user_id),
    INDEX idx_stm_user (user_id),
    INDEX idx_stm_status (status),
    FOREIGN KEY (team_id) REFERENCES savings_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### savings_goal_milestones
```sql
CREATE TABLE savings_goal_milestones (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal_id         BIGINT UNSIGNED NOT NULL,
    percentage      TINYINT UNSIGNED NOT NULL,               -- 25, 50, 75, 100
    reached         TINYINT(1) NOT NULL DEFAULT 0,
    reached_at      TIMESTAMP NULL,
    notified        TINYINT(1) NOT NULL DEFAULT 0,

    UNIQUE INDEX idx_sgm_goal_pct (goal_id, percentage),
    FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE
);

-- Seed milestones on goal creation
-- 25%, 50%, 75%, 100%
```

## Migration Seed Data
```sql
-- Insert default milestones for each new goal
DELIMITER //
CREATE TRIGGER after_goal_insert
AFTER INSERT ON savings_goals
FOR EACH ROW
BEGIN
    INSERT INTO savings_goal_milestones (goal_id, percentage) VALUES
        (NEW.id, 25),
        (NEW.id, 50),
        (NEW.id, 75),
        (NEW.id, 100);
END//
DELIMITER ;
```
