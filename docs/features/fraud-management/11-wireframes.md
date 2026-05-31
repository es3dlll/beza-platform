# Wireframes — Fraud Management Screens

## Screen 1: Fraud Operations Dashboard

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ 🔒 BEZA FRAUD PLATFORM                                [Search] [Profile] 🔔 │
├──────────────────────────────────────────────────────────────────────────────┤
│ 📊 FRAUD DASHBOARD (Live)                                        [Auto-refresh 5s] │
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐       │
│ │ TXNS TODAY   │ │ FRAUD RATE   │ │ FP RATE      │ │ DECISION TIME│       │
│ │ 284,502      │ │ 0.08% ▼      │ │ 2.7% ▼       │ │ 87ms ⚡      │       │
│ │ vs 265,100   │ │ Target: 0.1% │ │ Target: 3%   │ │ Target: 200ms│       │
│ └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘       │
│ ┌──────────────┐ ┌─────────────────────────────────────────────────────────┐│
│ │ ⏰ ALERTS    │ │ 📈 FRAUD RATE (7-day)                                 ││
│ │ 🔴 P0: 2    │ │ ┌─────────────────────────────────────────────────────┐││
│ │ 🟠 P1: 5    │ │ │ ▁▃▄▆█▇▅▃▁▂▄▆█▇▆▅▄▃▂▁▂▃▄▅▆█▇▆▅▄▃▂▁  │││
│ │ 🟡 P2: 12   │ │ │ 0.15 ───────────────────────────────────────       │││
│ │ ──────────  │ │ │ 0.10 ───────────────────────────────────────       │││
│ │ P0: ATO on  │ │ │ 0.05 ───────────────────────────────────────       │││
│ │   user 8492 │ │ │ 0.00 ───────────────────────────────────────       │││
│ │ P0: Agent   │ │ │ Mon Tue Wed Thu Fri Sat Sun                         │││
│ │   fraud Idl.│ │ └─────────────────────────────────────────────────────┘││
│ │ P1: SIM     │ └─────────────────────────────────────────────────────────┘│
│ │   swap det. │ ┌─────────────────────────────────────────────────────────┐│
│ └──────────────┘ │ 🏆 TOP RULES TRIGGERED                               ││
│                   │ ┌─────────────────────────────────────────────────────┐││
│                   │ │ VEL-003 (High velocity)    ████████████ 34%       │││
│                   │ │ TAMT-001 (Amount spike)    ██████████ 28%         │││
│                   │ │ DEV-005 (New device)       ███████ 18%            │││
│                   │ │ LOC-002 (Location anomaly) ████ 12%               │││
│                   │ │ AGT-012 (Agent float)      ██ 8%                  │││
│                   │ └─────────────────────────────────────────────────────┘││
│                   └─────────────────────────────────────────────────────────┘│
│                                                                              │
│ 🔝 QUICK ACTIONS: [Search Txn] [Open Cases (18)] [Recent Appeals (7)]      │
├──────────────────────────────────────────────────────────────────────────────┤
│ Operations Status: 🟢 All systems normal                Fraud Team: 4 online │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Annotations:**

- Top bar: navigation + search + notifications
- KPI cards: real-time values with trend arrows and target comparison
- Alert feed: left panel showing P0/P1/P2 with clickable items
- Trend chart: 7-day fraud rate with target line
- Rule performance: bar chart of top rules hit rate
- Quick actions: common operations team tasks

---

## Screen 2: Transaction Detail with Risk Score

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ 🔍 TRANSACTION DETAIL                                            [Back] 🏠 │
├──────────────────────────────────────────────────────────────────────────────┤
│ ⚠️ Risk Score: 78/100 (MEDIUM-HIGH)                                         │
│ Decision: APPROVED WITH VERIFICATION                                        │
│ ┌────────────────┐ ┌─────────────────────────────────────────────────────┐  │
│ │ 📄 TRANSACTION  │ │ 📊 RISK FACTORS                                  │  │
│ │                 │ │ ┌────────────────────────────────────────────────┐│  │
│ │ ID: TXN-28492  │ │ │ Factor              Score  Weight  Detail    ││  │
│ │ Amount: 150,000 │ │ │──────────────────────────────────────────────││  │
│ │ SYP             │ │ │ New Device          85     25     Device  ││  │
│ │ Fee: 1,500 SYP  │ │ │                     /100   pts    "Samsung ││  │
│ │ Timestamp:      │ │ │                           S23" seen first  ││  │
│ │ 14 Mar 2025     │ │ │                           time on account  ││  │
│ │ 15:42:30        │ │ │──────────────────────────────────────────────││  │
│ │ Sender:         │ │ │ Amount Spike        72     20     3σ above ││  │
│ │ Layla K.        │ │ │                           user avg (50K)  ││  │
│ │ Wallet: 8201****│ │ │──────────────────────────────────────────────││  │
│ │ Recipient:      │ │ │ New Location        65     15     Aleppo   ││  │
│ │ Mohammed A.     │ │ │                           (user usually    ││  │
│ │ Wallet: 7712****│ │ │                           in Damascus)     ││  │
│ │ Channel: Mobile │ │ │──────────────────────────────────────────────││  │
│ │ App v2.4.1      │ │ │ ML Prediction       82     10     Model v12 ││  │
│ │ Device: Samsung │ │ │                           predicts fraud:  ││  │
│ │ S23, Android 14 │ │ │                           18% probability  ││  │
│ │ Location:       │ │ │──────────────────────────────────────────────││  │
│ │ Aleppo, Syria   │ │ │ Time Since Last     30     5      8 hours  ││  │
│ │ Network:        │ │ │ Txn                          since last txn ││  │
│ │ Syriatel 4G     │ │ └────────────────────────────────────────────────┘│  │
│ └────────────────┘ └─────────────────────────────────────────────────────┘  │
│                                                                              │
│ 📜 RULES TRIGGERED (3)                                                      │
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ Rule              | Threshold | Actual   | Action      | Weight        ││
│ │──────────────────────────────────────────────────────────────────────────││
│ │ DEV-001:New Device| N/A       | New dev  | FLAG        | +25           ││
│ │ TAMT-001:Amount   | 3σ        | 5.2σ     | SLOW        | +20           ││
│ │ LOC-002:New Loc   | N/A       | Aleppo   | FLAG        | +15           ││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│ 🔧 ACTIONS                                                                  │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌────────────────────┐  │
│ │ ✓ Approve    │ │ 🚫 Block     │ │ ❄️ Freeze    │ │ + Create Case     │  │
│ └──────────────┘ └──────────────┘ └──────────────┘ └────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Annotations:**

- Risk score prominently displayed with color coding
- Transaction details in left card
- Risk factor breakdown with individual scores and weights
- Rules triggered table with compare view
- Action buttons for operations team

---

## Screen 3: Case Management — Investigation Workbench

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ 📋 CASE FR-2025-5678: ACCOUNT TAKEOVER                        [Back 📋List]│
├──────────────────────────────────────────────────────────────────────────────┤
│ Status: 🔴 UNDER INVESTIGATION     Priority: P0     SLA: 15 min ⏱️ 8:42 │
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ CASE DETAILS                                                            ││
│ │ Case ID: FR-2025-5678    Opened: 14 Mar 2025 15:45    Assigned: Sarah O.││
│ │ Fraud Type: Account Takeover    Amount at Risk: 500,000 SYP            ││
│ │ Victim: Layla K. (wallet: 8201****)    Status: Frozen                   ││
│ │ Suspect: Wallet 7712**** (mule account)    Status: Frozen               ││
│ │ Description: Unauthorized transfer attempt from new device. User        ││
│ │ confirmed via phone that she did not initiate.                          ││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│ ┌──────────────┬──────────────────────────────────────────────────────────┐│
│ │ 📊 EVIDENCE  │ 🕵️ INVESTIGATION NOTES                                ││
│ │ ┌──────────┐ │ ┌────────────────────────────────────────────────────┐││
│ │ │✓IP Log   │ │ │ [15:48] Sarah: User confirmed via call — she did  │││
│ │ │✓Device ID│ │ │ not authorize transfer. PIN likely compromised.   │││
│ │ │✓Txn Hist │ │ │ [15:52] Sarah: Checking receiving account 7712 —  │││
│ │ │✓SIM Record│ │ │ new user, 2 days old, received 3 transfers total │││
│ │ │✓Call Log │ │ │ [15:58] Sarah: Receiving account linked to device │││
│ │ │┌Photos  │ │ │ seen in 5 other accounts (mule ring suspected)     │││
│ │ │└────────┘ │ │ [16:02] Sarah: Escalating to Law Enforcement Desk  │││
│ │ └──────────┘ │ └────────────────────────────────────────────────────┘││
│ ├──────────────┤ [Add Note...]                                       ││
│ │ 👤 USER       │                                                       ││
│ │ PROFILE      │ ┌────────────────────────────────────────────────────┐││
│ │ Name: Layla K│ │ 📊 TRANSACTION GRAPH                              │││
│ │ Phone: 0933- │ │                                                    │││
│ │  123-456     │ │     [Layla K.]──500K SYP──>[7712****]             │││
│ │ KYC: Level 2 │ │         │                                           │││
│ │ Joined: 6mo  │ │         │ 200K SYP         150K SYP                │││
│ │ Avg txn: 45K │ │         ├──[7713****]──────[7715****]              │││
│ │ Last 30 txns │ │         │                                           │││
│ │   ████▒▒░░░░ │ │         │ 300K SYP         100K SYP                │││
│ │ Devices: 2   │ │         └──[7716****]──────[7718****]              │││
│ │   (S22, S23) │ │                                                    │││
│ │ Locations:   │ │     └─────────────────────────────────────────────┘││
│ │   Damascus   │ └────────────────────────────────────────────────────┘│
│ └──────────────┘                                                       │
│                                                                         │
│ 🔧 ACTIONS: [Confirm Fraud] [False Positive] [Escalate 🔺] [Restore]  │
└─────────────────────────────────────────────────────────────────────────┘
```

**Annotations:**

- Case header with status, priority, SLA timer
- Left panel: Evidence checklist, user profile
- Right panel: Investigation notes (chronological), transaction graph
- Visual graph shows money flow and mule account connections

---

## Screen 4: Rule Engine Configuration

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ⚙️ RULE ENGINE                                   [Test Mode: ON] [New Rule]│
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ 🔍 Search rules...  Filter: [All ▾]  Status: [Active ▾]  Impact: [All]││
│ ├──────┬──────────┬──────────┬──────────┬────────┬────────┬───────────────┤│
│ │ Rule │ Category │ Threshold│ Score    │ Action │ Status │ Performance  ││
│ ├──────┼──────────┼──────────┼──────────┼────────┼────────┼───────────────┤│
│ │VEL-03│ Velocity │>10 txns/ │ +25 pts  │ 🚫Block│ 🟢On   │ Hit: 3.2%   ││
│ │      │          │ 30min    │          │        │        │ FP: 1.1%     ││
│ ├──────┼──────────┼──────────┼──────────┼────────┼────────┼───────────────┤│
│ │DEV-01│ Device   │ New dev  │ +25 pts  │ ⚠️Flag │ 🟢On   │ Hit: 1.8%   ││
│ │      │          │ +amt>3σ  │          │        │        │ FP: 4.5%     ││
│ ├──────┼──────────┼──────────┼──────────┼────────┼────────┼───────────────┤│
│ │SIM-01│ SIM Swap │SIM chg<2h│ +40 pts  │ 🚫Block│ 🟢On   │ Hit: 0.3%   ││
│ │      │          │ +remit   │          │        │        │ FP: 0.5%     ││
│ ├──────┼──────────┼──────────┼──────────┼────────┼────────┼───────────────┤│
│ │AGT-12│ Agent    │ Float var│ +30 pts  │ ⚠️Flag │ 🟡Draft│ (not active) ││
│ │      │ Fraud    │ >3σ      │          │        │        │              ││
│ ├──────┼──────────┼──────────┼──────────┼────────┼────────┼───────────────┤│
│ │ML-001│ ML Model │Score>85  │ N/A      │ 🚫Block│ 🟢On   │ AUC: 0.94   ││
│ │      │          │          │          │        │        │              ││
│ └──────┴──────────┴──────────┴──────────┴────────┴────────┴───────────────┘│
│                                                                              │
│ Rule Editor (VEL-003 selected)                                              │
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ Rule Name:  VEL-003 (High Velocity Detection)                         ││
│ │ Category:   Velocity                                                    ││
│ │ Description: Flags transactions when user exceeds N txns in M minutes  ││
│ │                                                                          ││
│ │ CONDITIONS (ALL must match)                                              ││
│ │ ┌────────────────────────────────────────────────────────┐             ││
│ │ │ # │ Field          │ Operator   │ Value    | AND/OR   │             ││
│ │ │ 1 │ txn_count_30m  │ >          │ 10       | —        │             ││
│ │ │ 2 │ user_risk_tier │ IN         │ [high,   | AND      │             ││
│ │   │   │              │            │ critical]│           │             ││
│ │   │ 3 │ amount        │ >          │ 50000    | AND      │             ││
│ │   └────────────────────────────────────────────────────────┘            ││
│ │                                                                          ││
│ │ ACTION: [Block Transaction ◈]  Score Contribution: [+25 pts]           ││
│ │                                                                          ││
│ │ ACTIVATION: [⏰ Always Active ▾]  [Test in Shadow Mode ☐]               ││
│ │                                                                          ││
│ │ ┌──────────┐ ┌──────────┐ ┌──────────────┐                              ││
│ │ │ 💾 Save  │ │ 🗑 Delete│ │ 📊 Backtest  │                              ││
│ │ └──────────┘ └──────────┘ └──────────────┘                              ││
│ └──────────────────────────────────────────────────────────────────────────┘│
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## Screen 5: User Appeal Portal

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ 🔒 Beza Security — Appeal Center                 [My Account ⚙️] [Logout] │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│ 📩 MY APPEALS                           [Submit New Appeal +]               │
│                                                                              │
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ Appeal ID │ Transaction │ Amount │ Date       │ Status      │ Actions  ││
│ ├───────────┼─────────────┼────────┼────────────┼─────────────┼─────────┤│
│ │ APP-10234 │ TXN-28492   │150,000 │14 Mar 2025 │ 🟢 Resolved │ [View]  ││
│ │           │             │ SYP    │ 15:42      │ (approved)  │         ││
│ ├───────────┼─────────────┼────────┼────────────┼─────────────┼─────────┤│
│ │ APP-10235 │ TXN-28501   │50,000  │14 Mar 2025 │ 🟡 In Review│ [View]  ││
│ │           │             │ SYP    │ 16:15      │ (est. 20min)│         ││
│ └───────────┴─────────────┴────────┴────────────┴─────────────┴─────────┘│
│                                                                              │
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ ✍️ SUBMIT NEW APPEAL                                                   ││
│ │                                                                          ││
│ │ Transaction ID:  [TXN-_____________]                                    ││
│ │                                                                          ││
│ │ Why was this transaction flagged? (from your perspective)               ││
│ │ ○ It was me — I just used a different device/location                   ││
│ │ ○ I was sending to a family member                                       ││
│ │ ○ This was a business transaction                                        ││
│ │ ○ I don't know — it looks like someone else tried to use my account     ││
│ │ ○ Other: [______________________________________]                       ││
│ │                                                                          ││
│ │ Additional Comments:                                                     ││
│ │ [________________________________________________________________]      ││
│ │ [________________________________________________________________]      ││
│ │                                                                          ││
│ │ Attach supporting documents (optional): [Choose File 📎]                ││
│ │                                                                          ││
│ │ ☐ I confirm this appeal is truthful                                     ││
│ │                                                                          ││
│ │ ┌────────────────────┐                                                   ││
│ │ │ 📨 Submit Appeal  │                                                   ││
│ │ └────────────────────┘                                                   ││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│ 📝 TIPS FOR QUICK RESOLUTION                                                │
│ • Include the transaction ID from your SMS receipt                          │
│ • If you were using a different phone, mention this                         │
│ • For agent transactions, include the agent name and location               │
│ • Appeals are typically resolved within 30 minutes                          │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## Screen 6: CBS Regulatory Report

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ 📊 CBS REGULATORY REPORT — Q1 2025                        [Download PDF 📄]│
├──────────────────────────────────────────────────────────────────────────────┤
│ Prepared for: Central Bank of Syria (CBS) — AML Compliance Division         │
│ Prepared by: BEZA PSP — Fraud Prevention Team                               │
│ Report Period: 01 Jan 2025 — 31 Mar 2025                                    │
├──────────────────────────────────────────────────────────────────────────────┤
│ 1. EXECUTIVE SUMMARY                                                        │
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ Total Transactions Screened:  25,638,402                                ││
│ │ Total Transaction Value:      1,274,583,000 SYP                         ││
│ │ Total Fraud Cases:            1,842                                     ││
│ │ Total Fraud Value:            2,183,000 SYP                             ││
│ │ Fraud Rate:                   0.17%                                     ││
│ │ Amount Recovered:             458,430 SYP (21% recovery rate)           ││
│ │ False Positive Rate:          2.8%                                      ││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│ 2. FRAUD BY TYPE                                                           ││
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ Fraud Type              │ Cases │ Value (SYP) │ % of Total │            ││
│ │─────────────────────────│───────│─────────────│────────────│            ││
│ │ Account Takeover        │ 642   │ 873,000     │ 40.0%      │            ││
│ │ Agent Fraud             │ 421   │ 542,000     │ 24.8%      │            ││
│ │ SIM Swap                │ 312   │ 418,000     │ 19.2%      │            ││
│ │ Social Engineering      │ 284   │ 215,000     │ 9.9%       │            ││
│ │ Phishing                │ 153   │ 112,000     │ 5.1%       │            ││
│ │ Other                   │ 30    │ 23,000      │ 1.0%       │            ││
│ │ TOTAL                   │ 1,842 │ 2,183,000   │ 100%       │            ││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│ 3. REGIONAL BREAKDOWN                                                      ││
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ Region      │ Transactions │ Fraud Cases │ Fraud Rate │ Top Type        ││
│ │─────────────│──────────────│─────────────│────────────│──────────────── ││
│ │ Damascus    │ 8,421,300   │ 421         │ 0.07%      │ Account Takeover││
│ │ Aleppo      │ 5,234,100   │ 523         │ 0.15%      │ Agent Fraud     ││
│ │ Homs        │ 3,112,400   │ 311         │ 0.14%      │ SIM Swap        ││
│ │ Coastal     │ 4,123,500   │ 287         │ 0.10%      │ Social Eng.     ││
│ │ Northeast   │ 2,847,102   │ 198         │ 0.09%      │ Agent Fraud     ││
│ │ Rural       │ 1,900,000   │ 102         │ 0.08%      │ Agent Fraud     ││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│ 4. REGULATORY REPORTING                                                    ││
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ SARs Filed:                    38                                       ││
│ │ SARs ≥ 1M SYP:                 2                                        ││
│ │ Law Enforcement Referrals:    4                                         ││
│ │ CBS Notifications (24h):       8                                        ││
│ │                                                               ──────  ││
│ │ ALL SARs filed within required timeframe ✓                               ││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│ 5. SYSTEM EFFECTIVENESS                                                    ││
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ Metric                │ Performance │ Target     │ Status               ││
│ │───────────────────────│─────────────│────────────│──────────────────────││
│ │ Fraud Rate            │ 0.17%       │ < 0.1%     │ 🟡 Approaching       ││
│ │ False Positive Rate   │ 2.8%        │ < 3%       │ 🟢 On track          ││
│ │ Avg Decision Time     │ 87ms        │ < 200ms    │ 🟢 Exceeding         ││
│ │ Recovery Rate         │ 21%         │ > 20%      │ 🟢 On track          ││
│ │ Model AUC             │ 0.94        │ > 0.90     │ 🟢 Exceeding         ││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│ Prepared by: [Risk Manager Name]     Approved by: [Compliance Officer Name] │
│ Date: 01 Apr 2025                                                           │
│ ┌───────────────────────────────────────────────────────────┐              │
│ │ [✓] Confirmed accurate and complete for CBS submission    │              │
│ └───────────────────────────────────────────────────────────┘              │
└──────────────────────────────────────────────────────────────────────────────┘
```
