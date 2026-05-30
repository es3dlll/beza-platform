# FX Engine User Journeys

## Journey 1: Check Live Rates
```
Step 1: User opens Beza app → taps "Exchange" on home screen
Step 2: Shows FX rate card: SYP/USD, SYP/EUR, USD/EUR
Step 3: Each card shows: Bid (buy), Ask (sell), Beza Rate, Last Updated
Step 4: User taps SYP/USD card → expands to show rate sources:
         - CBS Official: 13,100 SYP/USD
         - Parallel Market: 14,550 SYP/USD
         - Black Market: 15,200 SYP/USD
Step 5: User sees "Beza Rate: 14,850 SYP/USD" (optimized from sources + spread)
Step 6: Swipe down to refresh rates → last updated refreshes
Step 7: User sees Sparkline chart showing 24h rate trend

Edge Cases:
   - All providers down: show "مصادر الأسعار غير متاحة" (Rate sources unavailable)
   - Some providers down: show available sources, highlight stale ones
   - Rate anomaly detected: show warning banner "تقلب غير طبيعي في السعر"
   - Cache stale (>30s): show amber indicator "قد يكون السعر قديماً"
```

## Journey 2: Convert Currency (SYP → USD)
```
Step 1: User on Exchange screen → taps "Convert"
Step 2: Selects source: SYP Wallet (balance: 10,000,000 SYP)
Step 3: Selects target: USD Wallet (balance: $0)
Step 4: Enters amount: 5,000,000 SYP
Step 5: System fetches live rate: 1 USD = 14,500 SYP (mid)
Step 6: Beza spread applied: 3% → Beza Rate: 1 USD = 14,935 SYP
Step 7: Preview:
         - You send: 5,000,000 SYP
         - Rate: 1 USD = 14,935 SYP
         - You receive: $334.78 USD
         - Fee (spread): 150,000 SYP (implied)
Step 8: User taps "Lock Rate" → rate locked for 30s
Step 9: Timer counts down: "Locked for 28s..." in green
Step 10: User confirms with PIN
Step 11: Conversion executes → 5,000,000 SYP debited, $334.78 USD credited
Step 12: Receipt shown: conversion ID, rate, amounts, timestamp, reference

Edge Cases:
   - Rate expires during confirmation: show "انتهت صلاحية السعر، يرجى المحاولة مرة أخرى"
   - Insufficient balance: show available balance, suggest lower amount
   - Daily conversion limit exceeded: show remaining limit, suggest KYC upgrade
   - Provider fails mid-conversion: failover to next provider, retry once
   - Duplicate request: idempotency key prevents double conversion
```

## Journey 3: Admin Rate Override
```
Step 1: Treasury operator logs into admin panel → FX Dashboard
Step 2: Sees all rate providers health status (green/red)
Step 3: Sees current Beza rates vs mid-market vs competitors
Step 4: Alert: "SYP/USD spread widened to 4.2% (exceeds 3% max)"
Step 5: Clicks alert → sees anomaly details (rate spike at 14:32 UTC)
Step 6: Operator overrides rate: Sets manual rate 14,600 SYP/USD
Step 7: System prompts: "Reason for override?" → operator enters "Black market spike, using parallel rate"
Step 8: Confirms with 2FA (TOTP)
Step 9: Rate updated → audit log: who, when, old rate, new rate, reason
Step 10: All users see the overridden rate for next 5 min (or until next provider fetch)

Edge Cases:
   - Operator enters rate outside max spread limits: blocked with explanation
   - Duplicate override within 5 min: warn "Override already active"
   - Override during active rate locks: existing locks honored at original rate
```

## Journey 4: Provider Failover (Automated)
```
Step 1: RateProviderService fetches from Provider A (Exchange House API) — timeout 2s
Step 2: Retry 1 (500ms): timeout again
Step 3: Retry 2 (500ms): timeout again
Step 4: Circuit breaker opens for Provider A (5 min cooldown)
Step 5: RateProviderService immediately switches to Provider B (Scraper)
Step 6: Provider B returns rate within 300ms
Step 7: Event emitted: RateProviderHealthChanged (Provider A: unhealthy, Provider B: active)
Step 8: Alert sent to #ops-fx Slack channel
Step 9: Next fetch cycle (15s): tries Provider A again (probe)
Step 10: If Provider A recovers → circuit breaker closes → restore as primary

Edge Cases:
   - All providers fail: serve last known cached rate with "stale" indicator
   - Partial fetch (some pairs succeed, some fail): serve partial rates
   - Cascade failover takes >1s: alert raised for performance degradation
```
