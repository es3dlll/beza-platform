# ADR-001: MySQL 8.0 Over PostgreSQL

## Status
Accepted

## Context
Beza Financial OS requires a primary relational database for its Modular Monolith architecture. Three options were evaluated: MySQL 8.0, PostgreSQL 16, and MariaDB 11. The decision must account for Syria-specific constraints:

1. **DBA talent pool in Syria:** MySQL has been the dominant database in the Syrian market for over a decade, driven by local hosting providers (SYsteam, AyaaCloud) and the prevalence of shared hosting that bundles MySQL. PostgreSQL DBAs are scarce, and hiring a dedicated PostgreSQL DBA in Damascus commands a 40-60% salary premium over a MySQL DBA with equivalent experience.

2. **Sanctions impact:** US and EU sanctions block access to AWS RDS, Google Cloud SQL, and Azure Database. All infrastructure must run on-premise or on Syrian-hosted servers (e.g., Syrian Telecom, private data centers). This eliminates any cloud-managed database advantage and places a premium on operational simplicity.

3. **V1 workload profile:** Target is 500 transactions per second across all modules (transfer, bill pay, float management). This is well within the single-node capabilities of both MySQL and PostgreSQL.

4. **Event store requirement:** The Ledger module will store domain events as JSON blobs. MySQL 8.0 offers native JSON column type with JSON-path indexing, comparable to PostgreSQL's JSONB.

5. **Crash recovery:** On-premise Syrian servers experience intermittent power outages (2-4 per month in some governorates). InnoDB's crash recovery via redo logs and doublewrite buffer is battle-tested in these conditions.

## Decision
Adopt MySQL 8.0 with InnoDB as the primary database engine.

Key configuration choices:
- Character set: `utf8mb4` with `utf8mb4_unicode_ci` collation for full Arabic character support
- Isolation level: `READ COMMITTED` (default in MySQL 8.0) — sufficient for our optimistic concurrency patterns
- Event store: JSON columns with `JSON_TABLE` virtual columns and multi-value indexes for querying events
- Connection pool: 150 concurrent connections (laravel-database-default), tuned for 8-core / 32 GB RAM servers

Specific MariaDB avoidance: MariaDB 11 diverges from MySQL in `EXPLAIN` format, `INFORMATION_SCHEMA` behavior, and lacks MySQL's `hash_join` optimizer hints. Given our existing Laravel ecosystem assumes MySQL compatibility, MariaDB introduces unnecessary risk.

## Consequences
**Positive:**
- Largest DBA talent pool in Syria — hiring and retention are straightforward
- InnoDB crash recovery is robust against unplanned power loss, a reality in Syrian data centers
- Laravel's Eloquent ORM has first-class MySQL support with well-tested query builder, migrations, and schema introspection
- JSON column support is sufficient for event sourcing without requiring a separate document store
- MySQL replication (group replication or async GTID-based) is well-understood by Syrian system administrators

**Negative / Trade-offs:**
- No native ARRAY type — requires join tables or JSON serialization for multi-value attributes (e.g., agent permitted service types)
- No partial indexes — indexed views must be simulated via generated columns or application-level filtering
- No `SKIP LOCKED` support in MySQL 8.0 GA (added in 8.0.1 but not as sophisticated as PostgreSQL's implementation) — may need application-level retry logic for queue consumers
- GIS support (MySQL Spatial) is weaker than PostGIS — acceptable because V1 has no geospatial requirements

## Compliance
Enforced via:
1. `config/database.php` default connection set to `mysql` driver
2. All migrations must pass `php artisan migrate:fresh` against a MySQL 8.0 database
3. CI pipeline runs integration tests against a MySQL 8.0 service container
4. Any module wishing to use a PostgreSQL-specific feature must file a new ADR
5. Schema review checklist includes: no MyISAM tables, utf8mb4 charset, InnoDB engine specified
