# Sharia Compliance Framework

> Single source of truth for Sharia compliance rules across ALL Beza Platform features. All products and features must be reviewed and certified by the Sharia Board.

## Governance

### Sharia Board
| Role | Member |
|------|--------|
| Chairman | Dr. [Name], PhD Islamic Finance, Al-Azhar University |
| Member | Sheikh [Name], Sharia scholar, Damascus University |
| Member | Dr. [Name], PhD Islamic Economics, University of Jordan |
| Secretary | Compliance Officer, Beza Pay |

### Board Responsibilities
1. Review and approve all new financial products and features
2. Issue fatwas (Sharia rulings) for product structures
3. Annual audit of all operations for Sharia compliance
4. Review and approve all marketing materials mentioning Sharia
5. Calculate and distribute Zakat
6. Annual Sharia compliance report

### Review Process
```
1. Feature team submits Sharia Assessment Form
2. Internal compliance review (5 working days)
3. Sharia Board review (monthly meeting, or urgent via email)
4. Board issues resolution: Approve / Approve with conditions / Reject
5. If rejected: Feature team revises and resubmits
6. Approved features tagged with Sharia certification number
7. Annual re-certification required
```

## Qard Hasan (قرض حسن) — Benevolent Loan

### Definition
Qard Hasan is an interest-free loan. The borrower repays only the principal amount. This is the primary lending model for Beza Pay's microfinance product.

### Rules
| Rule | Description | Compliance Check |
|------|-------------|-----------------|
| Zero profit | No interest, no markup, no additional amount charged | System enforces `profit_rate = 0` |
| Principal only | Borrower repays exactly what was borrowed | All repayment schedules verified |
| Late fees | Must go to charity, not to Beza Pay | Late fee account is segregated |
| Voluntary gift | Borrower may voluntarily give extra (هدية), but cannot be contractual | Optional field, no UI prompt |
| Purpose | Must be for permissible (حلال) purpose | Loan application screens for prohibited use |

### Late Fees to Charity
```php
class QardHasanLateFee
{
    const LATE_FEE_RATE = 0.001;   // 0.1% per day late
    const MAX_LATE_FEE = 0.05;     // 5% cap of principal
    const CHARITY_ACCOUNT_ID = 'charity_qard_hasan';

    public function calculateLateFee(Loan $loan): int
    {
        if (!$loan->isOverdue()) {
            return 0;
        }

        $daysLate = now()->diffInDays($loan->due_date);
        $fee = min(
            $loan->principal * self::LATE_FEE_RATE * $daysLate,
            $loan->principal * self::MAX_LATE_FEE
        );

        return (int) ceil($fee);
    }

    public function collectLateFee(Loan $loan): void
    {
        $fee = $this->calculateLateFee($loan);

        if ($fee > 0) {
            // Transfer late fee to charity wallet
            WalletService::transfer(
                from: $loan->borrower_wallet_id,
                to: self::CHARITY_ACCOUNT_ID,
                amount: $fee,
                reason: 'Qard Hasan late fee - charitable donation',
            );

            // Record in charity ledger
            CharityLedger::record([
                'source' => 'qard_hasan_late_fee',
                'loan_id' => $loan->id,
                'amount' => $fee,
                'donor_id' => $loan->borrower_id,
            ]);
        }
    }
}
```

### Qard Hasan Terms
| Parameter | Value |
|-----------|-------|
| Maximum principal | 500,000 SYP |
| Maximum tenure | 12 months |
| Profit rate | 0% |
| Processing fee | Maximum 1% (actual cost only, not profit) |
| Late fee | 0.1% per day (to charity, capped at 5%) |
| Grace period | 3 days after due date |

## Murabaha (مرابحة) — Cost-Plus Financing

### Definition
Murabaha is a cost-plus sale where Beza Pay purchases an asset and sells it to the customer at a disclosed markup (profit margin). Payment is deferred in installments.

### Rules
| Rule | Description | Compliance Check |
|------|-------------|-----------------|
| Asset existence | Asset must exist at time of sale | Proof of delivery / ownership transfer |
| True purchase | Beza Pay must own the asset before selling | Legal ownership documents |
| Cost disclosure | Original cost must be disclosed to customer | On contract, both cost and profit displayed |
| Fixed profit | Profit cannot change after contract | System locks profit rate |
| No late penalty | Late payment cannot increase profit | Late fees only to charity (same as Qard Hasan) |
| No compounding | Unpaid installments do not compound | Fixed installment schedule |

### Murabaha Process
```
1. Customer requests financing for specific asset (e.g., refrigerator, motorcycle)
2. Beza Pay purchases asset from vendor (payment to vendor)
3. Beza Pay takes ownership (legal or constructive)
4. Customer agrees to purchase from Beza Pay at cost + disclosed profit
5. Deferred payment: installment contract signed
6. Asset delivered to customer
7. Customer pays installments on schedule
```

### Murabaha Contract Disclosure
```php
class MurabahaContract
{
    public function generateDisclosure(MurabahaRequest $request): array
    {
        $assetCost = $request->asset_cost;           // e.g., 1,000,000 SYP
        $profitRate = 0.08;                           // 8% fixed profit
        $profitAmount = $assetCost * $profitRate;     // 80,000 SYP
        $totalAmount = $assetCost + $profitAmount;    // 1,080,000 SYP
        $installments = 12;
        $monthlyAmount = $totalAmount / $installments; // 90,000 SYP

        return [
            'asset_description' => $request->asset_description,
            'vendor_name' => $request->vendor_name,
            'original_cost' => $assetCost,
            'profit_margin' => $profitRate,
            'profit_amount' => $profitAmount,
            'total_sale_price' => $totalAmount,
            'number_of_installments' => $installments,
            'monthly_installment' => $monthlyAmount,
            'disclosure_statement' => 'تم الإفصاح عن التكلفة الأصلية للسلعة وقيمة الربح. تمت الموافقة على الشراء بالمرابحة.',
        ];
    }
}
```

### Prohibited Murabaha Items
- Gold, silver, precious metals (currency-like)
- Cryptocurrencies
- Pork, alcohol, tobacco
- Weapons, ammunition
- Adult entertainment
- Gambling equipment
- Any asset used for prohibited (حرام) activity

## Prohibited Activities (حرام)

### Absolute Prohibitions
| Activity | Basis | Related Features |
|----------|-------|-----------------|
| Riba (ربا) — Interest | Qur'an 2:275 | No interest-bearing accounts, no late interest |
| Gharar (غرر) — Excessive uncertainty | Hadith | All contract terms must be clear and fixed |
| Maysir (ميسر) — Gambling | Qur'an 5:90 | No lottery, no gambling, no speculation |
| Haram goods | Qur'an 2:173 | No pork, alcohol, tobacco financing |
| Fraud (غش) | Hadith | Full disclosure in all transactions |

### Permissible (حلال) Investment
- Only real assets and services
- No derivatives, futures, or options
- No short selling
- No speculative trading
- Equity investments only in Sharia-compliant businesses

## Zakat (زكاة)

### Calculation
```php
class ZakatCalculator
{
    public function calculate(User $user): ZakatResult
    {
        // Zakat is 2.5% of wealth held for one lunar year
        $walletBalance = $user->wallet->balance;
        $savingsAccounts = $user->savingsAccounts()->sum('balance');
        $investments = $user->investments()->where('held_for_days', '>=', 354)->sum('current_value');

        $totalWealth = $walletBalance + $savingsAccounts + $investments;

        // Nisab threshold (value of 85g gold)
        $nisab = 8500000; // SYP (approximate, recalculated daily)

        if ($totalWealth < $nisab) {
            return ZakatResult::notDue($totalWealth, $nisab);
        }

        $zakatDue = $totalWealth * 0.025;

        return ZakatResult::due([
            'total_wealth' => $totalWealth,
            'nisab' => $nisab,
            'zakat_due' => $zakatDue,
            'currency' => 'SYP',
        ]);
    }
}
```

### Zakat Features
- Automatic Zakat calculation on dashboard
- One-click Zakat payment from wallet
- Zakat distributed to approved charities
- Annual Zakat report for tax purposes
- Zakat reminders during Ramadan

## Sharia Audit

### Annual Audit Scope
1. All transaction types reviewed for Sharia compliance
2. Late fee collection verified as charitable (not revenue)
3. Murabaha contracts verified for proper disclosure
4. Qard Hasan principal-only verified
5. Prohibited activity filters verified (no gambling, etc.)
6. Zakat calculation accuracy verified
7. Marketing materials reviewed for Sharia claims

### Audit Output
```
Annual Sharia Compliance Certificate
Issued by: Beza Pay Sharia Board
Date: [Current Year]
Certifies that Beza Pay's operations conform to Sharia principles.
Certification Number: BEZA-SHARIA-[YEAR]-[SEQUENTIAL]

Products Certified:
1. Qard Hasan Microfinance — Sharia-compliant
2. Murabaha Asset Financing — Sharia-compliant
3. P2P Transfers — Sharia-compliant
4. Agent Cash-in/out — Sharia-compliant
5. Merchant Payments — Sharia-compliant
6. Savings Wallet — Sharia-compliant (Qard Hasan basis)
```

## Penalties for Non-Compliance

| Violation | Penalty |
|-----------|---------|
| Interest charged in error | Immediate refund + donation of equivalent amount to charity |
| Sharia-unapproved product launch | Product freeze + board review + public retraction |
| Late fee retained as revenue | 3x amount donated to charity |
| Prohibited activity not blocked | Feature disabled + compliance review |
| Marketing misrepresentation | Corrective communication + board apology |
