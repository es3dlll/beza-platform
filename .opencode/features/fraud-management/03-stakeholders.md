# Stakeholders — Fraud Management Feature

## Stakeholder Map

| Stakeholder | Role | Interest | Pain Points | Success Criteria |
|-------------|------|----------|-------------|-----------------|
| **CFO / Finance Director** | Financial steward | Fraud loss, provisioning, insurance | Direct fraud loss hits P&L; IFRS 9 provisions for expected losses | Fraud rate < 0.1%; recovery rate > 20%; loss provisions reduced |
| **CTO / Head of Engineering** | System integrity owner | Architecture, performance, reliability | Real-time screening adds latency; ML model maintenance cost; false positives erode trust | Decision time < 200ms; system uptime 99.95%; false positive rate < 3% |
| **Compliance Officer (CBS Liaison)** | Regulatory gatekeeper | AML reporting, SAR filing, audit trail | CBS expects suspicious transaction monitoring; manual SAR filing is slow; non-compliance = fines/revocation | Automated SAR generation; 100% of reportable fraud reported within 24h |
| **Head of Operations** | Agent network manager | Agent fraud, customer disputes, field support | Agent collusion with fraudsters; agents circumvent float checks; disputes consume ops team time | Agent fraud detected within 1h; dispute resolution < 48h |
| **Risk Manager** | Fraud strategy owner | Fraud typologies, thresholds, model accuracy | Fraudsters evolve faster than rules; unseen attack vectors; need to balance security vs. friction | Model AUC > 0.95; fraud typology coverage > 90% |
| **Customer Support Lead** | User trust guardian | Fraud appeals, false positive handling, user education | Flagged users are angry; false positives cause churn; support team lacks fraud tools | Appeal resolution < 30min for false positives; user satisfaction > 4.5/5 on fraud interactions |
| **Product Manager (Wallet)** | Feature owner | User experience, adoption, retention | Fraud screening should be invisible; too much friction = users abandon; Syrian users have low digital trust | Fraud feature invisible for 97% of users (false positive rate) |
| **Agent Network Manager** | Field operations | Agent training, agent KPIs, agent disputes | Agents commit fraud (float theft, fake cash-ins); agents also complain about legitimate transactions being blocked | Agent fraud detection rate > 80%; agent false flag rate < 1% |
| **End User (Customer)** | Transaction participant | Fast, safe transactions | Wrongfully blocked transactions; account frozen with no explanation; funds stuck in investigation | Clear communication when flagged; instant appeal; funds released within 24h if false positive |
| **CBS (Central Bank of Syria)** | Regulator | Financial system integrity, AML compliance | Systemic fraud could undermine digital payment adoption; need transparency on fraud statistics | Quarterly fraud reports; material fraud reported within 24h; annual fraud audit |
| **AML Department (Beza)** | Internal AML team | STR filing, AML risk assessment, suspicious activity monitoring | Fraud cases may also be AML-relevant; need integration with AML/sanctions screening | Fraud + AML combined case review; single source of truth for investigations |
| **Diaspora Users** | Remittance senders abroad | Sending money to family safely | Remittance intercepted by fraudster; sender has limited recourse from abroad | Remittance fraud protection; clear refund policy for confirmed fraud |
| **Merchants** | Payment recipients | Payment acceptance, settlement | Customer disputes = chargebacks; merchant collusion risk | Merchant fraud scoring; fair dispute resolution |

## Stakeholder Priority Matrix

```
High Power / High Interest (Manage Closely)
├── CFO — Budget holder, fraud loss owner
├── CTO — System implementation owner
├── Compliance Officer — Regulatory risk
├── Head of Operations — Day-to-day fraud operations
└── Risk Manager — Fraud strategy

High Power / Low Interest (Keep Satisfied)
├── CBS — Regulator (periodic reporting sufficient)
├── Agent Network Manager — Needs fraud tools, not meetings
├── Product Manager — Needs fraud to be invisible
└── AML Department — Integration handoff

Low Power / High Interest (Keep Informed)
├── Customer Support Lead — Needs training, tools, SLAs
├── End Users — Need transparent appeals process
├── Diaspora Users — Need remittance protection
└── Merchants — Need dispute fairness

Low Power / Low Interest (Monitor)
└── General public — Indirect impact (systemic trust)
```

## Stakeholder Communication Plan

| Stakeholder | Frequency | Channel | Content |
|-------------|-----------|---------|---------|
| CFO | Monthly | Email + Dashboard | Fraud loss P&L, recovery rate, provisioning impact |
| CTO | Weekly | Slack + Jira | System performance, incidents, ML model status |
| Compliance Officer | Weekly + On-demand | Email + Meetings | Material fraud, SAR status, CBS audit prep |
| Head of Operations | Daily | Slack + Dashboard | Fraud cases, agent flags, dispute queue |
| Risk Manager | Daily | Slack + Dashboard | Model metrics, rule performance, new patterns |
| Customer Support Lead | Weekly | Meeting + Playbook | Appeal stats, false positive trends, user feedback |
| CBS | Quarterly + Ad-hoc | Formal Report | Fraud statistics, SARs, fraud prevention effectiveness |
| End Users | Event-driven | SMS + In-app | Transaction blocked, investigation update, appeal result |

## RACI Matrix

| Activity | CFO | CTO | Compliance | Ops | Risk | CS | PM |
|----------|-----|-----|-----------|-----|------|----|----|
| Set fraud thresholds | C | C | C | I | R | I | A |
| Implement fraud rules | I | A | C | C | R | I | C |
| Investigate fraud case | I | I | C | R | C | C | I |
| Approve fraud refund | A | I | C | R | C | I | I |
| File SAR with CBS | I | I | R | C | C | I | I |
| Handle user appeal | I | I | I | C | C | R | I |
| Update ML model | I | A | I | I | R | I | C |
| Report fraud to CFO | C | I | C | I | R | I | C |

R=Responsible, A=Accountable, C=Consulted, I=Informed
