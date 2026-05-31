# Savings Compliance

## Sharia Compliance (Primary)

### Qard Hasan Framework
Beza Savings operates on **Qard Hasan** (benevolent loan) principles:
1. **Principal guarantee**: The saved amount is a loan from the user to Beza, guaranteed to be returned in full
2. **No interest (riba)**: Users do not earn interest. Instead, Beza may give a **hiba** (gift) or share **profit** from halal investments
3. **Profit sharing**: Any returns come from Sharia-compliant investments of the pooled savings, distributed proportionally
4. **Transparency**: Profit calculation methodology fully disclosed to users

### Prohibited Activities
```
🚫 Riba (Interest):     Never use the word "interest" or "فائدة"
                        Never guarantee a fixed return
                        Profit is variable and based on actual returns

🚫 Gharar (Uncertainty): Full disclosure of how profit is calculated
                          No hidden fees or surprise charges
                          Early withdrawal penalty disclosed at goal creation

🚫 Maysir (Gambling):    No luck-based elements
                          No random profit boosts
                          All returns from actual economic activity

✅ Halal Investments:    Pooled savings invested in:
                          - Sharia-compliant trade finance
                          - Halal commodity murabaha
                          - Sukuk (Islamic bonds) if available
                          - Central bank approved Sharia instruments
```

### Sharia Board Approval
```php
// Every feature change requires Sharia board sign-off
class ShariaComplianceCheck
{
    public static function validateSavingsFeature(array $changes): ComplianceResult
    {
        $rules = [
            'no_interest_terminology' => function () {
                return !in_array('interest', $changes['terms'] ?? [])
                    && !in_array('فائدة', $changes['arabic_terms'] ?? []);
            },
            'profit_disclosed' => function () {
                return isset($changes['profit_calculation_method']);
            },
            'no_fixed_return' => function () {
                return !isset($changes['guaranteed_return']);
            },
            'penalty_disclosed' => function () {
                return isset($changes['early_withdrawal_penalty'])
                    && $changes['early_withdrawal_penalty']['disclosed_at_creation'] === true;
            },
        ];

        $violations = [];
        foreach ($rules as $name => $check) {
            if (!$check()) {
                $violations[] = $name;
            }
        }

        return new ComplianceResult(
            passed: empty($violations),
            violations: $violations,
            requiresShariaBoard: count($violations) > 0,
        );
    }
}
```

## Regulatory Compliance

### AML / CFT — Savings Specific
```php
// Know Your Savings (KYS) rules:
- Max savings balance without KYC upgrade:
  Level 0: 500,000 SYP total across all goals
  Level 1: 5,000,000 SYP
  Level 2: 50,000,000 SYP

- Large deposit monitoring:
  Single deposit > 500,000 SYP → flag for review
  Cumulative deposits > 2,000,000 SYP in 30 days → flag for review

- Withdrawal monitoring:
  Single withdrawal > 1,000,000 SYP → step-up auth
  Cumulative withdrawals > 5,000,000 SYP in 30 days → report to compliance

- Source of funds:
  Mandatory for deposits > 5,000,000 SYP
  Acceptable: Salary, Business income, Remittances, Gift
  Required document: Upload proof (bank statement, contract, etc.)
```

### Sanctions Screening
```php
// Every goal creator and team member screened against:
// - UN sanctions list
// - Local Syrian sanctions list
// - OFAC SDN list (if applicable)

// Screening trigger points:
- Goal creation
- Team creation
- Member joins team
- Deposit > 1,000,000 SYP
- Withdrawal > 1,000,000 SYP
- Profit distribution > 100,000 SYP
```

### Data Retention
```php
'Savings data retention periods' => [
    'savings_goals' => '10 years after goal completion/cancellation',
    'savings_transactions' => '10 years (regulatory requirement)',
    'profit_distributions' => '10 years (tax/compliance)',
    'auto_save_logs' => '2 years (operational)',
    'round_up_executions' => '2 years (operational)',
    'savings_teams' => '10 years after team disbanded',
    'savings_team_members' => '10 years after member left',
    'goal_milestones' => '5 years',
];
```

### Reporting Obligations
```php
// Regular reports generated for regulators:
'Savings compliance reports' => [
    'daily' => [
        'Total savings under management',
        'New goals created (count + volume)',
        'Large transactions > threshold',
    ],
    'monthly' => [
        'Profit distribution summary',
        'Average balance per user',
        'Goal completion rate',
        'Early withdrawal penalty summary',
        'Suspicious activity flags',
    ],
    'quarterly' => [
        'Sharia compliance audit report',
        'AML/CFT report',
        'Audited savings pool balance',
        'Investment return report',
    ],
    'annually' => [
        'Full financial audit',
        'Sharia board opinion',
        'Regulatory filing',
    ],
];
```

## Sharia-Compliant Terminology

| English Term | Arabic Term | Usage Notes |
|-------------|-------------|-------------|
| Savings | التوفير / الادخار | Preferred: التوفير |
| Profit | ربح / أرباح | Never "فائدة" (interest) |
| Profit sharing | مشاركة في الأرباح | Emphasizes sharing, not fixed return |
| Principal | رأس المال | Guaranteed to be returned |
| Early withdrawal penalty | رسوم السحب المبكر | Justified as administrative fee |
| Goal lock | قفل الهدف | Voluntary commitment device |
| Pool | مجمع التوفير | Collective savings pool |
| Return | عائد | Variable, never guaranteed |
| Qard Hasan | قرض حسن | Benevolent loan (principal guaranteed) |
| Hiba | هبة | Voluntary gift (profit) |
| Mudaraba | مضاربة | Profit-sharing investment model (if applicable) |
