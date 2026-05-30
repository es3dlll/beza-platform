# ترحيل البيانات — Data Migration

## Migration Strategy

### Phase 1: Schema Creation (Empty State)
```bash
# Create all financing tables
npm run migrate:up

# Seed reference data (financing_products)
npm run seed:financing-products

# Verify migration
npm run migration:status
```

### Phase 2: Backfill Historical Data (if migrating from legacy)
```yaml
# If Beza previously offered financing via manual process:
source: Legacy Excel/Google Sheets + PDF contracts
target: PostgreSQL financing tables

data_maps:
  financing_products:
    source: "legacy_product_catalog.xlsx"
    transform: Map old product codes to new enum values
    validation: All products exist in new schema

  financing_applications:
    source: "legacy_loans.csv"
    transform:
      user_id: Lookup by phone number in users table
      amount: Convert to SYP (if multi-currency)
      status: Map legacy statuses (active→active, paid→completed, bad→defaulted)
      dates: Convert DD/MM/YYYY to TIMESTAMPTZ
    validation: 
      - All user_ids exist
      - Amounts within product limits
      - No duplicate application references

  financing_contracts:
    source: PDF contract files → extract metadata
    transform:
      contract_number: Generate new format (BZ-XX-YYYY-NNNNN)
      total_amount: Parse from PDF
    validation: Match with applications

  financing_repayments:
    source: "legacy_payments.csv"
    transform:
      payments: Map to installment schedule
      status: Overdue if > 7 days past due
    validation: Sum of payments ≤ contract total
```

### ETL Script
```typescript
// scripts/migrate-legacy-financing.ts
async function migrateLegacyFinancing() {
  const logger = createMigrationLogger('financing');
  const source = await openLegacySource('legacy_loans_2025.csv');
  const batchSize = 100;
  
  logger.info('Starting legacy financing migration');
  
  for await (const batch of source.readBatches(batchSize)) {
    const transactions = [];
    
    for (const row of batch) {
      // 1. Lookup user
      const user = await userService.findByPhone(row.phone);
      if (!user) {
        logger.warn(`User not found: ${row.phone}`);
        continue;
      }
      
      // 2. Create application
      const application = await createApplication({
        user_id: user.id,
        product_id: mapProduct(row.product_type),
        amount: row.amount,
        term_days: row.term_days,
        purpose: row.purpose,
        status: mapStatus(row.status),
        submitted_at: parseDate(row.start_date)
      });
      
      // 3. Create contract
      const contract = await createContract({
        application_id: application.id,
        contract_number: generateContractNumber(row.product_type, row.id),
        principal: row.amount,
        total_amount: row.total_amount,
        status: mapContractStatus(row.status),
        created_at: parseDate(row.start_date)
      });
      
      // 4. Create installments from payment history
      if (row.payments) {
        for (const payment of JSON.parse(row.payments)) {
          await createRepayment({
            contract_id: contract.id,
            ...payment
          });
        }
      }
      
      transactions.push({ application, contract });
    }
    
    await db.transaction().commit(transactions);
    logger.info(`Migrated batch: ${transactions.length} records`);
  }
  
  logger.info('Legacy migration complete');
}
```

## Rollback Plan
```yaml
rollback_migration:
  condition: Data validation fails (> 1% error rate)
  steps:
    1. Stop migration process
    2. Run validation report to identify failed records
    3. Manual correction of source data
    4. Truncate migrated tables (applications, contracts, repayments)
    5. Restart migration with corrected data
  recovery_time: 2 hours
```

## Data Validation Post-Migration
```sql
-- Validation 1: All applications have contracts
SELECT COUNT(*) FROM financing_applications a
LEFT JOIN financing_contracts c ON c.application_id = a.id
WHERE c.id IS NULL;

-- Validation 2: Sum of repayments matches contract totals
SELECT c.id, c.total_amount, SUM(r.paid_amount) as total_paid
FROM financing_contracts c
JOIN financing_repayments r ON r.contract_id = c.id
GROUP BY c.id, c.total_amount
HAVING SUM(r.paid_amount) > c.total_amount;

-- Validation 3: No orphan records
SELECT COUNT(*) FROM financing_repayments r
LEFT JOIN financing_contracts c ON c.id = r.contract_id
WHERE c.id IS NULL;
```

## Data Retention & Archiving
```yaml
active_data (in primary tables):
  - Active contracts
  - Contracts completed within last 12 months
  - Pending applications

warm_archive (separate schema, queryable):
  - Contracts completed > 12 months ago
  - Rejected applications > 6 months ago
  - Credit scores > 24 months old

cold_archive (compressed, S3 Glacier):
  - Contracts > 5 years old (regulatory retention: 7 years)
  - Contract PDFs (7 years)
  - Audit logs (7 years)

purging:
  - After 7 years: permanent deletion of PII
  - Aggregated statistics retained indefinitely
```
