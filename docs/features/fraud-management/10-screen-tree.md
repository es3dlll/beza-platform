# Screen Tree — Fraud Management UI Screens

## Screen Map Overview

```
Fraud Management Portal
│
├── 🏠 Dashboard (Fraud Ops Home)
│   ├── 📊 KPIs Header
│   │   ├── Total Transactions Today
│   │   ├── Fraud Rate %
│   │   ├── False Positive Rate %
│   │   ├── Average Decision Time
│   │   └── Blocked Amount (SYP)
│   ├── ⏰ Real-time Alert Feed
│   │   ├── P0 Alerts (Critical, red)
│   │   ├── P1 Alerts (High, orange)
│   │   └── P2 Alerts (Medium, yellow)
│   ├── 📈 Trend Charts
│   │   ├── Fraud Rate 7-day
│   │   ├── Top Rules Triggered
│   │   └── Fraud by Type
│   └── 🔝 Quick Actions
│       ├── Search Transaction
│       ├── View Open Cases
│       └── Recent Appeals
│
├── 🔍 Transaction Detail
│   ├── 📄 Transaction Info
│   │   ├── Txn ID, Amount, Timestamp
│   │   ├── Sender, Recipient
│   │   ├── Channel, Device
│   │   └── Location
│   ├── ⚠️ Risk Section
│   │   ├── Overall Risk Score
│   │   ├── Individual Factor Scores
│   │   ├── Rules Triggered
│   │   └── ML Prediction Score
│   ├── 📜 Timeline
│   │   ├── Transaction Initiated
│   │   ├── Fraud Screening
│   │   ├── Decision Made
│   │   └── Actions Taken
│   └── 🔧 Actions
│       ├── Approve (if pending)
│       ├── Block
│       ├── Freeze Account
│       └── Create Case
│
├── 📋 Case Management
│   ├── 📑 Case List
│   │   ├── Filter by status, priority, type, date
│   │   ├── Search by case ID, user ID, txn ID
│   │   └── Sort by date, amount, score
│   ├── 📂 Case Detail
│   │   ├── Case ID, Status, Priority
│   │   ├── Fraud Type
│   │   ├── Involved Parties
│   │   ├── Transaction History
│   │   ├── Investigation Notes
│   │   └── Evidence Attachments
│   ├── 🛠️ Investigation Workbench
│   │   ├── User Profile (KYC, history, devices)
│   │   ├── Transaction Graph (visual)
│   │   ├── Device History
│   │   ├── Location Timeline
│   │   └── Network Analysis
│   └── 📮 Actions
│       ├── Confirm Fraud
│       ├── Mark False Positive
│       ├── Escalate to CBS
│       ├── Refer to Law Enforcement
│       ├── Restore Account
│       └── Approve Refund
│
├── ⚙️ Rule Engine Configuration
│   ├── 📋 Rule List
│   │   ├── Active Rules (with on/off toggle)
│   │   ├── Draft Rules
│   │   ├── Archived Rules
│   │   └── Rule Performance Stats
│   ├── ✏️ Rule Editor
│   │   ├── Rule Name & Description
│   │   ├── Conditions (AND/OR logic builder)
│   │   ├── Thresholds
│   │   ├── Action (flag, slow, block, freeze)
│   │   ├── Weight/Score Contribution
│   │   └── Time Activation (always, schedule)
│   └── 📊 Rule Testing
│       ├── Backtest against historical data
│       ├── Shadow Mode (log-only)
│       └── A/B Test Rules
│
├── 🔬 Model Management
│   ├── 📈 Model Performance
│   │   ├── AUC-ROC Chart
│   │   ├── Precision-Recall Curve
│   │   ├── Feature Importance
│   │   └── Drift Detection
│   ├── 🔄 Model Versions
│   │   ├── Current Model
│   │   ├── Previous Versions
│   │   └── Rollback Option
│   └── 🎯 Retraining
│       ├── Schedule (daily, weekly)
│       ├── Last Retrain Timestamp
│       ├── Training Data Window
│       └── Trigger Manual Retrain
│
├── 📊 Reports
│   ├── 📄 Fraud Statistics
│   │   ├── Daily/Weekly/Monthly/Quarterly
│   │   ├── By Fraud Type
│   │   ├── By Product (Wallet, Agent, Remittance, etc.)
│   │   ├── By Region
│   │   └── By Agent
│   ├── 📋 CBS Regulatory Report
│   │   ├── Auto-generated quarterly report
│   │   ├── SAR filing status
│   │   └── Download as PDF
│   └── 📉 Loss Analysis
│       ├── Fraud Loss by Month
│       ├── Recovery Rate
│       ├── Provision Calculation
│       └── Chargeback Summary
│
├── 👤 User Appeal Portal
│   ├── 📩 My Appeals
│   │   ├── Appeal Status
│   │   ├── Case Reference
│   │   └── Resolution Timeline
│   ├── ✍️ Submit Appeal
│   │   ├── Transaction Reference
│   │   ├── Reason for Appeal
│   │   └── Supporting Documents
│   └── 💬 Appeal Chat
│       ├── Support Agent Chat
│       └── Status Updates
│
├── 👥 Agent Fraud Monitoring
│   ├── 📊 Agent Fraud Dashboard
│   │   ├── Agent Fraud Rate
│   │   ├── Top Flagged Agents
│   │   ├── Float Variance Alerts
│   │   └── Dispute Volume
│   ├── 🔍 Agent Detail
│   │   ├── Agent Profile (KYC, location, history)
│   │   ├── Transaction Pattern
│   │   ├── Float History
│   │   └── Customer Complaints
│   └── ⚡ Real-time Agent Map
│       ├── Agent locations with risk colors
│       └── Click for agent detail
│
└── ⚙️ Settings
    ├── 🔔 Alert Configuration
    │   ├── Alert Channels (Slack, Email, SMS, Push)
    │   ├── Alert Thresholds
    │   └── On-call Schedule
    ├── 👤 Team Management
    │   ├── Fraud Team Members
    │   ├── Roles & Permissions
    │   └── Shift Schedule
    └── 🔗 Integration Status
        ├── Feature Integrations (Wallet, Agent, etc.)
        ├── CBS Reporting Connection
        └── ML Model Service Status
```

## Screen Priority

| Screen                 | Priority | Phase   | Rationale                   |
| ---------------------- | -------- | ------- | --------------------------- |
| Dashboard              | P0       | Phase 1 | Ops team needs central view |
| Transaction Detail     | P0       | Phase 1 | Core investigation unit     |
| Case Management        | P0       | Phase 1 | Investigation workflow      |
| Rule Engine Config     | P0       | Phase 1 | Rules must be configurable  |
| User Appeal Portal     | P1       | Phase 2 | User-facing feature         |
| Reports (CBS)          | P1       | Phase 2 | Regulatory requirements     |
| Agent Fraud Monitoring | P1       | Phase 2 | Agent-specific fraud        |
| Model Management       | P2       | Phase 2 | ML team needs               |
| Settings               | P2       | Phase 2 | Admin functions             |
| Real-time Map          | P3       | Phase 3 | Advanced visualization      |

## Navigation Flow

```
Dashboard
  ├─ Click alert → Transaction Detail
  ├─ Click case count → Case Management
  └─ Click chart → Reports

Transaction Detail
  ├─ Click "Create Case" → Case Management (new case)
  └─ Click rules → Rule Engine Config (view rule)

Case Management
  ├─ Click case → Case Detail
  │   ├─ Click user → User Profile
  │   ├─ Click txn → Transaction Detail
  │   └─ Investigate → Workbench
  └─ Actions → Status change, escalate

Rule Engine
  ├─ Click rule → Rule Editor
  └─ Test → Backtest results

Appeals
  └─ Click appeal → Case Management (linked case)
```
