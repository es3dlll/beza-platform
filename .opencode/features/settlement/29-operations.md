# Settlement Operations

## Daily Operations

### 08:00 — Morning Review
```
1. Open settlement dashboard
2. Review previous day's batch status:
   - Were all EOD batches settled successfully?
   - Any exceptions still open?
   - Match rate ≥ 98%?
3. Check for any pending real-time settlements
4. Review bank confirmations received overnight
5. Investigate any unresolved exceptions
```

### 10:00 — Exception Triage
```
1. List all open exceptions sorted by severity
2. For each high/critical exception:
   - Assign investigator
   - Set target resolution time
   - Contact bank/counterparty if needed
3. For medium/low exceptions:
   - Batch resolve (tolerance matches)
   - Create adjustment entries
4. Update exception status in system
```

### 15:00 — Mid-Day Check
```
1. Verify real-time settlement processing is healthy
2. Check bank integration health
3. Review settlement pool balance (should be 0)
4. Prepare for EOD batch (verify cut-off config)
5. Confirm all payment orders transmitted OK
```

### 23:00 — EOD Settlement Run
```
1. System auto-starts: php artisan settlement:run-eod
2. Monitor batch creation (5 min)
3. Monitor batch processing (15 min)
4. Monitor payment transmission (5 min)
5. Monitor confirmations (up to 60 min)
6. Monitor reconciliation (10 min)
7. If any failure: execute runbook
8. Confirm all batches settled before sign-off
```

## Weekly Operations

### Monday — Weekly Reconciliation Review
```
1. Run weekly settlement summary report
2. Review exception aging (unresolved > 48h)
3. Meet with finance team:
   - Settlement pool reconciliation
   - Outstanding adjustments
   - Write-off approvals
4. Update settlement account balances
```

### Friday — Bank Confirmation Reconciliation
```
1. Download bank statements for the week
2. Manual reconciliation of any unmatched items
3. Create adjustment entries for bank fees
4. Archive confirmed payment orders
```

## Monthly Operations

### Month End — Settlement Close
```php
// Day 1: Finalize all unsettled batches
php artisan settlement:close-month --month=2026-05

// Day 2: Generate monthly settlement report
php artisan settlement:generate-report --type=monthly --month=2026-05

// Day 3: Settlement committee review
1. Review monthly report
2. Approve write-offs > 50,000 SYP
3. Sign off on settlement accuracy
4. Archive monthly records
```

## Operational Runbooks

### Manual Batch Processing
If automated EOD fails, run manually:
```bash
# Step 1: Check pending transactions
php artisan settlement:check-pending

# Step 2: Create batch manually
php artisan settlement:create-batch \
    --type=eod \
    --cut-off="2026-05-29T23:00:00" \
    --force

# Step 3: Process batch
php artisan settlement:process-batch \
    --id=STL-20260529-0001

# Step 4: Generate and transmit payment orders
php artisan settlement:transmit-orders \
    --batch=STL-20260529-0001

# Step 5: Run reconciliation
php artisan settlement:run-reconciliation \
    --batch=STL-20260529-0001

# Step 6: Report issues to team
```

### Adding a New Settlement Account
```php
// 1. Register in settlement_accounts
SettlementAccount::create([
    'entity_type' => 'bank',
    'entity_id' => 'new_bank_code',
    'account_name' => 'New Bank Syria',
    'cfe_account_id' => 'cfe_acc_bank_new',
    'settlement_type' => 'both',
    'cut_off_time' => '22:00',
]);

// 2. Configure bank integration in config/banks.php
// 3. Test connection: php artisan bank:test-connection new_bank_code
// 4. Create test batch: php artisan settlement:test-batch --entity=new_bank_code
// 5. Activate in production
```

### Cut-Off Time Change
```php
// 1. Update cut-off in settlement_accounts
$account = SettlementAccount::where('entity_id', 'bemo_saudi_fransi')->first();
$account->cut_off_time = '22:30';
$account->save();

// 2. Update cron schedule if needed
// app/Console/Kernel.php
$schedule->command('settlement:run-eod')
    ->dailyAt('22:30') // Previously 23:00
    ->timezone('Asia/Damascus');

// 3. Notify bank of new cut-off time
// 4. Monitor first batch under new cut-off
```

## Operations KPIs

| KPI | Target | Current | Status |
|-----|--------|---------|--------|
| Batches processed on time | ≥ 99.5% | 99.7% | ✅ |
| Avg match rate | ≥ 99% | 99.3% | ✅ |
| Exceptions resolved in 24h | ≥ 95% | 93% | ⚠️ |
| Max exception resolution time | 72h | 48h | ✅ |
| Manual interventions per week | ≤ 5 | 3 | ✅ |
| Bank confirmation success rate | ≥ 98% | 99.1% | ✅ |

## Holiday Schedule
```php
// config/settlement-holidays.php
return [
    'bank_holidays' => [
        '2026-01-01' => 'رأس السنة الميلادية',
        '2026-03-21' => 'عيد الأم',
        '2026-04-17' => 'عيد الجلاء',
        '2026-05-01' => 'عيد العمال',
        '2026-06-15' => 'أول أيام عيد الفطر',
        '2026-06-16' => 'ثاني أيام عيد الفطر',
        '2026-08-21' => 'أول أيام عيد الأضحى',
        '2026-08-22' => 'ثاني أيام عيد الأضحى',
        '2026-10-06' => 'ذكرى حرب تشرين',
        '2026-12-25' => 'عيد الميلاد',
    ],
    'settlement_delays' => [
        'BEFORE_HOLIDAY' => 'Process EOD 2 hours early',
        'AFTER_HOLIDAY' => 'Resume normal schedule; expect delayed bank confirmations',
    ],
];
```
