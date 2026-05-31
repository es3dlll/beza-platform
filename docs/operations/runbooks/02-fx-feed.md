# Runbook: FX Rate Feed Down

## Severity: P1

## Symptoms

- **Alerts:**
  - Grafana alert `fx_feed_stale` fires when no new FX rate ingested for > 15 minutes
  - PagerDuty: "FX_FEED_DOWN — CBS official rate not updated since {timestamp}"
  - Slack #fx-alerts: "⚠️ FX rate feed stale — using last known rate {rate} SYP/USD from {timestamp}"
  - Datadog metric `fx.cbs_fixing.age_seconds` exceeds 900

- **What users see:**
  - Mobile app displays: "سعر الصرف غير متاح حالياً — يُرجى المحاولة لاحقاً" (Exchange rate temporarily unavailable — please try later)
  - FX conversion screen shows last cached rate with warning: "آخر تحديث: {time}" (Last updated: {time})
  - Agent float valuation in USD fails — agents cannot deposit USD or receive USD-denominated transfers
  - Merchant settlement in USD defaults to last known rate with warning banner
  - CBS reporting system rejects FX data if rate is > 30 min old

- **What dashboards show:**
  - `https://grafana.beza-sy.com/d/fx` — FX feed status pane shows red "STALE" for CBS fixing
  - `https://grafana.beza-sy.com/d/fx` — Bloomberg feed status shows red if secondary feed also down
  - `https://metabase.beza-sy.com/dashboard/fx-monitor` — Rate age gauge > 15 min

## Immediate Actions (First 5 min)

1. **Acknowledge the alert:**
   ```
   pd acknowledge -i <incident_id>
   ```

2. **Check current FX rate feed status from all sources:**
   ```sql
   SELECT source, rate, rate_date, ingested_at, 
          EXTRACT(EPOCH FROM NOW() - ingested_at) as age_seconds
   FROM fx.rate_cache
   ORDER BY source;
   ```

3. **Check if CBS website is serving the daily fixing:**
   ```
   curl -s -o /dev/null -w "%{http_code}" https://www.cbs.gov.sy/fx-rates
   ```

4. **Check Bloomberg terminal connectivity:**
   ```
   curl -s https://bloomberg.beza-sy.internal/api/v1/status | jq .status
   ```

5. **Check Bemo Saudi Fransi quote desk API:**
   ```
   curl -s https://api.bemo-sy.com/fx/rates/latest \
     -H "Authorization: Bearer <bemo-api-key>" | jq '.rates[] | select(.pair=="USD/SYP")'
   ```

## Investigation Steps

1. **Open the FX monitoring dashboard:**
   - URL: `https://grafana.beza-sy.com/d/fx`
   - Identify which feed(s) are down: CBS, Bloomberg, Bemo, or all
   - Check rate age distribution per source in the "Feed Health" panel

2. **Check CBS FX rate publication schedule:**
   - CBS publishes daily fixing at 10:00 Syria time Sunday–Thursday
   - If before 10:00, this is expected — no action needed
   - If after 10:00, CBS may have delayed publication — call CBS FX desk: +963 11 245 8912

3. **Check FX ingestion service logs:**
   ```
   kubectl logs -n fx-ingestion -l app=cbs-fx-scraper --tail 100 --since=1h
   ```

4. **Validate Bloomberg terminal is logged in and connected:**
   - Physical check at Bemo office desk (Damascus, Baramkeh branch)
   - Or call Bemo Treasury Desk: Sami Daoud +963 11 236 3344

5. **Check network connectivity to CBS:**
   ```
   ping -n 3 www.cbs.gov.sy
   traceroute www.cbs.gov.sy
   ```

6. **Check last successful FX rate cache entry and age:**
   ```sql
   SELECT rate, rate_date, source, ingested_at 
   FROM fx.rate_log 
   WHERE source IN ('CBS_FIXING', 'BLOOMBERG', 'BEMO_QUOTE')
   ORDER BY ingested_at DESC 
   LIMIT 5;
   ```

## Resolution Steps

1. **If only CBS feed is down (most common — CBS website or API issue):**
   - Call CBS FX desk: +963 11 245 8912 (ask for Ali or Mohammed)
   - Request official fixing rate verbally, confirm in writing via email
   - Manually ingest the rate via admin tool:
     ```
     curl -X POST https://beza-api.internal/fx/rates/manual \
       -H "Authorization: Bearer <treasury-token>" \
       -d '{
         "source": "CBS_FIXING_MANUAL",
         "pair": "USD/SYP",
         "rate": <rate_from_cbs>,
         "rate_date": "YYYY-MM-DD",
         "confirmed_by": "<your_name>",
         "reason": "CBS feed down — manual entry per incident <incident_id>"
       }'
     ```

2. **If Bloomberg feed is down:**
   - Contact Bloomberg helpdesk: +44 20 7330 7500 (24h EMIA support)
   - Or reach Bemo IT team to restart Bloomberg terminal at Bemo branch
   - Fallback to CBS fixing rate only (no secondary cross-check)

3. **If Bemo quote API is down:**
   - Contact Bemo Treasury Desk: Sami Daoud +963 11 236 3344
   - Request price via phone or email
   - Or fallback to CBS rate + 20 pip spread (standard Bemo margin)

4. **If ALL feeds are down (catastrophic failure):**
   - Use last cached CBS fixing rate (acceptable for up to 2 hours per CBS guidelines)
   - Display "سعر الصرف متأخر" (Rate delayed) banner on all FX screens
   - After 2 hours, suspend USD-denominated services:
     ```sql
     UPDATE system.service_flags SET enabled = false WHERE service = 'FX_CONVERSION';
     ```
   - Notify customers via in-app notification:
     "نأسف، خدمة تحويل العملات غير متاحة حالياً بسبب انقطاع مصادر أسعار الصرف"
   - Escalate to CEO if outage > 4 hours

5. **Verify feed restored:**
   ```sql
   SELECT source, rate, ingested_at, 
          EXTRACT(EPOCH FROM NOW() - ingested_at) as age_seconds
   FROM fx.rate_cache
   WHERE ingested_at > NOW() - INTERVAL '5 minutes';
   ```
   - Check dashboard returns to green: `https://grafana.beza-sy.com/d/fx`
   - Re-enable FX services if suspended:
     ```sql
     UPDATE system.service_flags SET enabled = true WHERE service = 'FX_CONVERSION';
     ```

## Rollback Plan

- **If manually entered rate was incorrect:**
  1. Revert to last known CBS rate (from fx.rate_log):
     ```sql
     INSERT INTO fx.rate_cache (source, pair, rate, rate_date, ingested_at)
     SELECT 'FALLBACK', 'USD/SYP', rate, rate_date, NOW()
     FROM fx.rate_log
     WHERE source = 'CBS_FIXING' AND rate_date = CURRENT_DATE
     ORDER BY ingested_at DESC LIMIT 1;
     ```
  2. Correct the rate via admin API with proper value
  3. Run rate audit for any transactions that used wrong rate:
     ```sql
     SELECT t.id, t.amount_syp, t.amount_usd, t.rate_used
     FROM fx.transactions t
     WHERE t.created_at BETWEEN <wrong_rate_start> AND <wrong_rate_end>
     AND t.rate_source = 'CBS_FIXING_MANUAL';
     ```
  4. For each affected transaction, calculate differential and issue correction if needed

- **If FX services were suspended incorrectly:**
  1. Re-enable immediately:
     ```sql
     UPDATE system.service_flags SET enabled = true WHERE service = 'FX_CONVERSION';
     ```
  2. Send clear notification to product team

## Communication Template

**Initial Alert:**
```
🔴 FX RATE FEED DOWN
Time: {current_time} Syria Time
Affected feeds: {CBS/Bloomberg/Bemo/all}
Last good rate: {rate} SYP/USD at {timestamp}
Severity: P1
Impact: Auto-FX conversions paused. Manual rates available via treasury.
Incident ID: {incident_id}
```

**Update (15 min):**
```
🟡 FX FEED INCIDENT UPDATE
Time: {current_time}
Status: {CBS contacted / Bloomberg ticket filed / working on fix}
Rate source plan: {using last cached / Bemo phone quote / manual CBS}
Next update: 15 min
```

**Resolution:**
```
🟢 FX FEED INCIDENT RESOLVED
Time: {current_time}
Feeds restored: {CBS/Bloomberg/Bemo}
Rate verified: {rate} SYP/USD
Outage duration: {minutes} minutes
Root cause: {CBS website maintenance / Bloomberg terminal logout / Bemo API timeout}
```

**Arabic Stakeholder Message:**
```
عذراً منكم، توقف تحديث أسعار صرف العملات في الساعة {time}.
تم حل المشكلة بالتنسيق مع مصرف سورية المركزي {or other source}.
سعر الصرف المعتمد: {rate} ل.س لكل دولار أمريكي.
نعتذر عن أي إزعاج.
```

## Post-Mortem

- **Root cause analysis:**
  - Was this a CBS-side outage or Beza ingestion failure?
  - Was the fallback rate source used within acceptable SLA?
  - How many transactions were affected (if any)?
  - Was manual rate entry process followed correctly?

- **Data to collect:**
  - FX feed status logs for 24h preceding incident
  - CBS website availability logs
  - Scraper pod logs (kubectl logs)
  - Network connectivity trace
  - Manual rate entry audit trail
  - Any transactions with stale rates

- **Teams to involve:**
  - Engineering (rate ingestion reliability)
  - Treasury (rate fallback process review)
  - Compliance (CBS notification — if manual rate used > 30 min)
  - Product (customer experience during outage)
  - Data (FX feed monitoring improvements)

- **Follow-up actions:**
  - Implement redundant CBS FX scraper with different source (CBS API vs website)
  - Add automated Bemo API fallback on CBS failure
  - Set up WhatsApp/SMS alert to Treasury if both feeds down
  - Monthly FX feed failover drill (next: {next_month_date})
  - Review SLA with CBS for rate publication reliability
