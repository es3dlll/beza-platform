# Agent Network Financial Flows

## Flow 1: Cash-in (User Deposits Cash)

### Participants
- **Customer**: Umm Khaled
- **Agent**: Abu Mohammad (BZ-10234, Bronze tier)
- **Beza Platform**: Processes transaction

### Amounts
- Customer hands over: 100,000 SYP cash
- Agent commission: 500 SYP (0.5% for Bronze tier)
- Customer wallet credited: 100,000 SYP
- Agent float debited: 100,000 SYP
- Customer cash-in fee: 0 SYP (free)

### Step-by-Step
```
Step 1: Customer Verification
  System: Send SMS code to customer phone +963912345678
  Customer: Reads code "4821" to agent
  Agent: Enters code on POS → Code verified ✓

Step 2: Authorization
  Check: Agent 10234 is active ✓
  Check: Agent float = 1,000,000 ≥ 100,000 ✓
  Check: Agent daily cash-in limit (Bronze: 5M, used 4.5M + 0.1M = 4.6M ✓)
  Check: Customer wallet max balance (current 150K + 100K = 250K < 10M ✓)

Step 3: Financial Execution
  DR Agent Float (Agent 10234)             100,000 SYP
  CR Customer Wallet (Umm Khaled)          100,000 SYP
  -- Customer credited, agent float reduced

  CR Agent Commission Payable (Agent 10234)     500 SYP
  DR Commission Expense                         500 SYP
  -- Commission accrued for this transaction

Step 4: Post-Transaction State
  Agent Float:    1,000,000 → 900,000 SYP
  Customer Wallet: 150,000 → 250,000 SYP
  Agent Pending Commission: +500 SYP
```

### Sequence Diagram
```
Customer               Agent POS              Beza API              CFE Ledger
    │                     │                      │                      │
    │── Hand cash ───────>│                      │                      │
    │                     │── POST /cash-in ────>│                      │
    │                     │   {amount: 100000}   │                      │
    │                     │                      │── Verify agent ─────>│
    │                     │                      │<── Agent OK ────────│
    │                     │                      │── Verify float ─────>│
    │                     │                      │<── Float OK ────────│
    │                     │                      │                      │
    │                     │                      │── DR Float ─────────>│
    │                     │                      │── CR Customer ──────>│
    │                     │                      │── CR Commission ────>│
    │                     │                      │                      │
    │<── Receipt ─────────│<── 200 OK ──────────│                      │
    │                     │   {txn_id, receipt}  │                      │
    │                     │                      │── emit(CashIn) ─────>│
    │<── SMS ─────────────│                      │                      │
    │  "تم إيداع 100,000" │                      │                      │
```

## Flow 2: Cash-out (User Withdraws Cash)

### Participants
- **Customer**: Umm Khaled
- **Agent**: Abu Mohammad (BZ-10234, Bronze tier)
- **Beza Platform**: Processes transaction

### Amounts
- Customer receives: 50,000 SYP cash
- Customer fee: 750 SYP (1.5%)
- Customer total deducted: 50,750 SYP
- Agent commission: 375 SYP (0.75% for Bronze tier)
- Agent float credited: 50,000 SYP
- Beza retains: 750 SYP (fee) - 375 SYP (commission) = 375 SYP

### Step-by-Step
```
Step 1: Customer Verification
  System: Send SMS code +963912345678 → "7392"
  Agent: Enters code → Verified ✓

Step 2: Transaction Initiation
  Agent enters: 50,000 SYP
  POS shows: Fee 750 SYP, Total deduction: 50,750 SYP
  Customer confirms with PIN → "1234" → PIN verified ✓

Step 3: Authorization
  Check: Agent 10234 active ✓
  Check: Agent has enough physical cash (float: 900K, cash-out: 50K ✓)
  Check: Agent daily cash-out limit (Bronze: 2M, used 1.5M + 50K = 1.55M ✓)
  Check: Customer wallet balance (250,000 ≥ 50,750 ✓)
  Check: Customer daily cash-out limit (500K, used 200K + 50K = 250K ✓)
  Check: Amount ≤ 500,000 SYP → PIN only (no biometric needed)

Step 4: Financial Execution
  DR Customer Wallet (Umm Khaled)            50,750 SYP
  CR Agent Float (Agent 10234)               50,000 SYP
  CR Fee Income Account                         750 SYP
  -- Customer debited (amount + fee), agent float credited, fee retained

  CR Agent Commission Payable (Agent 10234)     375 SYP
  DR Commission Expense                         375 SYP
  -- Commission accrued for agent

Step 5: Cash Handover
  Agent counts 50,000 SYP → hands to customer
  Agent taps "تم التسليم" on POS
  Transaction completed

Step 6: Post-Transaction State
  Customer Wallet: 250,000 → 199,250 SYP
  Agent Float:    900,000 → 950,000 SYP
  Agent Pending Commission: +375 SYP
  Beza Fee Income: +750 SYP
  Beza Commission Expense: -375 SYP
  Beza Net: +375 SYP
```

### Sequence Diagram
```
Customer               Agent POS              Beza API              CFE Ledger
    │                     │                      │                      │
    │── Request cash ────>│                      │                      │
    │                     │── POST /cash-out ───>│                      │
    │                     │   {amount: 50000,    │                      │
    │                     │    pin: "1234"}      │                      │
    │                     │                      │── Verify customer ──>│
    │                     │                      │── Verify PIN ───────>│
    │                     │                      │<── PIN OK ──────────│
    │                     │                      │── Check limits ─────>│
    │                     │                      │<── Limits OK ───────│
    │                     │                      │                      │
    │                     │                      │── DR Customer ──────>│
    │                     │                      │── CR Agent Float ───>│
    │                     │                      │── CR Fee Income ────>│
    │                     │                      │── CR Commission ────>│
    │                     │                      │                      │
    │<── "سلم 50,000" ───│<── 200 OK ──────────│                      │
    │                     │   {txn_id, receipt}  │                      │
    │── Cash handed ─────>│                      │                      │
    │                     │── POST /confirm ────>│                      │
    │<── Receipt ─────────│<── Confirmed ───────│                      │
    │                     │                      │── emit(CashOut) ────>│
    │<── SMS ─────────────│                      │                      │
    │  "تم سحب 50,000"    │                      │                      │
```

## Flow 3: Float Funding (Agent Top-Up)

### Scenario A: Top-up from Agent's Beza Wallet

#### Participants
- **Agent**: Abu Mohammad
- **He has**: 1,500,000 SYP in his personal Beza wallet
- **His float**: 50,000 SYP (critical)

#### Amounts
- Transfer amount: 500,000 SYP
- Source: Agent's personal Beza wallet
- Destination: Agent float account
- Fee: 0 SYP

#### Steps
```
Step 1: Agent opens POS → "إدارة الصندوق" → "تعبئة من المحفظة"
Step 2: POS shows: "رصيد محفظتك: 1,500,000 ل.س"
Step 3: Agent enters: 500,000 SYP
Step 4: POS shows: "سيتم خصم 500,000 ل.س من محفظتك"
Step 5: Agent confirms with PIN
Step 6: Financial Execution:
  DR Agent Wallet (Abu Mohammad)            500,000 SYP
  CR Agent Float (BZ-10234)                 500,000 SYP
Step 7: Post-Transaction:
  Agent Wallet: 1,500,000 → 1,000,000 SYP
  Agent Float:  50,000 → 550,000 SYP
Step 8: SMS: "تم تعبئة صندوقك بـ 500,000 ل.س. الرصيد: 550,000 ل.س"
```

### Scenario B: Cash Deposit at Beza Hub

#### Participants
- **Agent**: Abu Mohammad
- **Hub Operator**: Beza Damascus Hub

#### Amounts
- Deposit: 1,000,000 SYP cash
- Agent brings cash to Beza hub

#### Steps
```
Step 1: Agent visits Beza hub with 1,000,000 SYP cash
Step 2: Hub operator verifies cash (count + counterfeit check)
Step 3: Operator creates float funding on admin dashboard
Step 4: Financial Execution:
  DR Cash on Hand (Hub)                     1,000,000 SYP
  CR Agent Float (BZ-10234)                 1,000,000 SYP
Step 5: Agent float updated:
  550,000 → 1,550,000 SYP
Step 6: SMS notification to agent
Step 7: Verification delay: up to 2 hours during peak times
```

### Scenario C: Agent-to-Agent Float Transfer (Gold+ tiers)

#### Participants
- **Source Agent**: Abu Khaled (BZ-10235, Gold tier, surplus 3M float)
- **Target Agent**: Abu Mohammad (BZ-10234, Silver tier, deficit 50K float)

#### Amounts
- Transfer: 500,000 SYP
- Fee: 0 SYP

#### Steps
```
Step 1: Abu Mohammad opens POS → "تعبئة من وكيل آخر"
Step 2: Enters source agent code: BZ-10235
Step 3: Requests 500,000 SYP
Step 4: Abu Khaled receives notification on his POS:
  "طلب تحويل صندوق من وكيل BZ-10234 (بقالة أبو محمد) بمبلغ 500,000 ل.س"
Step 5: Abu Khaled accepts → confirms with PIN
Step 6: Financial Execution:
  DR Agent Float (BZ-10235)                 500,000 SYP
  CR Agent Float (BZ-10234)                 500,000 SYP
Step 7: Post-Transaction:
  Source Float:  3,000,000 → 2,500,000 SYP
  Target Float:  50,000 → 550,000 SYP
Step 8: SMS to both agents confirming transfer
```

## Flow 4: Commission Settlement (T+1 Batch)

### Participants
- **Agent**: Abu Mohammad
- **Beza**: Settles commissions daily at 03:00 AM

### Amounts (Example Day)
- Agent had 25 transactions yesterday
- Cash-in commission: 12 transactions × avg 500 SYP = 6,000 SYP
- Cash-out commission: 13 transactions × avg 375 SYP = 4,875 SYP
- Total commission earned: 10,875 SYP

#### Steps
```
Step 1: System query at 03:00 AM:
  SELECT SUM(commission) FROM agent_commissions
  WHERE agent_id = 10234 AND status = 'accrued' AND created_at < TODAY

Step 2: Create settlement batch SET-20260602

Step 3: Financial Execution (per agent):
  DR Commission Expense Account             10,875 SYP
  CR Agent Wallet (Abu Mohammad)            10,875 SYP

Step 4: Mark all commissions as settled

Step 5: Post-Settlement:
  Agent Pending Commission: 12,500 → 1,625 SYP
  Agent Wallet Balance: 1,000,000 → 1,010,875 SYP

Step 6: SMS: "تم تسوية عمولات يوم أمس: 10,875 ل.س. رصيد المحفظة: 1,010,875 ل.س"
```
