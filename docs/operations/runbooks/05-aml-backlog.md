# Runbook: AML Compliance Queue Backlog

## Severity: P1

## Symptoms

- **Alerts:**
  - Grafana alert `aml_queue_backlog` fires when pending screening items exceed 500 or age > 30 min
  - PagerDuty: "AML_QUEUE_BACKLOG — {count} items pending screening — oldest: {oldest_age} min"
  - Slack #compliance-alerts: "⚠️ AML queue backlog: {count} items. Oldest item {age} min. Threshold: 500 items / 30 min"
  - CBS anti-money laundering reporting trigger: any item > 60 min requires escalation
  - Datadog metric `aml.screening.queue_depth` exceeds 500

- **What users (internal) see:**
  - Compliance ops dashboard shows red "BACKLOG" warning
  - High-value transaction approvals delayed (manual review queue growing)
  - New customer onboarding stuck in "قيد التحقق" (Under verification) for > 1 hour
  - Large transfer (> 5M SYP) approvals queued — not processed within CBS-mandated 30 min
  - CBS AML portal shows pending SARS (Suspicious Activity Reports) overdue

- **What dashboards show:**
  - `https://grafana.beza-sy.com/d/aml` — Queue depth panel shows > 500 items
  - `https://grafana.beza-sy.com/d/aml-screening` — Age distribution histogram skewed right
  - `https://metabase.beza-sy.com/dashboard/aml-compliance` — Pending items table growing
  - `https://srm.cbs.gov.sy/aml/status` — CBS AML reporting compliance gauge showing yellow/red

## Immediate Actions (First 5 min)

1. **Acknowledge the alert:**
   ```
   pd acknowledge -i <incident_id>
   ```

2. **Assess queue size and growth rate:**
   ```sql
   SELECT 
       COUNT(*) as queue_depth,
       MIN(created_at) as oldest_item,
       MAX(created_at) as newest_item,
       EXTRACT(EPOCH FROM NOW() - MIN(created_at))/60 as oldest_age_minutes,
       COUNT(*) FILTER (WHERE status = 'PENDING_SCREENING') as pending_screening,
       COUNT(*) FILTER (WHERE status = 'PENDING_REVIEW') as pending_review,
       COUNT(*) FILTER (WHERE status = 'PENDING_APPROVAL') as pending_approval
   FROM aml.transaction_screening
   WHERE status IN ('PENDING_SCREENING', 'PENDING_REVIEW', 'PENDING_APPROVAL');
   ```

3. **Check AML screening service health:**
   ```
   kubectl get pods -n aml-screening
   kubectl logs -n aml-screening -l app=aml-scorer --tail 100 --since=30m | grep -E "error|warn|exception"
   ```

4. **Check if the AML rules engine is running:**
   ```
   curl -s https://beza-api.internal/aml/health | jq
   ```

5. **Check external screening provider status (if applicable):**
   ```
   curl -s -o /dev/null -w "%{http_code}" https://api.world-check.com/v2/health
   curl -s -o /dev/null -w "%{http_code}" https://api.kompany.com/v1/health
   ```

6. **Check CBS AML portal connectivity:**
   ```
   curl -s -o /dev/null -w "%{http_code}" https://srm.cbs.gov.sy/aml
   ```

## Investigation Steps

1. **Open AML monitoring dashboard:**
   - URL: `https://grafana.beza-sy.com/d/aml`
   - Check "Queue Depth by Status" panel — where is the bottleneck?
   - Check "Screening Latency" panel — are scores taking too long?
   - Check "External Provider Latency" panel — is World-Check/Kompany slow?
   - Check "Throughput" panel — items processed per minute vs incoming rate

2. **Identify the bottleneck:**
   ```sql
   -- Where are items getting stuck?
   SELECT 
       CASE 
           WHEN status = 'PENDING_SCREENING' THEN 'Screening Engine'
           WHEN status = 'PENDING_REVIEW' THEN 'Manual Review'
           WHEN status = 'PENDING_APPROVAL' THEN 'MLRO Approval'
       END as stage,
       COUNT(*) as item_count,
       AVG(EXTRACT(EPOCH FROM (NOW() - created_at))/60) as avg_wait_minutes,
       MAX(EXTRACT(EPOCH FROM (NOW() - created_at))/60) as max_wait_minutes
   FROM aml.transaction_screening
   WHERE status IN ('PENDING_SCREENING', 'PENDING_REVIEW', 'PENDING_APPROVAL')
   GROUP BY status
   ORDER BY avg_wait_minutes DESC;
   ```

3. **Check screening engine resource utilisation:**
   ```
   kubectl top pods -n aml-screening
   kubectl describe pod -n aml-screening -l app=aml-scorer
   ```

4. **Check for recent configuration changes that may have caused surge:**
   ```
   kubectl rollout history -n aml-screening deployment/aml-scorer
   kubectl logs -n aml-screening -l app=aml-config --tail 20
   ```

5. **Check for false positive surge (rules too sensitive):**
   ```sql
   SELECT 
       rule_id, rule_name,
       COUNT(*) as triggered_count,
       COUNT(*) FILTER (WHERE status = 'PENDING_REVIEW') as pending_review,
       ROUND(100.0 * COUNT(*) FILTER (WHERE status = 'PENDING_REVIEW') / COUNT(*), 2) as pending_pct
   FROM aml.transaction_screening s
   JOIN aml.screening_rules r ON s.rule_id = r.id
   WHERE s.created_at > NOW() - INTERVAL '2 hours'
   GROUP BY rule_id, rule_name
   ORDER BY triggered_count DESC
   LIMIT 20;
   ```

6. **Check CBS AML reporting queue:**
   ```
   curl -s -H "Authorization: Bearer <cbs-srm-token>" \
     https://srm.cbs.gov.sy/api/v1/aml/pending-reports | jq '.total_pending'
   ```

## Resolution Steps

1. **If screening engine is slow (CPU/memory saturation):**
   - Scale up screening engine workers:
     ```
     kubectl scale deployment -n aml-screening aml-scorer --replicas=5
     ```
   - Or increase resource limits:
     ```
     kubectl patch deployment -n aml-screening aml-scorer -p \
       '{"spec":{"template":{"spec":{"containers":[{"name":"aml-scorer","resources":{"limits":{"cpu":"4","memory":"8Gi"}}}]}}}}'
     ```

2. **If external screening provider (World-Check / Kompany) is slow or down:**
   - Enable local scoring fallback (cached rules only):
     ```
     curl -X POST https://beza-api.internal/aml/config \
       -H "Authorization: Bearer <compliance-token>" \
       -d '{"provider_fallback": "LOCAL_CACHE_ONLY", "reason": "Incident {incident_id}"}'
     ```
   - Flag all items screened via fallback for re-screening later:
     ```sql
     UPDATE aml.transaction_screening 
     SET needs_rescreening = true, rescreen_reason = 'External provider offline — incident {incident_id}'
     WHERE created_at > NOW() - INTERVAL '2 hours' AND provider = 'FALLBACK';
     ```

3. **If rules engine is generating excessive false positives:**
   - Temporarily adjust sensitivity of high-volume low-value rules:
     ```sql
     UPDATE aml.screening_rules 
     SET threshold_multipler = 2.0, temp_override = true, 
         overridden_by = '<compliance_officer>', override_reason = 'Temporary — incident {incident_id}'
     WHERE false_positive_rate > 0.95 AND triggered_count > 100;
     ```
   - Auto-approve low-risk items (amount < 500K SYP, known customer):
     ```sql
     UPDATE aml.transaction_screening 
     SET status = 'AUTO_APPROVED', 
         risk_score = 0, 
         approved_at = NOW(),
         approver_note = 'Auto-cleared — low risk, backlog incident {incident_id}'
     WHERE status = 'PENDING_SCREENING'
       AND customer_risk_tier = 'LOW'
       AND amount < 500000
       AND created_at < NOW() - INTERVAL '30 minutes';
     ```

4. **If manual review queue is the bottleneck (compliance team overwhelmed):**
   - Activate compliance on-call surge team: call MLRO + compliance officers
   - Priority-review high-value items (> 5M SYP) first (CBS 30-min mandate):
     ```sql
     SELECT id, customer_name, amount, created_at, EXTRACT(EPOCH FROM NOW() - created_at)/60 as age_minutes
     FROM aml.transaction_screening
     WHERE status = 'PENDING_REVIEW' AND amount > 5000000
     ORDER BY amount DESC;
     ```
   - Batch-approve known low-risk customers per CBS guidelines:
     ```sql
     UPDATE aml.transaction_screening
     SET status = 'APPROVED', approved_by = 'SURGE_TEAM', approved_at = NOW()
     WHERE status = 'PENDING_REVIEW'
       AND customer_id IN (SELECT id FROM customers WHERE risk_tier = 'LOW' AND tenure_days > 180)
       AND amount < 2000000;
     ```

5. **If CBS AML report submission is backlogged:**
   - Generate batch SAR (Suspicious Activity Report) for CBS submission:
     ```
     curl -X POST https://beza-api.internal/aml/generate-sar-batch \
       -H "Authorization: Bearer <compliance-token>" \
       -d '{"incident_id": "<incident_id>", "items": ["<item_ids>"]}'
     ```
   - Submit via CBS SRM portal:
     ```
     curl -X POST https://srm.cbs.gov.sy/api/v1/aml/submit-sar \
       -H "Authorization: Bearer <cbs-srm-token>" \
       -d @sar_batch_{incident_id}.json
     ```

6. **Verify queue is draining:**
   ```sql
   SELECT 
       COUNT(*) as remaining,
       MIN(created_at) as oldest,
       EXTRACT(EPOCH FROM NOW() - MIN(created_at))/60 as oldest_age_min
   FROM aml.transaction_screening
   WHERE status IN ('PENDING_SCREENING', 'PENDING_REVIEW', 'PENDING_APPROVAL');
   ```
   - Check dashboard returns to green: `https://grafana.beza-sy.com/d/aml`

7. **Restore normal configuration:**
   - Revert rule sensitivity multipliers:
     ```sql
     UPDATE aml.screening_rules 
     SET threshold_multipler = 1.0, temp_override = false, overridden_by = NULL
     WHERE temp_override = true;
     ```
   - Re-enable external providers:
     ```
     curl -X POST https://beza-api.internal/aml/config \
       -H "Authorization: Bearer <compliance-token>" \
       -d '{"provider_fallback": "PRIMARY_AND_FALLBACK", "reason": "Backlog incident resolved — {incident_id}"}'
     ```
   - Scale down workers if needed:
     ```
     kubectl scale deployment -n aml-screening aml-scorer --replicas=2
     ```

8. **Rescreen all items processed via fallback:**
   ```
   curl -X POST https://beza-api.internal/aml/rescreen-batch \
     -H "Authorization: Bearer <compliance-token>" \
     -d '{"incident_id": "<incident_id>", "ids": ["<fallback_item_ids>"]}'
   ```

## Rollback Plan

- **If auto-approvals were incorrectly applied to high-risk items:**
  1. Identify auto-approved items that should not have been:
     ```sql
     SELECT id, customer_id, customer_name, amount, created_at
     FROM aml.transaction_screening
     WHERE approved_by = 'SURGE_TEAM' AND customer_risk_tier IN ('HIGH', 'CRITICAL');
     ```
  2. Immediately freeze those transactions:
     ```sql
     UPDATE ledger.transactions
     SET hold_reason = 'AML review — incident {incident_id}', status = 'HOLD'
     WHERE id IN (<affected_transaction_ids>);
     ```
  3. Manually review each frozen transaction
  4. If confirmed high-risk, file SAR with CBS within 24h

- **If rules sensitivity was reduced too aggressively and suspicious transactions passed:**
  1. Identify transactions that were auto-cleared during relaxed rule period:
     ```sql
     SELECT * FROM aml.transaction_screening
     WHERE created_at BETWEEN <incident_start> AND <incident_end>
       AND status IN ('AUTO_APPROVED', 'APPROVED');
     ```
  2. Re-run full screening on those transactions with original rule sensitivity:
     ```
     curl -X POST https://beza-api.internal/aml/rescreen-period \
       -H "Authorization: Bearer <compliance-token>" \
       -d '{"start": "<incident_start>", "end": "<incident_end>", "sensitivity": "FULL"}'
     ```
  3. Report any newly flagged items to MLRO

- **If scale-up caused resource contention:**
  1. Immediately scale back to previous replicas:
     ```
     kubectl scale deployment -n aml-screening aml-scorer --replicas=2
     ```
  2. Investigate cluster resource pressure

## Communication Template

**Initial Alert:**
```
🔴 AML QUEUE BACKLOG — P1
Time: {current_time} Syria Time
Queue depth: {count} items
Oldest pending: {age} min
Bottleneck stage: {screening / review / approval}
CBS compliance deadline approaching: {minutes} until 60-min threshold
Incident ID: {incident_id}
```

**Update (15 min):**
```
🟡 AML QUEUE UPDATE
Time: {current_time}
Status: {scaling workers / surge team activated / rule adjustments applied}
Current queue: {current_count} ({cleared} cleared since alert)
Bottleneck: {current_bottleneck}
ETA to clear: {estimated_time}
```

**Resolution:**
```
🟢 AML QUEUE RESOLVED
Time: {current_time}
Queue cleared: {count} items processed
Root cause: {reason — e.g., "World-Check API latency spike caused screening backlog"}
Actions taken: {scaling, rule adjustments, surge review, rescreening queued}
CBS report: {submitted / no report needed}
```

**Arabic + English Stakeholder Message:**
```
English:
An AML screening backlog of {count} items was detected at {time}. The queue reached {max_age} minutes for the oldest item.
Issue resolved. All items screened and processed. {auto_approved} items auto-cleared (low risk), {manual_reviewed} manually reviewed.
CBS compliance maintained — no regulatory deadlines missed.
Rescreening of fallback-processed items scheduled.

Arabic:
تم رصد ازدحام في قائمة فحص مكافحة غسل الأموال بلغ {count} معاملة في الساعة {time}. 
وصلت أقدم معاملة إلى {max_age} دقيقة في الانتظار.
تم حل المشكلة. تم فحص ومعالجة جميع المعاملات.
عدد المعاملات التي تمت الموافقة عليها تلقائياً: {auto_approved} (منخفضة المخاطر)،
عدد المعاملات التي تمت مراجعتها يدوياً: {manual_reviewed}.
الحفاظ على الامتثال لمصرف سورية المركزي — لم يتم تفويت أي موعد تنظيمي.
ستتم إعادة فحص المعاملات التي تمت معالجتها عبر النظام الاحتياطي.
```

## Post-Mortem

- **Root cause analysis:**
  - Why did the queue build up? (volume spike, system failure, rule change)
  - Was it predictable? (e.g., end-of-month transaction surge, new regulation)
  - Did the auto-scaling/alerting thresholds work correctly?
  - Could the surge team have been activated sooner?

- **Data to collect:**
  - AML queue depth time series for 24h preceding incident
  - Screening engine CPU/memory metrics
  - External provider latency logs
  - Rule false positive rates before/during/after
  - Surge team activation time and response time
  - All monitoring dashboard snapshots

- **Teams to involve:**
  - Compliance (rule tuning, manual review process, CBS reporting)
  - Engineering (AML scorer performance, auto-scaling, external provider integration)
  - MLRO (quality control of surge approvals, CBS SAR decisions)
  - Data (monitoring and alert tuning, throughput forecasting)
  - Product (onboarding delays, customer communication)
  - Legal (CBS regulatory implications, reporting obligations)

- **Follow-up actions:**
  - Implement predictive auto-scaling for AML screening engine (horizonal pod autoscaler based on queue depth)
  - Add weekly AML queue capacity review with Compliance team
  - Tune rule false positive rates — review top 10 most-triggered rules monthly
  - Set up automated surge team paging when queue > 200 for > 15 min
  - Implement CBS 60-min deadline countdown timer on compliance dashboard
  - Monthly AML throughput stress test (simulate 2× normal volume)
  - Review and update CBS AML SAR submission procedures
  - Create compliance surge team rota (MLRO + 2 compliance officers on call)
