# Government Collections Incident Response

## Incident Severity Levels

| Level | Definition | Response Time | Example |
|-------|-----------|---------------|---------|
| SEV-1 | Critical — ministry payments down | 5 min | All ministry APIs unreachable |
| SEV-2 | High — one ministry integration broken | 15 min | MoF API returning errors |
| SEV-3 | Medium — partial feature degraded | 1 hour | Receipt PDF generation slow |
| SEV-4 | Low — cosmetic / non-functional | Next business day | Translation typo in receipt |

## On-Call Rotation

```
Primary On-Call: Backend engineer (gov payments specialist)
Secondary On-Call: Infrastructure engineer
Escalation: Engineering Manager → CTO

Contact:
- PagerDuty / Opsgenie
- WhatsApp group: #gov-payments-oncall
- Ministry contacts: listed in biller config
```

## Incident Response Playbooks

### Playbook 1: Ministry API Down
```
Severity: SEV-1 or SEV-2 (depending on ministry)
Symptoms:
- ministry_api_up = 0
- Payment failures increasing
- ministry_api_error_total spiking

Immediate Actions (first 5 min):
1. Confirm alert from monitoring
2. Check ministry status page (if available)
3. Try manual API call from staging environment
4. Contact ministry technical contact (phone > email)
5. Set system to "queuing mode" — accept payments but don't send to ministry
   → Payments queued in government_transactions (status: pending_minitry)
   → User sees: "جاري تأكيد الدفع مع الوزارة" (no timeout shown)

After 15 min:
6. If ministry still down:
   → Switch to "hold mode" — stop accepting new payments for that ministry
   → Display: "نظام وزارة المالية غير متاح حالياً. يرجى المحاولة لاحقاً"
7. Notify users with queued payments via SMS

After 60 min:
8. Escalate to CTO
9. Prepare manual reconciliation process
10. Update status page

Resolution:
11. Ministry comes back online
12. Process queued payments + confirm with ministry
13. Run manual reconciliation
14. Post-mortem within 24 hours
```

### Playbook 2: Settlement Failure
```
Severity: SEV-1 (if >24h delay)
Symptoms:
- settlement_lag_hours > 24
- settlement_failure_total increasing
- Ministry complaining of non-receipt

Immediate Actions:
1. Verify settlement batch file exists and is correct
2. Check bank transfer status with Beza treasury
3. Confirm ministry bank account details haven't changed
4. Check SFTP/file transfer logs (for file-based settlements)

Resolution:
5. Re-upload settlement file
6. Re-initiate wire transfer if bank issue
7. Verify ministry received funds
8. Update settlement statuses
9. Run reconciliation to confirm match
```

### Playbook 3: Security Incident
```
Severity: SEV-1
Symptoms:
- Unauthorized access detected
- Ministry API credentials compromised
- Receipt forgery detected

Immediate Actions (first 5 min):
1. Isolate compromised component
2. Rotate all API keys and credentials
3. Revoke compromised access tokens
4. Block affected user accounts
5. Contact ministry security team

Investigation:
6. Full audit log review
7. Identify scope of compromise
8. Determine data accessed
9. Identify affected users/transactions

Notification:
10. Notify regulatory bodies (CBS, AML commission)
11. Notify affected users (if personal data impacted)
12. Prepare public statement if required

Recovery:
13. Patch vulnerability
14. Enhanced monitoring post-incident
15. Post-mortem within 48 hours
```

## Communication Templates

### Status Page Message
```json
{
  "status": "degraded_performance" | "major_outage" | "operational",
  "affected_services": ["tax", "passport"],
  "message_ar": "نظام وزارة المالية يواجه انقطاعاً مؤقتاً. يتم معالجة المدفوعات فور عودة الخدمة.",
  "message_en": "Ministry of Finance system is temporarily unavailable. Payments will be processed once the service is restored.",
  "last_updated": "2025-08-15T10:30:00Z",
  "estimated_recovery": "2025-08-15T11:30:00Z"
}
```

### User Notification (Push)
```json
{
  "title_ar": "تأخير في تأكيد الدفع",
  "body_ar": "نظام وزارة المالية غير متاح حالياً. تم حفظ دفعتك وستتم معالجتها تلقائياً.",
  "action": "open_receipt",
  "receipt_ref": "GOV-2025-0815-7823"
}
```

### Internal Communication
```json
{
  "channel": "#gov-payments-incidents",
  "message": "SEV-1: MOF API down since 10:00 UTC. 15 payments queued. Engineering investigating. Next update: 5 min.",
  "tags": ["@gov-oncall", "@backend", "@ministry"]
}
```

## Post-Mortem Template
```markdown
## Incident Post-Mortem

### Summary
- Date: 2025-08-15
- Duration: 45 minutes (10:00–10:45 UTC)
- Severity: SEV-2
- Ministry: Ministry of Finance (MOF)
- Impact: 15 payments queued, 0 lost

### Root Cause
MoF API certificate expired. Certificate was not renewed by ministry IT.

### Timeline
- 10:00: Alert — ministry_api_up = 0
- 10:02: On-call acknowledges
- 10:05: Ministry IT contacted
- 10:15: Queuing mode activated
- 10:40: Ministry issues temporary certificate
- 10:42: Queued payments processed
- 10:45: All payments confirmed, monitoring green

### Action Items
- [ ] MoF to automate certificate renewal (ministry responsibility)
- [ ] Beza to implement certificate expiry monitoring + alerting
- [ ] Beza to add "queuing mode" capacity test to monthly drill
- [ ] Add alternate ministry contact person

### Lessons Learned
- Certificate expiry is a recurring issue with MoF — need 30-day advance warning
- Queuing mode worked well — no payments lost
- Communication with ministry IT took too long — need direct phone number
```
