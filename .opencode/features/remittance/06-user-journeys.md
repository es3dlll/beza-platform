# Remittance User Journeys

## Journey 1: Local P2P SYP Transfer
```
Step 1: User opens Beza app → taps "Send"
Step 2: Searches contact "Ahmad" or enters phone +963912345678
Step 3: Enters amount: 50,000 SYP
Step 4: App shows fee: 250 SYP (0.5%), total: 50,250 SYP
Step 5: Adds note: "مصاريف البيت" (House expenses)
Step 6: Confirms with PIN (6 digits)
Step 7: Animation: money flying, success screen with receipt
Step 8: Recipient gets push notification + SMS
Step 9: SMS: "تم استلام 50,000 ل.س من أحمد. الرصيد: 75,000 ل.س"

Edge Cases:
  - Recipient not on Beza: SMS sent with download link + pickup code
  - Recipient has unregistered phone: funds held in suspense, SMS with code to claim
  - Insufficient balance: "الرصيد غير كافٍ" with suggested max amount
  - Daily limit reached: "تم تجاوز الحد اليومي" with KYC upgrade CTA
  - Self-transfer: blocked "لا يمكن التحويل إلى نفسك"
  - Duplicate request: idempotency key prevents double debit
  - Network timeout during transfer: funds held, retry with idempotency
```

## Journey 2: Diaspora Remittance (EUR→SYP)
```
Step 1: Khalid (Berlin) opens Beza app → taps "Send"
Step 2: Selects saved beneficiary "أم محمد" (Mom)
Step 3: Selects funding source: N26 bank account (SEPA direct debit)
Step 4: Enters amount: 300 EUR
Step 5: FX preview:
  - Beza rate: 1 EUR = 13,200 SYP (mid-market 13,420, spread 1.8%)
  - Fee: 1.5% = 4.50 EUR
  - Total debit: 304.50 EUR from N26
  - Recipient gets: 3,960,000 SYP
Step 6: Rate lock button: "تثبيت السعر لمدة 60 ثانية" (Lock rate for 60s)
Step 7: Khalid confirms with biometric (Face ID)
Step 8: Processing screen: "جارٍ تحويل العملة..." (Converting currency)
Step 9: Success: "تم التحويل! 3,960,000 ل.س في طريقها إلى أم محمد"
Step 10: Mom's phone: SMS "تم استلام 3,960,000 ل.س من خالد. الرصيد: 4,010,000 ل.س"
Step 11: Push notification to Khalid: "تم استلام المبلغ من قبل أم محمد"

Edge Cases:
  - FX rate expired: show new rate, ask for reconfirmation
  - SEPA direct debit fails: notify Khalid, hold FX rate 30 min
  - Recipient KYC 0: 3,960,000 > 200,000 max balance → split into cash-out
  - Daily send limit exceeded: show remaining, suggest KYC upgrade
  - Sanctions screening hit: automatic block, compliance notification
  - Source of funds check: if >$1,000, prompt for income proof
```

## Journey 3: Recurring Monthly Remittance
```
Step 1: Fatima (Stockholm) opens app → goes to "Recurring"
Step 2: Taps "Create Recurring Transfer"
Step 3: Selects beneficiary "والدتي" (My mother)
Step 4: Sets amount: 200 EUR
Step 5: Frequency: Monthly, 1st of each month
Step 6: Duration: Until cancelled
Step 7: FX preference: Lock rate at execution time (not at setup)
Step 8: Review summary:
  - Monthly: 200 EUR + 3 EUR fee (1.5%) = 203 EUR
  - Recipient: ~2,640,000 SYP (at current rate)
  - Next execution: 2026-07-01
Step 9: Confirms with PIN + biometric
Step 10: Recurring transfer created with confirmation screen
Step 11: SMS: "تم إنشاء تحويل شهري بقيمة 200 يورو إلى والدتك"

Edge Cases:
  - Insufficient funds on execution date: retry after 1h, 6h, 24h, then fail
  - FX rate drastically different (>10%): pause recurring, notify user
  - Beneficiary changes wallet: auto-update by phone number
  - Payment method expired (e.g., card): notify user 7 days before
  - KYC expires: pause recurring until re-verified
```

## Journey 4: Request Money (P2P)
```
Step 1: Ahmad opens app → taps "Request"
Step 2: Selects contact "Khalid" (brother in Germany)
Step 3: Enters amount: 100 EUR
Step 4: Adds note: "مساهمة في علاج الوالدة" (Contribution for mother's treatment)
Step 5: Taps "Send Request"
Step 6: Khalid gets notification: "أحمد يطلب 100 يورو منك"
Step 7: Khalid opens request → sees details → taps "Pay"
Step 8: Khalid confirms with biometric → transfer executes
Step 9: Both receive notifications

Edge Cases:
  - Request expires after 7 days
  - Request to non-Beza user: SMS with invite link
  - Partial payment: not supported (must pay full amount)
  - Request cancelled by requester: notification to requestee
  - Multiple requests to same person: visible in pending list
```
