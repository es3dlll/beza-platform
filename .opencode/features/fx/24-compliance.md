# FX Engine Compliance

## Regulatory Framework

### Applicable Regulations
```
1. Central Bank of Syria (CBS) — Foreign Exchange Regulations
   - All digital FX operators must report rates daily
   - Maximum spread on consumer conversions: 5%
   - Spread must be disclosed to user before transaction
   - CBS official rate must be displayed alongside Beza rate

2. Syria Anti-Money Laundering Law (Law No. 31 of 2010)
   - FX conversions are financial transactions subject to AML
   - Conversions > $5,000 (or equivalent) require enhanced due diligence
   - Suspicious FX patterns (structuring, rapid conversion chains) must be reported
   - Record keeping: 10 years minimum

3. Data Protection
   - Rate data is commercial data (not personal)
   - Conversion history is personal financial data
   - User identity remains confidential in CBS reports
```

## CBS (Central Bank of Syria) Rate Reporting

### Daily Rate Report
```
Report Name: بيان أسعار الصرف اليومي — Beza Financial Services
Frequency: Daily by 08:00 AM (before market open)
Recipient: Central Bank of Syria — Foreign Exchange Directorate
Format: PDF (Arabic) + CSV

Report Content:
┌─────────────────────────────────────────────────────────────┐
│ بيان أسعار الصرف اليومي                                    │
│ Beza Financial Services                                     │
│ تاريخ التقرير: 2026-06-01                                   │
│                                                            │
│ 1. أسعار الصرف الرسمية:                                     │
│    الزوج     │ السعر الرسمي من مصرف سورية المركزي           │
│    USD/SYP   │ 13,100                                       │
│    EUR/SYP   │ 14,200                                       │
│                                                            │
│ 2. أسعار الصرف المعتمدة من Beza:                           │
│    الزوج     │ سعر الشراء  │ سعر البيع  │ السعر الوسطي     │
│    USD/SYP   │ 14,700      │ 15,000      │ 14,850           │
│    EUR/SYP   │ 16,100      │ 16,400      │ 16,250           │
│    EUR/USD   │ 1.088       │ 1.100       │ 1.094            │
│                                                            │
│ 3. حجم العمليات المنفذة:                                    │
│    الزوج     │ عدد العمليات │ الحجم الإجمالي (ل.س)          │
│    USD/SYP   │ 120          │ 250,000,000                    │
│    EUR/SYP   │ 30           │ 45,000,000                     │
│                                                            │
│ 4. الفارق السعري (السبريد):                                 │
│    الزوج     │ متوسط السبريد │ الحد الأقصى │ الحد المسموح   │
│    USD/SYP   │ 2.4%          │ 3.0%         │ 5% ✓          │
│    EUR/SYP   │ 2.8%          │ 3.5%         │ 5% ✓          │
│                                                            │
│ 5. ملاحظات:                                                 │
│    - جميع العمليات ضمن الحدود النظامية المسموح بها          │
│    - تم الإبلاغ عن عملية واحدة بقيمة 8,000 دولار (أعلى من   │
│      حد 5,000 دولار) وفقاً لتعليمات مكافحة غسل الأموال      │
│    - لا توجد عمليات مشبوهة تم رصدها                        │
└─────────────────────────────────────────────────────────────┘
```

### Report Generation Implementation
```php
class CbsReportingService
{
    public function generateDailyReport(Carbon $date): CbsReport
    {
        $report = new CbsReport();
        $report->report_date = $date;
        $report->report_type = 'daily';

        foreach (CurrencyPair::cases() as $pair) {
            $conversions = FxConversion::where('created_at', '>=', $date->startOfDay())
                ->where('created_at', '<=', $date->endOfDay())
                ->where('pair', $pair->value)
                ->where('status', ConversionStatus::COMPLETED)
                ->get();

            $cbsRate = $this->getCbsOfficialRate($pair, $date);

            $report->addPairData([
                'pair' => $pair->value,
                'cbs_official_rate' => $cbsRate,
                'cbs_official_source' => 'CBS Daily Bulletin',
                'beza_avg_bid' => $conversions->avg('rate_used'),
                'beza_avg_ask' => $conversions->avg('rate_used') * (1 + $conversions->avg('spread_pct')),
                'beza_avg_mid' => $conversions->avg('rate_used'),
                'beza_avg_spread' => $conversions->avg('spread_pct'),
                'max_spread' => $conversions->max('spread_pct'),
                'volume_converted' => $conversions->sum('source_amount'),
                'transaction_count' => $conversions->count(),
                'large_transactions' => $conversions->filter(fn($c) =>
                    $c->source_amount > config('fx.reporting.large_txn_threshold.' . $pair->base()->value, 5000000)
                )->count(),
            ]);
        }

        $this->storeReport($report);
        $this->exportPdf($report);

        return $report;
    }

    private function getCbsOfficialRate(CurrencyPair $pair, Carbon $date): float
    {
        // Fetch from CBS official API or scrape daily bulletin
        return FxRate::where('pair', $pair->value)
            ->where('source', 'CBS Official')
            ->whereDate('recorded_at', $date)
            ->orderByDesc('recorded_at')
            ->value('mid') ?? 0;
    }
}
```

## Spread Limits Per Regulations

### Regulatory Spread Caps
| Pair | Regulatory Max Spread | Beza Max (self-imposed) | Beza Standard |
|------|---------------------|------------------------|---------------|
| SYP/USD | 5% | 4% | 3% |
| SYP/EUR | 5% | 4% | 3.5% |
| USD/EUR | 3% | 2.5% | 1.5% |

### Spread Compliance Checks
```php
class SpreadComplianceCheck
{
    public function validateSpread(CurrencyPair $pair, float $spreadPct): bool
    {
        $regulatoryLimit = config("fx.regulatory.max_spread.{$pair->value}", 0.05);
        $selfLimit = config("fx.compliance.max_spread.{$pair->value}", 0.04);

        // Must pass BOTH limits
        $passesRegulatory = $spreadPct <= $regulatoryLimit;
        $passesSelfLimit = $spreadPct <= $selfLimit;

        if (!$passesRegulatory) {
            logger()->critical("Spread {$spreadPct}% exceeds regulatory limit of {$regulatoryLimit}%", [
                'pair' => $pair->value,
            ]);
        }

        if (!$passesSelfLimit) {
            logger()->warning("Spread {$spreadPct}% exceeds self-imposed limit of {$selfLimit}%", [
                'pair' => $pair->value,
            ]);
        }

        return $passesRegulatory && $passesSelfLimit;
    }
}
```

## Rate Display Regulations

### Regulatory Display Requirements
```
CBS Regulation Article 12: Rate Display

All digital FX platforms MUST display:
1. The CBS official rate alongside any proprietary rate
2. The spread or markup clearly stated as a percentage and absolute amount
3. The last update timestamp (must be within 60 seconds or marked stale)
4. A disclaimer: "هذا السعر غير رسمي وقد يختلف عن السعر الرسمي لمصرف سورية المركزي"

Beza Compliance:
  ✓ CBS rate shown in expanded rate detail
  ✓ Spread % shown on every rate card
  ✓ Spread in absolute SYP amount shown on conversion preview
  ✓ Timestamp shown with "last updated X seconds ago"
  ✓ Stale rates clearly marked with amber indicator
  ✓ Disclaimer displayed in rate detail screen
  ✓ Rate source labels descriptive (CBS, Parallel, Black Market)
```

## Audit Trail Requirements

### Audit Record Keeping
```
All rate changes must be auditable for 10 years:

What is audited:
  - Every rate fetch (which provider, what rate, when)
  - Every rate lock (who, what rate, how long, used/expired)
  - Every conversion (who, what rate, spread, amount, result)
  - Every provider status change (active → degraded, etc.)
  - Every admin override (who, what, why, when)
  - Every anomaly detection (type, severity, action taken)

Audit retention:
  - fx_rates: 90 days in partitioned table, then archived to cold storage
  - fx_conversions: 10 years (financial transaction record)
  - fx_rate_overrides: 10 years (regulatory requirement)
  - fx_rate_locks: 90 days
  - Audit logs: 10 years

Cold Storage Format:
  - Monthly exports to Parquet format
  - Stored in S3-compatible object storage
  - Encrypted at rest (AES-256)
  - Access logged and restricted to compliance team
```

## Suspicious Activity Monitoring

### FX-Specific AML Rules
```
Rule FX-AML-1: Rapid Conversion Loop
  Pattern: User converts SYP→USD, then immediately USD→SYP (>3 cycles in 1h)
  Threshold: 3+ round-trip conversions within 60 minutes
  Action: Flag for manual review, freeze conversion capability
  Rationale: Potential layering/structuring to obscure fund origin

Rule FX-AML-2: Rate Arbitrage Exploitation
  Pattern: User locks rate, waits for market to move, converts at favorable lock
  Threshold: >5% profit on any single conversion vs current market
  Action: Flag, investigate if coordinated or automated
  Rationale: Could indicate insider knowledge or market manipulation

Rule FX-AML-3: Large Split Conversions
  Pattern: Multiple conversions just below reporting threshold ($5,000)
  Threshold: 3+ conversions within 1h, each $4,000-$4,999
  Action: Flag for STR (Suspicious Transaction Report)
  Rationale: Structuring to avoid EDD triggers

Rule FX-AML-4: High-Risk Corridor
  Pattern: Conversions from high-risk jurisdiction
  Action: Enhanced due diligence on source of funds
  Rationale: Syria under enhanced monitoring by FATF

Rule FX-AML-5: Rapid Wallet Depletion via FX
  Pattern: Wallet funded → multiple FX conversions → funds moved out
  Threshold: >80% of wallet balance converted and transferred within 24h
  Action: Flag, freeze, compliance review
  Rationale: Smurfing/structuring indicator
```

### STR Filing Process (FX-Specific)
```
1. Detection: AML rule triggers on conversion pattern
2. Investigation: Compliance officer reviews conversion history
   - Check user profile, KYC level, transaction history
   - Check device, IP, location patterns
   - Check counterparties (if funds transferred after conversion)
3. Narrative: Write STR describing FX pattern
4. Filing: Submit through CBL STR portal
5. Flag: Mark user as "enhanced monitoring" — 30 days
6. Record: Keep all data for 10 years
7. Follow-up: If suspicious confirmed → file STR to FIU (Financial Intelligence Unit)
```
