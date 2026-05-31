# Open Finance User Journeys

## Journey 1: Developer Registration & First API Call
```
Step 1: Developer visits portal (developers.beza.com)
Step 2: Clicks "Register" — enters: email, company, phone
Step 3: Email verification sent → clicks link
Step 4: Dashboard shows: "مرحباً بك! ابدأ مع بيئة الاختبار"
Step 5: Sandbox API key auto-generated (sk_test_...)
Step 6: Quick start guide: "أرسل أول طلب"
Step 7: Developer copies example cURL from docs
Step 8: Makes first call: GET /api/v1/sandbox/balance
Step 9: Response shows simulated balance: 1,000,000 SYP
Step 10: Developer sees webhook inspector → sends test payment → sees event

Edge Cases:
  - Email already registered: "هذا البريد مسجل بالفعل، هل نسيت كلمة المرور؟"
  - API key exposed: developer can rotate immediately
  - Rate limit hit: "تم تجاوز الحد المسموح — قم بترقية خطتك"
```

## Journey 2: Payment Initiation (E-commerce Checkout)
```
Step 1: Customer selects "Pay with Beza" on e-commerce site
Step 2: Merchant's backend calls POST /api/v1/payments
Step 3: Beza returns payment_url — browser redirects to Beza
Step 4: Customer logs in with phone + PIN
Step 5: Customer sees: "دفع 25,000 ل.س لمتجر الأمل"
Step 6: Customer confirms — PIN entered
Step 7: Webhook sent to merchant: payment.completed
Step 8: Merchant processes order
Step 9: Customer redirected to merchant success page

Edge Cases:
  - Insufficient balance: "الرصيد غير كافٍ" → suggest other payment
  - Payment expired (15 min): webhook payment.expired
  - Duplicate idempotency key: returns same transaction
  - Customer cancels: webhook payment.cancelled
```

## Journey 3: NGO Bulk Disbursement
```
Step 1: NGO uploads CSV: phone_number, amount, reference
Step 2: POST /api/v1/payments/bulk — accepts batch up to 10K
Step 3: API validates: all phone numbers exist? total within balance?
Step 4: Returns batch_id: batch_ABC123
Step 5: System processes in background (100 txn/sec)
Step 6: Webhook sent per transaction: payment.completed / payment.failed
Step 7: NGO dashboard shows: "تم صرف 950 من أصل 1,000 مستفيد"
Step 8: Export CSV of results — ready for donor reporting

Edge Cases:
  - Beneficiary not on Beza: auto-create wallet? flag for review?
  - Partial failures: 980 success, 20 failed — retry failed
  - Insufficient balance for whole batch: reject entire batch
  - Duplicate reference numbers: detect and skip
```

## Journey 4: Webhook Integration
```
Step 1: Developer configures webhook endpoint in portal
Step 2: Signs payload secret with HMAC-SHA256
Step 3: Beza sends test event — developer verifies signature
Step 4: On every transaction, Beza POSTs to endpoint
Step 5: Developer's server returns 200 OK within 5 seconds
Step 6: If timeout/error: retry 3x with exponential backoff
Step 7: Failed deliveries visible in webhook log in portal
Step 8: Developer can manually retry from portal

Edge Cases:
  - Server down for 30 min: events queued, delivered on recovery
  - Invalid HMAC: developer debugged via webhook log
  - Payload too large: paginated for large events
  - Duplicate delivery: idempotent via event_id field
```
