# Wallet User Journeys

## Journey 1: First Wallet Funding (Agent Cash-in)
```
Step 1: User walks to Beza Agent shop
Step 2: "I want to deposit" or dials *123# → 3 (Cash-in)
Step 3: Agent enters phone number → user phone displays verification code
Step 4: User enters PIN on agent POS or USSD
Step 5: User hands cash to agent (50,000 SYP)
Step 6: Agent confirms amount on POS
Step 7: Both see confirmation screen
Step 8: SMS sent: "تم تعبئة محفظتك بـ 50,000 ل.س. الرصيد: 50,000 ل.س"
Step 9: User checks balance in app → shows 50,000 SYP

Edge Cases:
  - User enters wrong PIN: 3 attempts, then block 30 min
  - Agent runs out of cash: screen shows "Insufficient float"
  - Network failure: transaction recorded offline, sync when online
  - Dispute: transaction recorded with agent signature
  - Partial amount: agent can accept partial (min 5,000 SYP)
```

## Journey 2: P2P Transfer
```
Step 1: User opens app → taps "Send"
Step 2: Selects contact from phonebook or enters phone number
Step 3: Enters amount: 25,000 SYP
Step 4: App shows fee: 125 SYP (0.5%)
Step 5: Total: 25,125 SYP
Step 6: Adds note: "Rent for June"
Step 7: Confirms with PIN
Step 8: Animation: money flying from sender to recipient
Step 9: Recipient gets push notification + SMS
Step 10: Sender sees success screen with receipt

Edge Cases:
  - Recipient not on Beza: invite via SMS (link to download)
  - Insufficient balance: show error + suggest amount
  - Daily limit exceeded: show remaining limit, suggest upgrade KYC
  - Self-transfer: blocked with error message
  - Duplicate request: idempotency key prevents double debit
  - Recipient phone number has multiple accounts: show name selector
```

## Journey 3: Bill Payment
```
Step 1: User taps "Pay Bills" on home screen
Step 2: Selects biller category: Electricity
Step 3: Enters customer ID (24-digit smart meter number)
Step 4: App fetches bill: Amount 35,000 SYP, Due date: 2026-06-15
Step 5: Shows breakdown: Principal 33,500 + Late fee 1,500
Step 6: User confirms with PIN
Step 7: Bill is paid instantly
Step 8: Receipt: Digital + SMS
Step 9: Biller system confirms within 5 min

Edge Cases:
  - Bill not found: "No pending bills for this ID"
  - Bill already paid: show paid status + reference
  - Insufficient balance: suggest partial payment (if supported)
  - Wrong customer ID: validation against biller database
  - Biller system down: queue payment, retry 3x, notify if failed
```

## Journey 4: Check Balance via USSD
```
Step 1: User dials *123#
Step 2: Screen shows: 1. رصيدي (Balance) 2. تحويل (Transfer) 3. دفع (Pay)
Step 3: User sends 1
Step 4: Response: "رصيدك: 125,000 ل.س | دولار: $250"
Step 5: Shows: 1. العمليات الأخيرة (Recent) 2. الرجوع (Back)
Step 6: User sends 1
Step 7: Shows last 5 transactions with amounts

Edge Cases:
  - User has USD only wallet: show in USD
  - Network timeout: "الخدمة غير متاحة حالياً"
  - Invalid input: "إدخال غير صحيح، حاول مرة أخرى"
```
