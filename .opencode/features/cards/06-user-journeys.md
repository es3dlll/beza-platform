# Cards User Journeys

## Journey 1: Create & Use Virtual Card
```
Step 1: User opens app → taps "Cards" tab
Step 2: Taps "Create Card" → selects "Virtual Card"
Step 3: Chooses currency (SYP or USD)
Step 4: Sets initial spending limits (e.g., Online: 500,000 SYP daily)
Step 5: App shows card details: PAN (first 6 + last 4), expiry, CVV
Step 6: User taps "Add to Apple Pay" → card tokenized into wallet
Step 7: Card ready in < 30 seconds
Step 8: User shops on AliExpress → selects Beza card → enters CVV
Step 9: Push notification: "تم الدفع 125,000 ل.س لمتجر AliExpress"
Step 10: User checks card → sees transaction with merchant name + amount

Edge Cases:
  - KYC not sufficient: prompt to upgrade
  - Limits too low: "الحد الأدنى للبطاقة 50,000 ل.س"
  - Device doesn't support Apple Pay: skip wallet step
  - Card creation fails (BIN exhausted): try different BIN
  - User wants multiple cards: max per KYC level applies
```

## Journey 2: Freeze/Unfreeze Card
```
Step 1: User notices suspicious charge on card
Step 2: Opens app → Cards → Select card → "تجميد البطاقة"
Step 3: Confirms with PIN
Step 4: Card status changes to "Frozen" immediately
Step 5: Push + SMS: "تم تجميد بطاقتك رقم 1234"
Step 6: Any further auth requests get declined
Step 7: User reports transaction → support investigates
Step 8: After resolution, user taps "إلغاء التجميد"
Step 9: Card active again in < 5 seconds

Edge Cases:
  - Card already frozen: option to unfreeze only
  - Card linked to subscriptions: pending payments will fail
  - Multiple cards frozen at once: bulk freeze option
  - Frozen card used for ATM: machine will retain card
```

## Journey 3: ATM Withdrawal (Physical Card)
```
Step 1: User receives physical card at Beza agent
Step 2: Goes to ATM → inserts card
Step 3: ATM reads chip → prompts for PIN
Step 4: User enters 6-digit PIN
Step 5: Selects "سحب نقدي" → enters amount: 50,000 SYP
Step 6: Beza card processor: checks limits, balance, fraud
Step 7: If approved: hold on wallet → dispense cash
Step 8: ATM dispenses 50,000 SYP
Step 9: SMS: "تم السحب 50,000 ل.س من الصراف - الرصيد: 450,000 ل.س"
Step 10: Card transaction appears in app immediately

Edge Cases:
  - Wrong PIN (3 attempts): card blocked for 24h
  - ATM out of cash: "عذراً، لا يوجد نقود كافية"
  - Daily ATM limit exceeded: "تم تجاوز حد السحب اليومي"
  - Insufficient balance: "الرصيد غير كافٍ"
  - Card damaged: chip not reading → use contactless
  - Network issue: transaction declined, no hold placed
```

## Journey 4: One-Time Virtual Card
```
Step 1: User wants to buy from unknown online store
Step 2: Opens app → Cards → "إنشاء بطاقة لمرة واحدة"
Step 3: Enters amount: 75,000 SYP (exact purchase amount)
Step 4: Card generated: PAN + CVV + expiry (valid 24h only)
Step 5: User enters card details on merchant site
Step 6: Transaction authorized for exactly 75,000 SYP
Step 7: After auth: card auto-destroyed (cannot be reused)
Step 8: Notification: "بطاقة الاستخدام الواحد: تم الدفع 75,000 ل.س"
Step 9: Refund scenario: refund goes back to main wallet

Edge Cases:
  - Amount mismatch: merchant tries to charge more → declined
  - Card not used within 24h: auto-expired, no charge
  - Multiple auth attempts: only first succeeds
  - Partial capture: remaining amount released after 7 days
```
