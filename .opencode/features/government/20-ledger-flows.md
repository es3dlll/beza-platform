# Government Collections Ledger Flows

## Chart of Accounts

### Government Collections GL Accounts
```
Account Code       Account Name                        Type         Normal Balance
────────────       ──────────────────────────          ──────────    ──────────────
1-100-100          User Main Wallet (SYP)              Asset         Debit
1-500-100          Beza Government Settlement Pool     Asset         Debit
2-500-100          Ministry Payable — MoF              Liability     Credit
2-500-200          Ministry Payable — MoI              Liability     Credit
2-500-300          Ministry Payable — Traffic          Liability     Credit
2-500-400          Ministry Payable — Universities     Liability     Credit
2-500-500          Ministry Payable — Courts           Liability     Credit
2-500-600          Ministry Payable — Municipalities   Liability     Credit
2-500-700          Ministry Payable — Civil Registry   Liability     Credit
3-300-100          Beza Management Fee Income          Revenue       Credit
3-300-200          Beza Penalty Income                 Revenue       Credit
3-300-300          Beza Late Payment Fee Income        Revenue       Credit
4-100-100          Beza Settlement Reserve             Equity        Credit
```

## Ledger Flow: Tax Payment Collection

```
User Main Wallet (SYP)              Beza Settlement Pool
┌──────────────────────────┐        ┌──────────────────────────┐
│ Balance: 500,000         │        │ Balance: 0               │
├──────────────────────────┤        ├──────────────────────────┤
│ CR: Tax 263,812          │────┐   │ DR: Tax collection        │
│ Balance: 236,188         │    │   │     262,500              │←────┘
└──────────────────────────┘    │   │ DR: Beza fee 1,312       │
                                │   │ Balance: 263,812         │
                                │   └──────────────────────────┘
                                │
                                │   Beza Management Fee Income
                                │   ┌──────────────────────────┐
                                │   │ CR: Fee 1,312            │←────┘
                                │   │ Balance: 1,312           │
                                │   └──────────────────────────┘
```

## Ledger Flow: Settlement to Ministry

```
Beza Settlement Pool               Ministry Payable — MoF
┌──────────────────────────┐       ┌──────────────────────────┐
│ Balance: 263,812         │       │ Balance: 0               │
├──────────────────────────┤       ├──────────────────────────┤
│ CR: Settlement 262,500   │───┐   │ DR: Settlement 262,500   │←────┘
│ Balance: 1,312           │   │   │ Balance: 262,500         │
└──────────────────────────┘   │   └──────────────────────────┘
                               │
                               │   (End of day: wire to Ministry)
                               │   DR: Ministry Payable — MoF 262,500
                               │   CR: Bank Account 262,500
```

## Ledger Flow: Multiple Collections Batch

```
End of Day Ministry Settlement (Example: 20 tax payments total 5,250,000 SYP)

Beza Settlement Pool               Ministry Payable — MoF
┌──────────────────────────┐       ┌──────────────────────────┐
│ Multiple collections     │       │ Multiple credits         │
│ aggregated:              │       │ Total: 5,250,000         │
│ 20 × payments            │       │                          │
│ Total collected:         │       │ Transfer to MoF bank:    │
│ 5,276,250 (incl fees)    │       │ DR: 5,250,000            │
│                          │       │ Balance: 0               │
│ Beza fee retained:       │       └──────────────────────────┘
│ 26,250 (0.5%)            │
│                          │       Beza Management Fee Income
│ Net to settle: 5,250,000 │       ┌──────────────────────────┐
│                          │       │ CR: 26,250 (aggregated)  │
└──────────────────────────┘       │ Balance: 27,562          │
                                   └──────────────────────────┘
```

## Ledger Flow: Passport Fee (Diaspora)

```
User Main Wallet (SYP)              Beza Settlement Pool
┌──────────────────────────┐        ┌──────────────────────────┐
│ Balance: 236,188         │        │ Balance: 1,312           │
├──────────────────────────┤        ├──────────────────────────┤
│ CR: Passport 75,375      │────┐   │ DR: Passport collection  │
│ Balance: 160,813         │    │   │     75,000               │←────┘
└──────────────────────────┘    │   │ DR: Beza fee 375         │
                                │   │ Balance: 76,687          │
                                │   └──────────────────────────┘
                                │
                                │   Ministry Payable — MoI
                                │   ┌──────────────────────────┐
                                │   │ CR: Settlement 75,000    │←────┘
                                │   │ Balance: 75,000          │
                                │   └──────────────────────────┘
```

## Ledger Flow: Refund / Reversal

```
Failed payment (ministry did not confirm within timeout)

Beza Settlement Pool               User Main Wallet (SYP)
┌──────────────────────────┐       ┌──────────────────────────┐
│ Balance: 76,687          │       │ Balance: 160,813          │
├──────────────────────────┤       ├──────────────────────────┤
│ CR: Refund 75,375        │───┐   │ DR: Refund 75,375        │←────┘
│ Balance: 1,312           │   │   │ Balance: 236,188          │
└──────────────────────────┘   │   └──────────────────────────┘
                               │   (No fee charged for failed)
                               │   Beza Management Fee Income
                               │   ┌──────────────────────────┐
                               │   │ DR: Reversal 375         │←────┘
                               │   │ Balance: 27,187          │
                               │   └──────────────────────────┘
```
