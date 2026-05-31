# Agent Network User Journeys

## Journey 1: Agent Registration and Activation
```
Step 1: Abu Mohammad hears about Beza from neighbor shopkeeper
Step 2: Calls Beza agent hotline — "بدي صير وكيل معتمد"  
Step 3: Field officer visits shop within 48 hours
Step 4: Officer explains commission model, takes documents:
  - هوية شخصية سارية (ID card)
  - سجل تجاري أو إثبات ملكية المحل
  - فاتورة كهرباء أو ماء (إثبات عنوان)
  - صورتان شخصيتان
Step 5: Officer takes photo of shop front + interior
Step 6: Documents uploaded to Beza system → agent status = "pending"
Step 7: Within 24 hours, background check completed
Step 8: Agent approved → SMS: "أهلاً بك في Beza! رقم وكيلك: 10234"
Step 9: Training session (1 hour at shop):
  - كيفية تشغيل التطبيق
  - محاكاة إيداع وسحب
  - التعامل مع الحالات الطارئة
  - شرح العمولات والأرباح
Step 10: POS device delivered (Samsung Galaxy Tab A9 + thermal printer)
Step 11: Agent deposits initial float: 500,000 SYP cash → 500,000 SYP digital float
Step 12: First login with phone number + PIN 123456 (must change)
Step 13: Status → "active" — ready for first customer

Edge Cases:
  - Documents incomplete: "يرجى إكمال المستندات الناقصة" → list missing items
  - Background check fail: automatic rejection + appeal process
  - Shop location too close to existing agent (<500m): require justification
  - Training fails (agent cannot use POS): second training session, then reassess
  - Float deposit cash counterfeit: agent bears loss (training covers detection)
  - Device damaged on delivery: replacement within 24h
  - Agent changes mind during process: partial documents kept for 90 days
```

## Journey 2: Cash-in (User Deposits Cash)
```
Step 1: Umm Khaled walks to Abu Mohammad's shop
Step 2: "بدي أودع فلوس بمحفظتي" — or agent asks "إيداع ولا سحب؟"
Step 3: Agent opens POS → taps "إيداع نقدي" (big green button)
Step 4: Agent enters customer phone number: 0961234567
Step 5: Customer receives SMS with verification code: 4821
Step 6: Customer tells agent the code, agent enters it
Step 7: Agent enters amount: 100,000 SYP
Step 8: Agent takes cash from customer, counts it
Step 9: POS shows confirmation: "تأكيد الإيداع: 100,000 ل.س"
Step 10: Agent taps "تأكيد"
Step 11: System processes:
  - Debit agent float: -100,000 SYP
  - Credit customer wallet: +100,000 SYP
  - Record commission: agent earns 500 SYP (0.5%)
Step 12: POS shows success screen with checkmark animation
Step 13: Receipt prints automatically:
  "Beza — إيداع نقدي
   المبلغ: 100,000 ل.س
   التاريخ: 01/06/2026 10:30
   رقم المعاملة: CI-20260601-87142
   شكراً لتعاملكم مع Beza"
Step 14: Agent hands receipt to customer
Step 15: SMS to customer: "تم إيداع 100,000 ل.س في محفظتك. الرصيد: 250,000 ل.س"

Edge Cases:
  - Customer wrong phone: show verification code via SMS (only correct phone receives)
  - Agent has insufficient float: "الرصيد غير كافٍ — الرجاء تعبئة الصندوق"
  - Amount exceeds agent daily cash-in limit: "تم تجاوز حد الإيداع اليومي"
  - Amount exceeds customer wallet max balance: "رصيد المحفظة سيتجاوز الحد المسموح"
  - Customer does not receive SMS: resend code option (up to 3x), then USSD fallback
  - Network failure: transaction queued offline, "سيتم الإرسال عند الاتصال"
  - Offline queue full (50 pending): "الرجاء الاتصال بالإنترنت لإتمام المعاملة"
  - Cash counterfeit detected: agent refuses, transaction cancelled
  - Customer disputes amount: agent shows receipt, system logs timestamp + location
  - Partial deposit: agent can accept minimum 5,000 SYP
  - Duplicate request: idempotency key prevents double processing
```

## Journey 3: Cash-out (User Withdraws Cash)
```
Step 1: Umm Khaled needs cash for market
Step 2: Goes to Abu Mohammad's shop
Step 3: "بدي أسحب فلوس" — agent taps "سحب نقدي" (big red button)
Step 4: Agent enters customer phone: 0961234567
Step 5: Customer receives SMS with code: 7392
Step 6: Agent enters code on POS
Step 7: Agent enters amount: 50,000 SYP
Step 8: POS shows: fee 750 SYP (1.5%), total deduction: 50,750 SYP
Step 9: Agent tells customer total deduction
Step 10: Customer confirms with PIN on POS (or via USSD)
Step 11: POS shows: "الرجاء مسح البصمة للمبالغ فوق 500,000 ل.س"
    (For <500K: PIN only → skip biometric)
Step 12: System processes:
  - Debit customer wallet: -50,750 SYP
  - Credit agent float: +50,000 SYP
  - Beza retains fee: 750 SYP
  - Commission accrues: agent earns 375 SYP (0.75%)
Step 13: Agent counts cash, hands 50,000 SYP to customer
Step 14: Receipt prints
Step 15: SMS: "تم سحب 50,000 ل.س من محفظتك. الرسوم: 750 ل.س. الرصيد: 199,250 ل.س"

Edge Cases:
  - Customer has insufficient balance: "الرصيد غير كافٍ — رصيدك الحالي: 30,000 ل.س"
  - Amount exceeds agent float: "الرصيد النقدي للوكيل غير كافٍ — أقصى مبلغ للسحب الآن: 200,000 ل.س"
  - Amount exceeds customer daily limit: "تم تجاوز حد السحب اليومي — باقي: 150,000 ل.س"
  - Customer forgets PIN: 3 attempts then block 30min, can reset at any agent
  - Agent does not have enough physical cash: partial cash-out (e.g., 30,000 instead of 50,000)
  - Customer cancels after PIN: transaction voided, no fee charged if within 60 seconds
  - Biometric fail for >500K: fallback to PIN + SMS OTP
  - Large cash-out (>2M SYP): requires agent to call Beza approval hotline
  - Customer is not Beza user: "المستخدم غير مسجل في Beza — الرجاء التسجيل أولاً"
```

## Journey 4: Float Management (Agent Top-up)
```
Step 1: Abu Mohammad gets alert: "رصيد الصندوق منخفض: 50,000 ل.س"
Step 2: Opens POS → taps "إدارة الصندوق"
Step 3: Sees current float: 50,000 SYP
Step 4: Taps "تعبئة الصندوق"
Step 5: Option A: تحويل من محفظة Beza
  - Enters amount: 500,000 SYP
  - Confirms with PIN
  - Amount transferred from agent's Beza wallet to float
  - Float balance now: 550,000 SYP
Step 6: Option B: إيداع نقدي (brings cash to Beza office or authorized hub)
  - Agent deposits physical cash at Beza hub
  - Hub operator credits agent float
  - SMS: "تم تعبئة صندوقك بمبلغ 500,000 ل.س. الرصيد: 1,000,000 ل.س"
Step 7: Option C: من وكيل آخر (agent-to-agent transfer — Gold+ tiers)
  - Agent selects nearby agent with surplus float
  - Requests transfer: 200,000 SYP
  - Other agent accepts on their POS
  - Float transferred instantly

Edge Cases:
  - Agent wallet insufficient for top-up (Option A): "الرصيد في محفظتك غير كافٍ"
  - Cash deposit at hub: verification delay (can take up to 2 hours if busy)
  - Agent-to-agent rejected: "الوكيل الآخر لم يقبل الطلب"
  - Top-up exceeds max float for tier: Bronze max 5M, Silver max 15M
  - Agent tries to withdraw float: float is non-withdrawable (settled via commission)
  - Negative float: impossible at system level (prevented by balance check)
  - SMS alert fails: in-app notification badge + POS dashboard warning
```
