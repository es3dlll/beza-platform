# Government Collections Operations

## Operational Roles & Responsibilities

| Role | Team | Responsibilities |
|------|------|------------------|
| Government Payments Lead | Product | Ministry relationships, SLA management, new integrations |
| Reconciliation Analyst | Finance | Daily reconciliation, mismatch investigation, settlement monitoring |
| Ministry Integration Engineer | Engineering | Adapter development, ministry API maintenance, incident response |
| Compliance Officer | Legal | Regulatory reporting, AML monitoring, audit preparation |
| Ministry Relationship Manager | Partnerships | Contract negotiations, ministry onboarding, escalation point |
| Payment Operations Specialist | Operations | Manual settlement executions, refund processing, user disputes |

## Daily Operations Checklist

### Morning (08:00 – 10:00)
```
□ Check reconciliation results from previous night
  └─ If mismatches: investigate before 10:00
  └─ If unreconciled >48h: escalate to Finance
□ Verify all ministry APIs are up (monitoring dashboard)
□ Review failed payments from overnight queue
  └─ Retry eligible payments
  └─ Contact users for payments requiring manual action
□ Check settlement queue for each ministry
  └─ Verify pending settlements are within SLA
  └─ Execute manual settlements if auto-settlement failed
□ Review ministry contact channel for any messages
```

### Mid-Day (12:00 – 13:00)
```
□ Spot-check 5 random transactions per ministry
  └─ Verify: query → pay → receipt → settlement flow
□ Monitor payment volume against daily projection
  └─ If volume anomaly: check for promotions, deadlines, or issues
□ Check ministry bank account for incoming settlements (if applicable)
  └─ Verify amounts match Beza records
```

### Evening (16:00 – 17:00)
```
□ Prepare daily settlement batches (cut-off at 16:00)
  └─ Generate settlement reports per ministry
  └─ Queue for settlement execution (23:30 cron)
□ Check payment success rate for the day
  └─ Target: >97%
  └─ If below: investigate, adjust, document
□ Update ministry status board
  └─ Active integrations, known issues, upcoming changes
```

## Weekly Operations

### Monday
```
□ Weekly reconciliation report (Monday 10:00)
  └─ Previous week's reconciliation summary
  └─ Outstanding mismatches older than 7 days
  └─ Action plan for each unresolved item
□ Ministry check-in calls (rotation)
  └─ MoF: Monday 11:00
  └─ MoI: Monday 14:00
  └─ TRAF: Tuesday 10:00
```

### Wednesday
```
□ Payment volume forecasting for upcoming month
  └─ Based on: tax season, academic calendar, historical patterns
  └─ Share with treasury for settlement reserve planning
□ Review agent-assisted payment performance
  └─ Check agent commission calculations
  └─ Verify agent settlements
```

### Friday
```
□ Non-production day (limited operations)
  └─ Critical incident only
  └─ Automated reconciliation runs as scheduled
  └─ Batch settlements still execute
```

## Monthly Operations

### Month End
```
□ Run full monthly reconciliation (1st)
  └─ All ministries, all transactions
  └─ Generate monthly settlement report
  └─ Submit to Ministry of Finance
□ Monthly regulatory reporting
  └─ Transaction volume report (CBS)
  └─ AML suspicious transaction report (if any)
  └─ Tax collection summary (MoF)
□ Monthly billing to ministries for service fees
  └─ Generate invoice for each ministry
  └─ Track payment from ministry
□ Monthly incident review
  └─ SEV-1/2 incidents analysis
  └─ Post-mortem follow-up status
  └─ System improvement backlog prioritisation
```

### Quarterly
```
□ External audit preparation
  └─ Provide audit trail for sampled transactions
  └─ Reconciliation evidence
  └─ Settlement verification
□ Ministry contract review
  └─ SLA compliance assessment
  └─ Fee structure review
  └─ Contract renewal processing
□ DR/BCP test
  └─ Failover test for government payment module
  └─ Ministry API fallback test
  └─ Manual settlement process test
```

## Ministry Communication Protocols

### Regular Channels
| Ministry | Channel | Frequency | Contacts |
|----------|---------|-----------|----------|
| Ministry of Finance | Phone + Email | Daily reconciliation | finance_ops@mof.sy, +963 11 123 4567 |
| Ministry of Interior | WhatsApp + Email | As needed | moi_api@moi.sy, +963 11 234 5678 |
| Traffic Directorate | Portal + SFTP | Weekly | traffic_it@traf.sy |
| Damascus University | API + Phone | Real-time | bursar@damascusuniv.edu.sy |
| Ministry of Justice | Fax + File | Monthly (legacy) | courts_finance@moj.sy |

### Escalation Matrix
```
Issue Type          First Contact          Escalation 1          Escalation 2
──────────          ─────────────          ───────────          ───────────
API/Technical       Ministry IT helpdesk   Ministry IT Director  Minister's Office
Settlement/Finance  Ministry Finance       Ministry CFO         Deputy Minister
Contractual         Ministry Legal         Minister's Office    — 
Security            Ministry Security      National CSIRT       —
```

## Runbook: Ministry Integration Onboarding
```
New Ministry Integration Checklist:
□ 1. Legal agreement signed
  □ Payment terms defined
  □ Settlement schedule agreed
  □ Fee structure approved
  □ SLA documented
□ 2. Technical integration
  □ API specification reviewed
  □ Adapter developed (MinistryXxxAdapter.php)
  □ Authentication mechanism implemented
  □ Test environment access granted
  □ 3. End-to-end testing
  □ Happy path: query → pay → confirm → settle
  □ Error path: timeout, invalid reference, auth failure
  □ Receipt format validated by ministry
□ 4. Reconciliation process defined
  □ Matching criteria agreed (reference, amount, date)
  □ File format (if file-based) confirmed
  □ Expected variance tolerance established
□ 5. Go-live
  □ Production credentials configured
  □ Monitoring dashboards created
  □ On-call team briefed
  □ Ministry contact list distributed
  □ Launch communication prepared
□ 6. Post-launch (first 30 days)
  □ Daily manual reconciliation
  □ Monitoring alert tuning
  □ User feedback collection
  □ Performance baseline established
```
