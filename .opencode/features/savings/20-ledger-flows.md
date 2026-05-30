# Savings Ledger Flows

## Chart of Accounts

### Savings-Related GL Accounts
```
Account Code       Account Name                        Type         Normal Balance
────────────       ──────────────────────────          ──────────    ──────────────
1-100-100          User Main Wallet (SYP)              Asset         Debit
1-100-200          User Savings Sub-Wallet (SYP)       Asset         Debit
2-200-100          Pooled Savings Liability            Liability     Credit
3-300-100          Beza Management Fee Income          Revenue        Credit
3-300-200          Beza Early Withdrawal Income        Revenue        Credit
3-300-300          Beza Round-Up Operational Income    Revenue        Credit
4-400-100          Sharia-Compliant Investment Pool    Asset          Debit
4-400-200          Pool Return Receivable              Asset          Debit
```

## Ledger Flow: Auto-Save

```
User Main Wallet (SYP)                 Savings Sub-Wallet (SYP)
┌──────────────────────────┐           ┌──────────────────────────┐
│ Balance: 125,000         │           │ Balance: 1,245,000       │
├──────────────────────────┤           ├──────────────────────────┤
│ DR: Auto-save 5,000      │──────┐    │ CR: Auto-save 5,000      │←─────┘
│ Balance: 120,000         │      │    │ Balance: 1,250,000       │
└──────────────────────────┘      │    └──────────────────────────┘
                                  │
                                  │    No fee (auto-save is free)
                                  │    No income account involved
```

## Ledger Flow: Round-Up

```
User Main Wallet (SYP)                 Savings Sub-Wallet (SYP)
┌──────────────────────────┐           ┌──────────────────────────┐
│ Balance: 76,500          │           │ Balance: 1,250,000       │
├──────────────────────────┤           ├──────────────────────────┤
│ DR: Round-up 500         │──────┐    │ CR: Round-up 500         │←─────┘
│ Balance: 76,000          │      │    │ Balance: 1,250,500       │
└──────────────────────────┘      │    └──────────────────────────┘
                                  │
                                  │    Rounding residual (<1,000 SYP)
                                  │    → No operational income (all goes to user)
```

## Ledger Flow: Goal Completion Withdrawal

```
Savings Sub-Wallet (SYP)             User Main Wallet (SYP)
┌──────────────────────────┐         ┌──────────────────────────┐
│ Balance: 2,500,500       │         │ Balance: 76,000          │
├──────────────────────────┤         ├──────────────────────────┤
│ CR: Withdrawal 2,500,000 │────┐    │ DR: Withdrawal 2,500,000 │←────┘
│ Balance: 500             │    │    │ Balance: 2,576,000       │
└──────────────────────────┘    │    └──────────────────────────┘
                                │
                                │    Residual 500 SYP stays as profit buffer
                                │    No fee on goal completion withdrawal
```

## Ledger Flow: Early Withdrawal (Locked)

```
Savings Sub-Wallet (SYP)             User Main Wallet (SYP)
┌──────────────────────────┐         ┌──────────────────────────┐
│ Balance: 1,300,000       │         │ Balance: 76,000          │
├──────────────────────────┤         ├──────────────────────────┤
│ CR: Withdrawal 500,000   │──┐      │ DR: Net 490,000          │←────┘
│ Balance: 800,000         │  │      │ Balance: 566,000         │
└──────────────────────────┘  │      └──────────────────────────┘
                              │
                              │      Beza Early Withdrawal Income
                              │      ┌──────────────────────────┐
                              └─────>│ DR: Penalty 10,000       │
                                     │ Balance: 10,000          │
                                     └──────────────────────────┘
```

## Ledger Flow: Profit Distribution

```
Sharia-Compliant Investment Pool      Pooled Savings Liability
┌──────────────────────────┐         ┌──────────────────────────┐
│ Investment: 50,000,000   │         │ Liability: 50,000,000    │
│ Return: 150,000          │         │                           │
└────────────┬─────────────┘         └──────────────────────────┘
             │
             ▼
Pool Return Receivable               Beza Management Fee Income
┌──────────────────────────┐         ┌──────────────────────────┐
│ DR: 150,000              │         │ CR: 15,000 (10% mgmt)   │
└────────────┬─────────────┘         └──────────────────────────┘
             │
             ▼
Savings Sub-Wallet A        Savings Sub-Wallet B       Savings Sub-Wallet C
┌─────────────────────┐    ┌─────────────────────┐    ┌─────────────────────┐
│ CR: 27,000          │    │ CR: 6,750           │    │ CR: 94,500          │
│ Balance: 1,277,000  │    │ Balance: 5,006,750  │    │ Balance: 35,094,500 │
└─────────────────────┘    └─────────────────────┘    └─────────────────────┘
```

## Daily Settlement Summary

```
End of Day: Savings Ledger Snapshot

Assets:
  User Main Wallets (SYP)                   Sum of individual main wallet balances
  Savings Sub-Wallets (SYP)                 Sum of all goal current_amounts
  Sharia-Compliant Investment Pool          Total pooled savings invested
  Pool Return Receivable                    Accrued but undistributed returns

Liabilities:
  Pooled Savings Liability                  Total savings under management

Revenue (Period):
  Management Fee Income                     10% of pool returns
  Early Withdrawal Penalty Income           2% of early withdrawal amounts
```
