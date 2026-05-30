# Operations — Fraud Management Team

## Team Structure

### Fraud Operations Team

```
┌─────────────────────────────────────────────────────────────────────┐
│                    FRAUD OPERATIONS TEAM                             │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │               Head of Fraud Operations                       │   │
│  │           Reports to: CRO / CTO                              │   │
│  └──────────────────────────┬──────────────────────────────────┘   │
│                              │                                       │
│        ┌─────────────────────┼─────────────────────┐                │
│        ▼                     ▼                     ▼                  │
│  ┌─────────────┐     ┌──────────────┐     ┌──────────────┐         │
│  │ Fraud Team  │     │ Fraud Team   │     │ Fraud Team   │         │
│  │ Alpha       │     │ Beta         │     │ Gamma        │         │
│  │ 06:00-14:00 │     │ 14:00-22:00  │     │ 22:00-06:00  │         │
│  │ (4 analysts)│     │ (4 analysts) │     │ (2 analysts) │         │
│  └──────┬──────┘     └──────┬───────┘     └──────┬───────┘         │
│         │                   │                     │                  │
│         ▼                   ▼                     ▼                  │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │           Support Functions (Business Hours)                │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐  │   │
│  │  │Data Sci- │ │ML        │ │Compliance│ │Engineering   │  │   │
│  │  │entist (1)│ │Engineer  │ │Officer   │ │Support (1)  │  │   │
│  │  │          │ │(1)       │ │(1)       │ │             │  │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────────┘  │   │
│  └─────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

### Team Roles & Responsibilities

| Role                       | Count           | Responsibilities                                                 |
| -------------------------- | --------------- | ---------------------------------------------------------------- |
| Head of Fraud Operations   | 1               | Strategy, team management, CBS liaison, board reporting          |
| Senior Fraud Analyst       | 3 (1 per shift) | Escalation handling, complex investigations, team lead on shift  |
| Fraud Analyst              | 7 (3+3+1)       | Day-to-day case investigation, alerts, user appeals              |
| Data Scientist (Fraud)     | 1               | ML model management, feature engineering, performance monitoring |
| ML Engineer                | 1               | Model deployment, infrastructure, ONNX pipeline                  |
| Compliance Officer (Fraud) | 1               | SAR filing, CBS reporting, regulatory compliance                 |
| Engineering Support        | 1               | System health, rule deployment, on-call rotation                 |
| **Total**                  | **15**          |                                                                  |

## Shift Schedule

### 24/7 Coverage (Syria Time — UTC+3)

| Shift            | Time        | Team                | Analysts                                                                    | Coverage Rationale |
| ---------------- | ----------- | ------------------- | --------------------------------------------------------------------------- | ------------------ |
| Alpha (Morning)  | 06:00–14:00 | 4 analysts + senior | Peak hours: business transactions, morning activity, agent cash-in/out      |
| Beta (Afternoon) | 14:00–22:00 | 4 analysts + senior | Peak hours: afternoon activity, remittance processing, evening P2P          |
| Gamma (Night)    | 22:00–06:00 | 2 analysts          | Off-hours: lower volume, critical alerts only; focus on automated detection |

### Shift Handoff Protocol

```
HANDOFF CHECKLIST
─────────────────
☐ Open cases review: status of all active investigations
☐ P0/P1 alerts handed off: context, actions taken, next steps
☐ System health status: any incidents, degraded modes
☐ Rule/model changes: new deployments, shadow mode rules
☐ CBS notifications: any SARs filed or pending
☐ Known issues: ongoing attacks, false positive storms
☐ Priorities for next shift: what to focus on

Format: Slack #fraud-handoff channel + 5-min verbal handoff
```

## Case Management SLAs

### Priority Levels

| Priority | Definition                   | Examples                                                             | SLA (to decision) | Escalation              |
| -------- | ---------------------------- | -------------------------------------------------------------------- | ----------------- | ----------------------- |
| **P0**   | Active fraud in progress     | Account takeover, large transfer attempt, confirmed credential theft | 15 minutes        | Head of Fraud Ops + CTO |
| **P1**   | Confirmed fraud with loss    | Fraudulent transaction completed, agent fraud detected               | 1 hour            | Head of Fraud Ops       |
| **P2**   | Suspicious transaction       | Transaction flagged for review, user reported unusual activity       | 4 hours           | Senior Fraud Analyst    |
| **P3**   | User appeal / false positive | User disputes flagged transaction                                    | 30 minutes        | Fraud Analyst           |

### SLA Monitoring

| Metric                   | Target | Breach Consequence          |
| ------------------------ | ------ | --------------------------- |
| P0 resolved within 15min | 99%    | Escalate to Head of Ops     |
| P1 resolved within 1h    | 95%    | Escalate to Head of Ops     |
| P2 resolved within 4h    | 90%    | Weekly review               |
| P3 resolved within 30min | 98%    | Monthly review              |
| No case older than 24h   | 99.9%  | Auto-escalate to management |

## Investigation Playbooks

### Playbook 1: Account Takeover Investigation

```
┌─────────────────────────────────────────────────────────────────────┐
│ PLAYBOOK: ACCOUNT TAKEOVER (ATO)                                    │
│ Priority: P0 / P1                                                    │
│                                                                    │
│ STEPS:                                                              │
│ 1. CONFIRM: Contact user via registered phone                       │
│    □ Ask: "Did you just make a transaction of [amount]?"            │
│    □ If no → proceed with investigation                             │
│    □ If yes → mark as false positive, close case                    │
│                                                                    │
│ 2. SECURE: Freeze the affected account                              │
│    □ Freeze account immediately (within 1 min of confirmation)      │
│    □ Block all outgoing transactions                                │
│    □ Terminate all active sessions                                  │
│                                                                    │
│ 3. INVESTIGATE: Gather evidence                                     │
│    □ Check device log for recent logins                             │
│    □ Check if SIM was recently swapped (via telecom API)            │
│    □ Review recent failed login attempts                            │
│    □ Check if user received phishing SMS/call                       │
│    □ Review transaction history for unauthorized access window      │
│                                                                    │
│ 4. TRACE: Follow the money                                          │
│    □ Identify receiving accounts (mules)                            │
│    □ Freeze receiving accounts                                      │
│    □ Run transaction graph to find all connected accounts           │
│    □ Check if any funds already cashed out                          │
│                                                                    │
│ 5. RESOLVE: Determine outcome                                       │
│    □ Fraud confirmed → file SAR if > 1M SYP                         │
│    □ Funds recovered → return to victim account                     │
│    □ Funds lost → document for IFRS 9 provisioning                  │
│    □ Account restored → new PIN/credentials, register new device    │
│    □ User notified of outcome                                       │
│                                                                    │
│ 6. PREVENT: Update controls                                         │
│    □ Add device to fraud blacklist                                  │
│    □ Add IP/location to watchlist                                   │
│    □ Enhance ATO detection rules if pattern is new                  │
│    □ Recommend user enable 2FA                                      │
│    □ Flag user for enhanced monitoring (30 days)                    │
└─────────────────────────────────────────────────────────────────────┘
```

### Playbook 2: Agent Fraud Investigation

```
┌─────────────────────────────────────────────────────────────────────┐
│ PLAYBOOK: AGENT FRAUD                                               │
│ Priority: P1 / P2                                                    │
│                                                                    │
│ STEPS:                                                              │
│ 1. DETECT: Alert from fraud engine (AGT-012: float variance)        │
│    □ Check alert details: agent ID, variance amount, time window    │
│    □ Review agent's recent transaction history                      │
│    □ Compare reported float vs expected float                       │
│                                                                    │
│ 2. VERIFY: Contact agent's supervisor                               │
│    □ Ask supervisor to visit agent location                         │
│    □ Physically reconcile cash float                                │
│    □ Interview agent about variance                                 │
│                                                                    │
│ 3. INVESTIGATE: Deep dive                                           │
│    □ Check if fake transactions were created                        │
│    □ Review CCTV footage (if agent has camera)                      │
│    □ Check customer complaints against this agent                   │
│    □ Run agent transaction pattern analysis                         │
│    □ Check agent's personal Beza account for suspicious activity    │
│                                                                    │
│ 4. RESOLVE: Determine outcome                                       │
│    □ False alarm → adjust float monitoring threshold                │
│    □ Agent error → training, warning, recovery plan                 │
│    □ Intentional fraud → suspend agent, freeze accounts, legal      │
│                                                                    │
│ 5. REPORT: Document and escalate                                    │
│    □ File internal fraud report                                     │
│    □ If loss > 1M SYP → file SAR with CBS                           │
│    □ Update agent risk score                                        │
│    □ Blacklist agent if intentional fraud                           │
└─────────────────────────────────────────────────────────────────────┘
```

### Other Playbooks

| Fraud Type         | Priority | Key Steps                                                                 |
| ------------------ | -------- | ------------------------------------------------------------------------- |
| SIM Swap           | P0       | Freeze account, verify with user, check telecom API, restore original SIM |
| Phishing           | P1       | Take down phishing site, notify affected users, enhance URL filtering     |
| Social Engineering | P1       | Identify compromised credentials, freeze accounts, user education         |
| Mule Account Ring  | P0       | Graph analysis to identify all accounts, batch freeze, file SAR           |
| Merchant Collusion | P1       | Freeze merchant account, review transaction history, escalate             |
| Synthetic Identity | P2       | Cross-reference KYC documents, check duplicates, report to CBS            |
| Insider Fraud      | P0       | Isolate access, preserve evidence, HR investigation, legal                |

## Weekly Model Review

### Review Agenda

```
WEEKLY FRAUD MODEL REVIEW
Day: Every Monday, 10:00 Syria time
Attendees: Data Scientist, ML Engineer, Head of Fraud Ops, Senior Analysts

AGENDA:
1. Model Performance (10 min)
   ─ AUC-ROC: current vs previous week
   ─ Precision-Recall: false positive rate by segment
   ─ Drift detection: feature distribution changes

2. New Fraud Patterns (15 min)
   ─ Fraud cases this week: any new patterns?
   ─ Adversarial behavior: fraudsters adapting?
   ─ Rule enhancements needed?

3. False Positive Review (10 min)
   ─ Top FP patterns this week
   ─ User appeals: any systematic issue?
   ─ Rule tuning opportunities

4. Model Updates (10 min)
   ─ Pending model retrain?
   ─ New features to add?
   ─ Rule changes to deploy?

5. Action Items (5 min)
   ─ Assignments for next week
   ─ Model/rule changes to implement
```

## Training & Knowledge Management

| Training                     | Frequency       | Audience       | Content                                       |
| ---------------------------- | --------------- | -------------- | --------------------------------------------- |
| Fraud Detection Fundamentals | Onboarding      | New analysts   | Fraud types, tools, procedures                |
| Investigation Playbooks      | Quarterly       | All analysts   | Deep dive into each fraud playbook            |
| ML Model Understanding       | Quarterly       | All analysts   | How ML works, feature importance, limitations |
| CBS Regulatory Training      | Bi-annual       | All fraud team | SAR filing, AML compliance, reporting         |
| System Updates               | Per release     | All fraud team | New features, rule changes, UI updates        |
| Syria Fraud Landscape        | Annual          | All fraud team | Evolving fraud patterns in Syria              |
| Red Team Results             | After each test | All fraud team | Lessons from adversarial testing              |
