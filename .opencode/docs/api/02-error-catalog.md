# Error Catalog — Beza Platform APIs

> Complete error taxonomy for ALL Beza platform APIs. Every service, mobile app client, and third-party integration MUST handle every error code listed here. Unknown/unlisted error codes MUST be treated as `SYS_INTERNAL_ERROR`.

**Owner:** API Governance Committee | **Version:** 2.0 | **Last updated:** 2026-05-30 (Sprints 15–19 added)

**See also:** [Error Catalog (numeric codes)](../shared/error-catalog/01-error-catalog.md) — the legacy numeric catalog. This document is the canonical descriptive-code catalog.

---

## Error Response Format

Every API error returns this exact JSON structure. The `details` object is domain-specific and populated per error code.

```json
{
  "error": {
    "code": "WALLET_INSUFFICIENT_FUNDS",
    "http_status": 422,
    "message": "Insufficient balance to complete transaction",
    "message_ar": "الرصيد غير كافٍ لإتمام العملية",
    "resolution": "Top up your wallet or reduce the transaction amount",
    "resolution_ar": "قم بتعبئة المحفظة أو تقليل مبلغ العملية",
    "details": {
      "current_balance": 150000,
      "required_amount": 500000,
      "shortfall": 350000,
      "currency": "SYP"
    }
  }
}
```

### Response Headers

| Header | Value | Description |
|--------|-------|-------------|
| `X-Error-Code` | `WALLET_INSUFFICIENT_FUNDS` | Machine-readable error code for programmatic handling |
| `X-Correlation-ID` | UUIDv4 | Trace ID linking request to backend logs |
| `Content-Language` | `ar` or `en` | Indicates which language the error body is in |
| `Retry-After` | seconds (on 429/503) | Suggested wait time before retry |

---

## Error Code Index

| Domain | Prefix | Total | HTTP Statuses Used |
|--------|--------|-------|-------------------|
| Wallet | `WALLET_*` | 19 | 400, 403, 404, 409, 422, 503 |
| Agent | `AGENT_*` | 12 | 400, 403, 404, 409, 422 |
| FX | `FX_*` | 9 | 400, 404, 409, 422, 503 |
| Remittance | `REMITTANCE_*` | 8 | 400, 403, 404, 422, 503 |
| Merchant | `MERCHANT_*` | 7 | 400, 403, 404, 409, 422 |
| Bill Payment | `BILL_*` | 8 | 400, 404, 409, 422, 502, 503 |
| Card | `CARD_*` | 6 | 400, 403, 404, 409, 422 |
| Loan | `LOAN_*` | 7 | 400, 403, 404, 409, 422 |
| Savings | `SAVINGS_*` | 5 | 400, 403, 404, 409 |
| Auth/Security | `AUTH_*` | 12 | 401, 403, 422, 429 |
| Compliance/KYC | `CMP_*` / `KYC_*` | 11 | 400, 403, 422 |
| Settlement | `SETTLEMENT_*` | 7 | 400, 409, 422, 500, 502 |
| Fraud | `FRAUD_*` | 5 | 403, 409 |
| System | `SYS_*` | 10 | 400, 409, 429, 500, 503 |
| **Total** | | **126** | |

---

## Wallet (WALLET_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `WALLET_INSUFFICIENT_FUNDS` | 422 | Insufficient balance to complete transaction | الرصيد غير كافٍ لإتمام العملية | Top up your wallet or reduce the transaction amount | قم بتعبئة المحفظة أو تقليل مبلغ العملية | Debit > available balance |
| `WALLET_NOT_FOUND` | 404 | Wallet not found | المحفظة غير موجودة | Verify the wallet ID and try again | تحقق من رقم المحفظة وحاول مرة أخرى | Invalid wallet_id, deleted wallet |
| `WALLET_FROZEN` | 403 | Wallet is frozen | المحفظة مجمدة | Contact support to unfreeze your wallet | اتصل بالدعم لإلغاء تجميد المحفظة | Action on frozen/suspended wallet |
| `WALLET_LIMIT_EXCEEDED` | 422 | Daily or monthly transaction limit exceeded | تجاوز الحد اليومي أو الشهري للمعاملات | Upgrade your KYC tier or wait for the limit to reset | قم بترقية مستوى التحقق أو انتظر حتى إعادة تعيين الحد | Transaction exceeds per-transaction, daily, or monthly limit |
| `WALLET_CURRENCY_MISMATCH` | 422 | Currency mismatch for this wallet | عملة غير متطابقة مع هذه المحفظة | Use a wallet that matches the transaction currency | استخدم محفظة تطابق عملة العملية | SYP transaction on USD wallet or vice versa |
| `WALLET_SAME_ACCOUNT` | 409 | Source and destination wallets are identical | محفظة المصدر والوجهة متماثلتان | Choose a different recipient wallet | اختر محفظة مستلم مختلفة | P2P transfer where sender = receiver |
| `WALLET_BELOW_MINIMUM` | 422 | Amount is below the minimum allowed | المبلغ أقل من الحد الأدنى المسموح به | Increase the amount to at least 100 SYP | قم بزيادة المبلغ إلى 100 ليرة على الأقل | Amount < minimum transaction threshold |
| `WALLET_ABOVE_MAXIMUM` | 422 | Amount exceeds the maximum allowed | المبلغ يتجاوز الحد الأقصى المسموح به | Reduce the amount or split into multiple transactions | قم بتقليل المبلغ أو تقسيمه إلى عدة معاملات | Amount > single transaction ceiling |
| `WALLET_RECIPIENT_INELIGIBLE` | 422 | Recipient wallet is not eligible to receive transfers | محفظة المستلم غير مؤهلة لاستقبال التحويلات | Recipient must complete KYC Tier 1 to receive funds | يجب على المستلم إكمال التحقق من المستوى الأول | Recipient KYC insufficient or account restricted |
| `WALLET_HOLD_EXPIRED` | 422 | Fund hold has expired before transaction completed | انتهت صلاحية تعليق الأموال قبل إتمام العملية | Re-initiate the transaction to obtain a fresh hold | أعد بدء العملية للحصول على تعليق أموال جديد | Hold TTL elapsed (bill payment, FX hold) |
| `WALLET_FEE_EXCEEDS_AMOUNT` | 422 | Transaction fee exceeds the transaction amount | رسوم العملية تتجاوز مبلغ العملية | Reduce fee or increase transaction amount | قم بتقليل الرسوم أو زيادة مبلغ العملية | Fee > amount when fee is percentage-based |
| `WALLET_DORMANT` | 403 | Wallet is dormant due to inactivity | المحفظة غير نشطة بسبب عدم الاستخدام | Perform a cash-in or contact support to reactivate | قم بعملية إيداع أو اتصل بالدعم لإعادة التنشيط | No activity on wallet for > 12 months |
| `WALLET_DAILY_COUNT_EXCEEDED` | 422 | Daily transaction count limit reached | تم تجاوز عدد المعاملات اليومي المسموح به | Wait until tomorrow for the counter to reset | انتظر حتى الغد لإعادة تعيين العداد | > 50 transactions per day (depends on KYC tier) |
| `WALLET_AMOUNT_PLUS_FEES_EXCEEDS_BALANCE` | 422 | Amount plus fees exceed available balance | المبلغ مضافاً إليه الرسوم يتجاوز الرصيد المتاح | Increase balance or reduce the amount plus fees | قم بزيادة الرصيد أو تقليل المبلغ والرسوم | Available balance < amount + all applicable fees |
| `WALLET_REFUND_PERIOD_EXPIRED` | 422 | Refund window has closed for this transaction | انتهت فترة استرجاع هذه العملية | Refunds accepted only within 7 days of transaction | يتم قبول الاسترجاع خلال 7 أيام فقط من تاريخ العملية | Refund attempted > 7 days after original transaction |
| `WALLET_TYPE_MISMATCH` | 400 | Wallet type does not support this operation | نوع المحفظة لا يدعم هذه العملية | Use a different wallet type for this operation | استخدم نوع محفظة مختلف لهذه العملية | E.g., trying to send from a merchant wallet |
| `WALLET_DEPOSIT_FAILED` | 422 | Deposit failed due to internal error | فشل الإيداع بسبب خطأ داخلي | Try again or contact support | حاول مرة أخرى أو اتصل بالدعم | Ledger/CFE posting failure during deposit |
| `WALLET_WITHDRAWAL_FAILED` | 422 | Withdrawal failed due to internal error | فشل السحب بسبب خطأ داخلي | Try again or contact support | حاول مرة أخرى أو اتصل بالدعم | Ledger/CFE posting failure during withdrawal |
| `WALLET_TRANSFER_FAILED` | 422 | Transfer failed due to internal error | فشل التحويل بسبب خطأ داخلي | Try again or contact support | حاول مرة أخرى أو اتصل بالدعم | Ledger/CFE posting failure during transfer |

## Agent (AGENT_*)

| Code | HTTP | Description | الوصف (AR) | Resolution | الحل (AR) | Trigger |
|------|------|-------------|------------|------------|------------|---------|
| `AGENT_NOT_FOUND` | 404 | Agent not found | الوكيل غير موجود | Verify the agent ID and try again | تحقق من رقم الوكيل وحاول مرة أخرى | Invalid or deleted agent ID |
| `AGENT_NOT_APPROVED` | 403 | Agent is not yet approved | الوكيل غير معتمد بعد | Wait for admin approval or contact support | انتظر موافقة المشرف أو اتصل بالدعم | Attempted cash-in/out on pending agent |
| `AGENT_LIMIT_EXCEEDED` | 422 | Agent daily transaction limit exceeded | تجاوز حد الوكيل اليومي للمعاملات | Reduce the amount or wait for limit reset | قلل المبلغ أو انتظر إعادة تعيين الحد | Daily cash-in/cash-out limit breached |
| `AGENT_FLOAT_INSUFFICIENT` | 422 | Agent float is insufficient for cash-out | الرصيد النقدي للوكيل غير كافٍ للسحب | Choose a different agent with sufficient float | اختر وكيلاً آخر برصيد كافٍ | Agent balance < cash-out amount |
| `CASH_IN_FAILED` | 422 | Cash-in failed due to internal error | فشل الإيداع النقدي بسبب خطأ داخلي | Try again or contact support | حاول مرة أخرى أو اتصل بالدعم | Wallet/CFE failure during cash-in |
| `CASH_OUT_FAILED` | 422 | Cash-out failed due to internal error | فشل السحب النقدي بسبب خطأ داخلي | Try again or contact support | حاول مرة أخرى أو اتصل بالدعم | Wallet/CFE failure during cash-out |

---

## FX (FX_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `FX_RATE_EXPIRED` | 422 | Rate quote has expired | انتهت صلاحية سعر الصرف | Fetch a new rate quote before confirming | احصل على سعر صرف جديد قبل التأكيد | Confirmation after 15-second rate lock window |
| `FX_RATE_UNAVAILABLE` | 503 | Exchange rate unavailable for this pair | سعر الصرف غير متاح لهذا الزوج | Try again later or use a different currency pair | حاول مرة أخرى لاحقاً أو استخدم زوج عملات آخر | CBS feed down, manual fallback unavailable |
| `FX_AMOUNT_EXCEEDS_LIMIT` | 422 | FX conversion amount exceeds daily limit | مبلغ تحويل العملات يتجاوز الحد اليومي | Reduce the amount or wait for daily reset | قم بتقليل المبلغ أو انتظر إعادة التعيين اليومي | User daily FX conversion limit exceeded |
| `FX_INVALID_PAIR` | 400 | Unsupported currency pair | زوج عملات غير مدعوم | Use a supported pair (SYP/USD in V1) | استخدم زوجاً مدعوماً (ل.س/د.أ في الإصدار الأول) | SYP/EUR not yet available, non-existent corridor |
| `FX_AMOUNT_BELOW_MINIMUM` | 422 | Amount below minimum for FX conversion | المبلغ أقل من الحد الأدنى لتحويل العملات | Increase the amount to at least equivalent of $10 USD | قم بزيادة المبلغ إلى ما يعادل 10 دولار أمريكي على الأقل | Minimum FX transaction threshold |
| `FX_RATE_STALE` | 503 | Rate feed is stale; using last known rate with warning | بيانات سعر الصرف قديمة؛ يتم استخدام آخر سعر معروف مع تحذير | Continue with caution or wait for feed recovery | تابع بحذر أو انتظر حتى استعادة التغذية | Rate feed > 5 minutes stale; system uses cached rate but flags risk |
| `FX_MARGIN_TOO_HIGH` | 422 | Calculated margin exceeds maximum allowed spread | هامش الربح المحسوب يتجاوز الحد الأقصى المسموح به | Contact support to adjust margin configuration | اتصل بالدعم لتعديل إعدادات هامش الربح | Manual rate override with spread > 5% |
| `FX_RATE_LOCK_CONTENTION` | 409 | Rate lock contention; another transaction consumed this quote | تعارض في قفل سعر الصرف؛ عملية أخرى استهلكت هذا السعر | Fetch a new rate quote | احصل على سعر صرف جديد | Race condition where two confirmations use same quote ID |
| `FX_NOT_FOUND` | 404 | FX resource not found | المورد غير موجود في نظام الصرف | Check the resource ID and try again | تحقق من معرف المورد وحاول مرة أخرى | Invalid conversion or quote ID |

---

## Remittance (REMITTANCE_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `REMITTANCE_CORRIDOR_UNAVAILABLE` | 503 | Remittance corridor is temporarily unavailable | ممر التحويل غير متاح حالياً | Try again later or use a different corridor | حاول مرة أخرى لاحقاً أو استخدم ممراً آخر | Partner (correspondent) system downtime |
| `REMITTANCE_SANCTIONS_HIT` | 403 | Sender or beneficiary matched a sanctions list | تطابق اسم المرسل أو المستفيد مع قائمة العقوبات | Contact compliance for further instructions | اتصل بقسم الامتثال لمزيد من التعليمات | OFAC, EU, UN, or CBS sanctions name match |
| `REMITTANCE_LIMIT_EXCEEDED` | 422 | Remittance volume limit exceeded | تجاوز الحد المسموح لحجم الحوالات | Wait for monthly limit to reset or request increase | انتظر حتى إعادة تعيين الحد الشهري أو اطلب زيادة | Daily or monthly remittance volume ceiling |
| `REMITTANCE_BENEFICIARY_NOT_FOUND` | 404 | Beneficiary not found in the system | المستفيد غير موجود في النظام | Verify the beneficiary identifier and try again | تحقق من معرف المستفيد وحاول مرة أخرى | Invalid beneficiary ID or not registered |
| `REMITTANCE_PURPOSE_REQUIRED` | 422 | Remittance purpose is required for cross-border transfer | الغرض من التحويل مطلوب للحوالات الخارجية | Select a purpose from the allowed list | اختر غرضاً من القائمة المسموح بها | CBS regulatory requirement for all outbound remittances |
| `REMITTANCE_SOURCE_OF_FUNDS_REQUIRED` | 422 | Source of funds declaration required | الإعلان عن مصدر الأموال مطلوب | Complete the source of funds form | أكمل نموذج مصدر الأموال | Amount > $3,000 equivalent triggers enhanced due diligence |
| `REMITTANCE_RECEIVING_COUNTRY_BLOCKED` | 403 | Receiving country is under sanctions restriction | بلد المستلم خاضع لقيود العقوبات | Choose a different receiving country | اختر بلد استلام مختلف | Country-level sanctions block |
| `REMITTANCE_BENEFICIARY_KYC_INCOMPLETE` | 422 | Beneficiary KYC information is incomplete | بيانات التحقق من هوية المستفيد غير مكتملة | Beneficiary must complete KYC registration before receiving | يجب على المستفيد إكمال التحقق من الهوية قبل الاستلام | Beneficiary not KYC-verified on payout platform |

---

## Agent (AGENT_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `AGENT_FLOAT_INSUFFICIENT` | 422 | Agent float is insufficient for cash-out | رصيد الوكيل غير كافٍ للسحب النقدي | Agent should request a float top-up from operations | يجب على الوكيل طلب تعبئة الرصيد من العمليات | Cash-out amount > agent's available float |
| `AGENT_SUSPENDED` | 403 | Agent account is suspended | حساب الوكيل موقوف | Contact agent network management for reinstatement | اتصل بإدارة شبكة الوكلاء لإعادة التنشيط | Suspended/terminated agent attempting transaction |
| `AGENT_CASH_IN_LIMIT` | 422 | Agent daily cash-in limit exceeded | تجاوز حد الإيداع النقدي اليومي للوكيل | Wait until tomorrow for limit reset | انتظر حتى الغد لإعادة تعيين الحد | Agent reached daily cash-in volume ceiling |
| `AGENT_CASH_OUT_LIMIT` | 422 | Agent daily cash-out limit exceeded | تجاوز حد السحب النقدي اليومي للوكيل | Wait until tomorrow for limit reset | انتظر حتى الغد لإعادة تعيين الحد | Agent reached daily cash-out volume ceiling |
| `AGENT_LOCATION_MISMATCH` | 409 | Customer GPS location is outside agent service area | موقع العميل خارج منطقة خدمة الوكيل | Move to a location near the agent shop or find another agent | انتقل إلى موقع قريب من متجر الوكيل أو ابحث عن وكيل آخر | Distance between customer and agent > allowed radius (e.g., 5 km) |
| `AGENT_NOT_FOUND` | 404 | Agent not found in the system | الوكيل غير موجود في النظام | Verify the agent code or scan the agent's QR | تحقق من رمز الوكيل أو امسح رمز QR الخاص به | Invalid agent ID or agent code |
| `AGENT_UNDER_REVIEW` | 403 | Agent is under compliance review | الوكيل قيد المراجعة من قبل الامتثال | Wait for the review to complete | انتظر حتى اكتمال المراجعة | Agent flagged for compliance re-verification |
| `AGENT_SUSPICIOUS_ACTIVITY` | 409 | Unusual activity detected on agent account | تم اكتشاف نشاط غير معتاد على حساب الوكيل | Contact agent operations for assistance | اتصل بعمليات الوكلاء للمساعدة | Unusual transaction velocity or amount patterns |
| `AGENT_CASH_IN_BELOW_MINIMUM` | 422 | Cash-in amount below minimum allowed (500 SYP) | مبلغ الإيداع النقدي أقل من الحد الأدنى (500 ل.س) | Increase cash-in amount to at least 500 SYP | قم بزيادة مبلغ الإيداع إلى 500 ل.س على الأقل | Cash-in < 500 SYP threshold |
| `AGENT_CASH_OUT_BELOW_MINIMUM` | 422 | Cash-out amount below minimum allowed (500 SYP) | مبلغ السحب النقدي أقل من الحد الأدنى (500 ل.س) | Increase cash-out amount to at least 500 SYP | قم بزيادة مبلغ السحب إلى 500 ل.س على الأقل | Cash-out < 500 SYP threshold |
| `AGENT_DAILY_CASH_LIMIT_ABOVE_MAXIMUM` | 422 | Agent daily cash handling limit exceeded | تجاوز حد التعامل النقدي اليومي للوكيل | Reduce transaction amount or conclude for the day | قم بتقليل مبلغ العملية أو اكتفِ لهذا اليوم | Aggregated cash-in + cash-out > agent's daily cash ceiling |
| `AGENT_COMMISSION_PAYOUT_FAILED` | 500 | Agent commission payout processing failed | فشل في معالجة عمولة الوكيل | Operations team will investigate and manually process | سيقوم فريق العمليات بالتحقيق والمعالجة يدوياً | Settlement/commission engine failure |

---

## Merchant (MERCHANT_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `MERCHANT_NOT_FOUND` | 404 | Merchant not found or QR code not recognized | التاجر غير موجود أو رمز QR غير معروف | Verify the QR code is valid and not expired | تحقق من صحة رمز QR وأنه لم ينته صلاحيته | Invalid QR code, expired static QR, deleted merchant |
| `MERCHANT_SUSPENDED` | 403 | Merchant account is suspended | حساب التاجر موقوف | Contact merchant support for reinstatement | اتصل بدعم التجار لإعادة التنشيط | Suspended/terminated merchant accepting payments |
| `MERCHANT_REFUND_EXPIRED` | 422 | Refund window has expired (7 days from transaction) | انتهت فترة الاسترجاع (7 أيام من تاريخ العملية) | Refunds are only available within 7 days of purchase | الاسترجاع متاح فقط خلال 7 أيام من تاريخ الشراء | Refund request > 7 days post-transaction |
| `MERCHANT_SETTLEMENT_FAILED` | 500 | Merchant settlement processing failed | فشل في معالجة تسوية التاجر | Operations team will investigate and retry settlement | سيقوم فريق العمليات بالتحقيق وإعادة محاولة التسوية | End-of-day batch settlement error |
| `MERCHANT_QR_EXPIRED` | 410 | Dynamic QR code has expired | انتهت صلاحية رمز QR الديناميكي | Request a new QR code from the merchant | اطلب رمز QR جديداً من التاجر | Dynamic (amount-specific) QR TTL exceeded (typically 5 minutes) |
| `MERCHANT_PAYMENT_ABOVE_MAXIMUM` | 422 | Payment amount exceeds merchant's maximum allowed | مبلغ الدفع يتجاوز الحد الأقصى المسموح به للتاجر | Reduce the payment amount | قم بتقليل مبلغ الدفع | Single transaction > merchant's configured ceiling |
| `MERCHANT_INVALID_AMOUNT` | 400 | Invalid payment amount for this merchant QR | مبلغ دفع غير صالح لرمز QR هذا | Enter an amount matching the QR or use a dynamic QR | أدخل مبلغاً مطابقاً لرمز QR أو استخدم رمز QR ديناميكياً | Amount mismatch on fixed-amount static QR |

---

## Bill Payment (BILL_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `BILLER_SERVICE_DOWN` | 503 | Biller service is currently unavailable | خدمة الفاتور غير متاحة حالياً | Try again later or pay at an agent location | حاول مرة أخرى لاحقاً أو ادفع في مكتب وكيل | Biller API timeout or unreachable |
| `BILL_ALREADY_PAID` | 409 | This bill has already been paid | تم دفع هذه الفاتورة مسبقاً | Verify the bill status; do not make duplicate payment | تحقق من حالة الفاتورة، لا تقم بدفع مكرر | Duplicate bill ID or bill already settled |
| `BILL_NOT_FOUND` | 404 | Biller or account not found | المزوّد أو الحساب غير موجود | Verify the biller code and account number | تحقق من رمز المزوّد ورقم الحساب | Invalid meter number, account ID, or biller code |
| `BILL_PAYMENT_FAILED` | 502 | Biller rejected the payment | رفض مزوّد الخدمة الدفع | Try again later or contact the biller directly | حاول مرة أخرى لاحقاً أو اتصل بمزوّد الخدمة مباشرة | Biller-side error during payment confirmation |
| `BILL_INQUIRY_FAILED` | 502 | Failed to fetch bill details from biller | فشل في جلب تفاصيل الفاتورة من المزوّد | Retry the inquiry or check account details | أعد محاولة الاستعلام أو تحقق من تفاصيل الحساب | Biller API error or timeout during balance check |
| `BILL_INVALID_AMOUNT` | 422 | Bill amount mismatch between inquiry and payment | عدم تطابق مبلغ الفاتورة بين الاستعلام والدفع | Re-inquire and accept the new amount | أعد الاستعلام واقبل المبلغ الجديد | Amount changed between inquiry and confirmation (biller-side update) |
| `BILL_ACCOUNT_FORMAT_INVALID` | 400 | Account number format is invalid for this biller | تنسيق رقم الحساب غير صالح لهذا المزوّد | Enter the account number in the correct format (e.g., 10-digit meter number) | أدخل رقم الحساب بالتنسيق الصحيح (مثلاً رقم العداد 10 أرقام) | Customer entered wrong format (e.g., letters in numeric field) |
| `BILL_RETRY_EXCEEDED` | 429 | Too many bill payment retries in a short period | تجاوز عدد محاولات دفع الفاتورة في فترة قصيرة | Wait 30 minutes before retrying | انتظر 30 دقيقة قبل إعادة المحاولة | > 3 failed attempts in 15 minutes for same biller account |

---

## Card (CARD_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `CARD_NOT_FOUND` | 404 | Card not found | البطاقة غير موجودة | Verify the card ID and try again | تحقق من معرف البطاقة وحاول مرة أخرى | Invalid or deleted card reference |
| `CARD_SUSPENDED` | 403 | Card is suspended or blocked | البطاقة موقوفة أو محظورة | Contact card support to resolve | اتصل بدعم البطاقات لحل المشكلة | Card reported lost, stolen, or fraud-blocked |
| `CARD_EXPIRED` | 422 | Card has expired | انتهت صلاحية البطاقة | Order a replacement card | اطلب بطاقة بديلة | Physical card beyond expiration date |
| `CARD_LIMIT_EXCEEDED` | 422 | Card transaction limit exceeded | تجاوز حد معاملات البطاقة | Wait for limit reset or request limit increase | انتظر إعادة تعيين الحد أو اطلب زيادة | Per-transaction, daily, or monthly card spending limit |
| `CARD_PIN_BLOCKED` | 403 | Card PIN is blocked after too many failed attempts | تم حظر رمز PIN للبطاقة بعد محاولات فاشلة | Contact support to reset the card PIN | اتصل بالدعم لإعادة تعيين رمز PIN للبطاقة | > 3 incorrect PIN attempts on card |
| `CARD_CVV_MISMATCH` | 422 | Card CVV does not match | رمز CVV للبطاقة غير صحيح | Verify the CVV and try again | تحقق من رمز CVV وحاول مرة أخرى | Online transaction with incorrect security code |

---

## Loan / Financing (LOAN_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `LOAN_NOT_FOUND` | 404 | Loan not found | القرض غير موجود | Verify the loan ID and try again | تحقق من معرف القرض وحاول مرة أخرى | Invalid loan reference |
| `LOAN_ALREADY_REPAID` | 409 | Loan has already been fully repaid | تم سداد القرض بالكامل مسبقاً | No further action needed; loan is closed | لا حاجة لإجراء آخر، القرض مغلق | Duplicate repayment or inquiry on closed loan |
| `LOAN_OVERDUE` | 422 | Loan is overdue; late fees may apply | القرض متأخر السداد؛ قد تُطبق رسوم تأخير | Make the overdue payment as soon as possible | قم بسداد المبلغ المتأخر في أقرب وقت ممكن | Payment due date passed without full repayment |
| `LOAN_INELIGIBLE` | 403 | User is not eligible for this loan product | المستخدم غير مؤهل لهذا المنتج التمويلي | Check KYC tier, account age, and credit history requirements | تحقق من متطلبات مستوى التحقق وعمر الحساب والتاريخ الائتماني | KYC tier too low, account too new, or existing default |
| `LOAN_AMOUNT_OUT_OF_RANGE` | 422 | Loan amount is outside the allowed range for this product | مبلغ القرض خارج النطاق المسموح به لهذا المنتج | Choose an amount between the minimum and maximum for this product | اختر مبلغاً بين الحدين الأدنى والأقصى لهذا المنتج | Amount < min or > max for chosen loan product |
| `LOAN_DEFAULTED` | 403 | Loan is in default status | القرض في حالة تعثر | Contact collections team to arrange a repayment plan | اتصل بفريق التحصيل لترتيب خطة سداد | Loan > 90 days overdue, moved to default |
| `LOAN_DISBURSEMENT_FAILED` | 500 | Loan disbursement to wallet failed | فشل صرف القرض إلى المحفظة | Operations team will investigate and retry | سيقوم فريق العمليات بالتحقيق وإعادة المحاولة | Wallet service error during disbursement |

---

## Savings (SAVINGS_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `SAVINGS_GOAL_NOT_FOUND` | 404 | Savings goal not found | هدف الادخار غير موجود | Verify the savings goal ID | تحقق من معرف هدف الادخار | Invalid or deleted goal |
| `SAVINGS_GOAL_COMPLETED` | 409 | Savings goal has already been completed | تم إكمال هدف الادخار مسبقاً | Close or create a new savings goal | أغلق الهدف أو أنشئ هدفاً جديداً | Deposit attempt on a completed goal |
| `SAVINGS_WITHDRAWAL_EARLY` | 422 | Early withdrawal penalty applies before goal maturity | تطبق غرامة السحب المبكر قبل تاريخ استحقاق الهدف | Confirm acceptance of the early withdrawal fee | قم بتأكيد قبول رسوم السحب المبكر | Withdrawal before goal maturity date |
| `SAVINGS_AUTO_DEPOSIT_FAILED` | 422 | Auto-deposit failed due to insufficient wallet balance | فشل الإيداع التلقائي بسبب عدم كفاية رصيد المحفظة | Ensure wallet has sufficient balance on the scheduled date | تأكد من وجود رصيد كافٍ في المحفظة في التاريخ المحدد | Scheduled deposit > available wallet balance |
| `SAVINGS_GOAL_PAUSED` | 403 | Savings goal is paused; resume to continue deposits | هدف الادخار موقوف؛ استأنف للمتابعة | Resume the goal from the savings settings | استأنف الهدف من إعدادات الادخار | Deposit attempted on paused goal |

---

## Authentication & Security (AUTH_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `AUTH_INVALID_OTP` | 401 | Invalid or expired OTP code | رمز التحقق غير صحيح أو منتهي الصلاحية | Request a new OTP and try again | اطلب رمز تحقق جديد وحاول مرة أخرى | Wrong OTP or OTP expired (TTL: 5 minutes) |
| `AUTH_INVALID_PIN` | 401 | Invalid wallet PIN | رمز PIN غير صحيح | Check your PIN and try again (3 attempts remaining) | تحقق من رمز PIN وحاول مرة أخرى (3 محاولات متبقية) | Incorrect PIN on login or transaction |
| `AUTH_DEVICE_NOT_BOUND` | 403 | Device not recognized; verification required | الجهاز غير معروف؛ التحقق مطلوب | Complete MFA verification via SMS OTP or email | أكمل التحقق عبر رمز SMS أو البريد الإلكتروني | New device login, first-time device binding |
| `AUTH_SESSION_EXPIRED` | 401 | Session has expired due to inactivity | انتهت الجلسة بسبب عدم النشاط | Re-authenticate with phone number and PIN | أعد تسجيل الدخول باستخدام رقم الهاتف ورمز PIN | 10 minutes of inactivity on the app |
| `AUTH_RATE_LIMITED` | 429 | Too many failed attempts; account temporarily locked | عدد كبير جداً من المحاولات الفاشلة؛ الحساب مقفل مؤقتاً | Wait 30 minutes before trying again | انتظر 30 دقيقة قبل المحاولة مرة أخرى | > 5 failed PIN attempts in 30 minutes |
| `AUTH_TOKEN_EXPIRED` | 401 | Access token has expired | انتهت صلاحية رمز الدخول | Refresh the token or re-authenticate | جدد الرمز أو أعد تسجيل الدخول | > 15 minutes since JWT issued |
| `AUTH_TOKEN_REVOKED` | 401 | Session has been revoked | تم إلغاء الجلسة | Re-authenticate with phone number and PIN | أعد تسجيل الدخول باستخدام رقم الهاتف ورمز PIN | Admin force-logout, password change, or device unbinding |
| `AUTH_REFRESH_TOKEN_EXPIRED` | 401 | Refresh token has expired | انتهت صلاحية رمز التحديث | Re-authenticate with phone number and PIN | أعد تسجيل الدخول باستخدام رقم الهاتف ورمز PIN | Refresh token TTL exceeded (30 days) |
| `AUTH_OTP_RATE_LIMITED` | 429 | Too many OTP requests; please wait | عدد كبير جداً من طلبات رمز التحقق؛ انتظر من فضلك | Wait 15 minutes before requesting a new OTP | انتظر 15 دقيقة قبل طلب رمز تحقق جديد | > 3 OTP requests in 15 minutes |
| `AUTH_PIN_SAME_AS_OLD` | 422 | New PIN is identical to the current PIN | رمز PIN الجديد مطابق للقديم | Choose a different PIN | اختر رمز PIN مختلفاً | PIN change: new PIN == old PIN |
| `AUTH_PIN_TOO_SHORT` | 422 | PIN must be 4–6 digits | يجب أن يتكون رمز PIN من 4-6 أرقام | Enter a PIN of valid length | أدخل رمز PIN بطول صحيح | PIN length < 4 or > 6 digits |
| `AUTH_PIN_CHANGE_RATE_LIMITED` | 429 | Too many PIN change attempts | عدد كبير جداً من محاولات تغيير رمز PIN | Wait 60 minutes before retrying | انتظر 60 دقيقة قبل إعادة المحاولة | > 3 PIN change attempts in 60 minutes |

---

## Compliance & KYC (CMP_*, KYC_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `CMP_KYC_REQUIRED` | 403 | KYC verification not completed; action requires higher tier | التحقق من الهوية غير مكتمل؛ تتطلب العملية مستوى أعلى | Complete KYC Tier 2 to access this feature | أكمل التحقق من المستوى الثاني للوصول إلى هذه الميزة | Action requires Tier 2+ but user is Tier 1 |
| `CMP_KYC_PENDING` | 403 | KYC documents are under review | وثائق التحقق قيد المراجعة | Wait for KYC review to complete (1–2 business days) | انتظر حتى اكتمال مراجعة المستندات (1-2 يوم عمل) | Action attempted during pending KYC verification |
| `CMP_KYC_REJECTED` | 403 | KYC verification was rejected | تم رفض التحقق من الهوية | Submit new documents following the rejection reason | قدم مستندات جديدة مع مراعاة سبب الرفض | Invalid, expired, or unclear documents submitted |
| `CMP_AML_REVIEW_REQUIRED` | 403 | Transaction flagged and pending AML review | تم الإبلاغ عن العملية وهي قيد مراجعة مكافحة غسل الأموال | Wait for compliance team to complete the review (24–48h) | انتظر حتى يكمل فريق الامتثال المراجعة (24-48 ساعة) | Transaction triggered AML rule (velocity, amount, geography) |
| `CMP_SANCTIONS_HIT` | 403 | Name matched sanctions screening list | تطابق الاسم مع قائمة العقوبات | Contact compliance immediately for resolution | اتصل بقسم الامتثال فوراً للحل | OFAC, CBS, UN, or EU sanctions list positive match |
| `CMP_DAILY_REPORT_FAILED` | 500 | Failed to generate CBS daily compliance report | فشل في إنشاء تقرير الامتثال اليومي للمصرف المركزي | Operations team must manually generate and submit the report | يجب على فريق العمليات إنشاء التقرير وتقديمه يدوياً | CBS report generation or submission error |
| `KYC_DOCUMENT_EXPIRED` | 422 | Uploaded document is expired | الوثيقة المرفوعة منتهية الصلاحية | Upload a valid, non-expired document | قم برفع وثيقة سارية المفعول | ID card, passport, or proof of address beyond validity date |
| `KYC_DOCUMENT_TYPE_UNSUPPORTED` | 400 | Document type is not accepted | نوع الوثيقة غير مقبول | Upload one of the accepted document types (ID, passport, etc.) | قم برفع أحد أنواع الوثائق المقبولة (هوية، جواز سفر، إلخ) | Unsupported doc type submitted |
| `KYC_FACE_MISMATCH` | 422 | Selfie does not match the ID document photo | الصورة الشخصية لا تطابق صورة وثيقة الهوية | Retake the selfie in good lighting; ensure face is clearly visible | أعد التقاط الصورة الشخصية في إضاءة جيدة؛ تأكد من وضوح الوجه | Facial recognition similarity score < 0.75 |
| `KYC_OCR_FAILED` | 422 | Failed to read document text; document may be blurry or damaged | فشل في قراءة نص الوثيقة؛ قد تكون الوثيقة غير واضحة أو تالفة | Ensure the document is flat, well-lit, and all text is readable | تأكد من أن الوثيقة مسطحة ومضاءة جيداً وجميع النصوص واضحة | OCR failure on uploaded ID image |
| `KYC_SUBMISSION_RATE_LIMITED` | 429 | Too many KYC submission attempts; try again tomorrow | عدد كبير جداً من محاولات تقديم مستندات التحقق؛ حاول مرة أخرى غداً | Wait until tomorrow to submit new documents | انتظر حتى الغد لتقديم مستندات جديدة | > 5 KYC submission attempts within 24 hours |

---

## Settlement (SETTLEMENT_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `SETTLEMENT_BATCH_IN_PROGRESS` | 409 | Settlement batch is already in progress for this period | تسوية الفترة قيد المعالجة بالفعل | Wait for the current settlement to complete | انتظر حتى اكتمال التسوية الحالية | Duplicate EOD trigger while batch still running |
| `SETTLEMENT_RECONCILIATION_FAILED` | 500 | Settlement reconciliation mismatch exceeded tolerance | عدم تطابق في تسوية الفروقات يتجاوز الحد المسموح | Manual intervention required; operations team notified | تدخل يدوي مطلوب؛ تم إشعار فريق العمليات | Discrepancy between wallet service and CBS/nostro records > threshold |
| `SETTLEMENT_BANK_TIMEOUT` | 502 | Bank transfer timed out during settlement | انتهت مهلة التحويل المصرفي أثناء التسوية | Retry the batch; operations team should verify final state | أعد محاولة الدفعة؛ يجب على فريق العمليات التحقق من الحالة النهائية | CBS RTGS/Syriaqat system timeout |
| `SETTLEMENT_NO_TRANSACTIONS` | 400 | No pending transactions found for settlement period | لا توجد معاملات معلقة للتسوية في هذه الفترة | No action needed; the period has no unsettled items | لا حاجة لإجراء؛ لا توجد عناصر غير مسوّاة في هذه الفترة | Empty batch trigger |
| `SETTLEMENT_AMOUNT_MISMATCH` | 422 | Settlement total does not match calculated aggregate | إجمالي التسوية لا يتطابق مع الإجمالي المحسوب | Operations team must reconcile the discrepancy | يجب على فريق العمليات تسوية الفرق | Arithmetic discrepancy in settlement calculation |
| `SETTLEMENT_MERCHANT_FAILED` | 500 | Merchant-specific settlement within batch failed | فشل تسوية تاجر معين ضمن الدفعة | Check merchant settlement configuration and retry | تحقق من إعدادات تسوية التاجر وأعد المحاولة | Individual merchant settlement error (bank account, limits) |
| `SETTLEMENT_AGENT_FAILED` | 500 | Agent-specific settlement within batch failed | فشل تسوية وكيل معين ضمن الدفعة | Check agent float reconciliation and retry | تحقق من تسوية رصيد الوكيل وأعد المحاولة | Agent commission or float adjustment error |

---

## Fraud (FRAUD_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `FRAUD_TRANSACTION_BLOCKED` | 403 | Transaction automatically blocked by fraud detection | تم حظر العملية تلقائياً من قبل نظام كشف الاحتيال | Contact support to review the block | اتصل بالدعم لمراجعة الحظر | Risk score > 900 (critical) triggers automatic block |
| `FRAUD_REVIEW_REQUIRED` | 403 | Transaction flagged for manual fraud review | تم الإبلاغ عن العملية لمراجعة احتيال يدوية | Wait for fraud team review (typically < 1 hour) | انتظر مراجعة فريق الاحتيال (عادة أقل من ساعة) | Risk score 700–900 (high) triggers manual review |
| `FRAUD_DEVICE_BLOCKED` | 403 | Device is blacklisted due to previous fraud activity | الجهاز في القائمة السوداء بسبب نشاط احتيال سابق | Contact support to appeal the device block | اتصل بالدعم للاعتراض على حظر الجهاز | Known fraud device ID attempting transaction |
| `FRAUD_IP_BLOCKED` | 403 | IP address is associated with fraudulent activity | عنوان IP مرتبط بنشاط احتيال | Use a different network or contact support | استخدم شبكة مختلفة أو اتصل بالدعم | IP in known fraud/abuse database |
| `FRAUD_RAPID_SUCCESSIVE_TXNS` | 409 | Multiple rapid transactions detected; rate-limited as precaution | تم اكتشاف معاملات سريعة متتالية؛ تم تقييد السرعة كإجراء احترازي | Slow down transaction frequency and try again | قلل من تكرار المعاملات وحاول مرة أخرى | > 5 transactions within 60 seconds on same wallet |

---

## Payroll (PAYROLL_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `PAYROLL_EMPLOYER_NOT_FOUND` | 404 | Employer not found | صاحب العمل غير موجود | Verify employer ID | تحقق من معرف صاحب العمل | Invalid employer reference |
| `PAYROLL_BATCH_NOT_FOUND` | 404 | Payroll batch not found | دفعة الرواتب غير موجودة | Verify batch ID | تحقق من معرف الدفعة | Invalid batch reference |
| `PAYROLL_BATCH_ALREADY_PROCESSED` | 409 | Batch has already been processed | تمت معالجة الدفعة مسبقاً | Cannot modify a processed batch | لا يمكن تعديل دفعة تمت معالجتها | Duplicate process attempt |
| `PAYROLL_INSUFFICIENT_FUNDS` | 422 | Employer float insufficient for payroll | رصيد صاحب العمل غير كافٍ للرواتب | Deposit funds to the employer wallet | قم بإيداع الأموال في محفظة صاحب العمل | Balance < total batch amount |
| `PAYROLL_CSV_INVALID` | 422 | CSV file format is invalid | تنسيق ملف CSV غير صالح | Check CSV headers and row format | تحقق من رؤوس CSV وتنسيق الصفوف | Missing or malformed CSV columns |
| `PAYROLL_DISBURSEMENT_FAILED` | 500 | Individual disbursement within batch failed | فشل صرف فردي ضمن الدفعة | Check employee wallet and retry | تحقق من محفظة الموظف وأعد المحاولة | Wallet service error during disbursement |

---

## Loyalty & Rewards (LOYALTY_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `LOYALTY_INSUFFICIENT_POINTS` | 422 | Insufficient loyalty points balance | رصيد نقاط ولاء غير كافٍ | Earn more points or choose a lower-tier reward | اربح المزيد من النقاط أو اختر مكافأة بمستوى أقل | Redeem amount > available points |
| `LOYALTY_TIER_NOT_FOUND` | 404 | Loyalty tier not found | مستوى الولاء غير موجود | Invalid tier reference | مرجع مستوى غير صالح | Tier configuration missing |
| `LOYALTY_REWARD_OUT_OF_STOCK` | 409 | Reward item is out of stock | المكافأة غير متوفرة في المخزون | Choose a different reward or wait for restock | اختر مكافأة مختلفة أو انتظر إعادة التزويد | Reward stock depleted |

---

## Government Collections (GOV_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `GOV_PROVIDER_NOT_FOUND` | 404 | Government service provider not found | مزود الخدمة الحكومية غير موجود | Verify provider ID | تحقق من معرف المزود | Invalid provider reference |
| `GOV_INQUIRY_EXPIRED` | 422 | Payment inquiry has expired | انتهت صلاحية استعلام الدفع | Create a new inquiry | قم بإنشاء استعلام جديد | Inquiry TTL exceeded (30 min) |
| `GOV_PAYMENT_FAILED` | 500 | Government payment processing failed | فشلت معالجة الدفع الحكومي | Retry or contact support | أعد المحاولة أو اتصل بالدعم | CBS/gateway error during processing |

---

## Education (EDU_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `EDU_INSTITUTION_NOT_FOUND` | 404 | Educational institution not found | المؤسسة التعليمية غير موجودة | Verify institution ID | تحقق من معرف المؤسسة | Invalid institution reference |
| `EDU_STUDENT_NOT_FOUND` | 404 | Student record not found | سجل الطالب غير موجود | Verify student ID or register the student | تحقق من معرف الطالب أو سجله | Unregistered student |
| `EDU_FEE_ALREADY_PAID` | 409 | Fee has already been fully paid | الرسوم مدفوعة بالكامل مسبقاً | No action needed | لا حاجة لإجراء | Duplicate payment attempt |

---

## Humanitarian Aid (HUM_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `HUM_ORGANIZATION_NOT_FOUND` | 404 | Humanitarian organization not found | المنظمة الإنسانية غير موجودة | Verify organization ID | تحقق من معرف المنظمة | Invalid org reference |
| `HUM_PROGRAM_NOT_FOUND` | 404 | Humanitarian program not found | البرنامج الإنساني غير موجود | Verify program ID | تحقق من معرف البرنامج | Invalid program reference |
| `HUM_INSUFFICIENT_BUDGET` | 422 | Program budget is insufficient for disbursement | ميزانية البرنامج غير كافية للصرف | Allocate additional budget to the program | خصص ميزانية إضافية للبرنامج | Requested amount > remaining budget |

---

## Open Finance (OF_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `OF_APP_NOT_FOUND` | 404 | Third-party app not found | التطبيق الخارجي غير موجود | Verify app/client ID | تحقق من معرف التطبيق | Invalid app reference |
| `OF_CONSENT_NOT_FOUND` | 404 | User consent not found | موافقة المستخدم غير موجودة | Verify consent ID | تحقق من معرف الموافقة | Invalid or revoked consent |
| `OF_CONSENT_EXPIRED` | 401 | User consent has expired | انتهت صلاحية موافقة المستخدم | Request new consent from the user | اطلب موافقة جديدة من المستخدم | Consent TTL exceeded |
| `OF_INVALID_SCOPE` | 400 | Requested scope is not valid or not granted | النطاق المطلوب غير صالح أو غير مصرح به | Request only valid granted scopes | اطلب نطاقات مصرح بها صالحة فقط | Unknown or unapproved scope requested |

---

## System (SYS_*)

| Code | HTTP | Message | Message (AR) | Resolution | Resolution (AR) | When |
|------|------|---------|-------------|------------|-----------------|------|
| `SYS_INTERNAL_ERROR` | 500 | An unexpected internal error occurred | حدث خطأ داخلي غير متوقع | Try again later; if the issue persists, contact support | حاول مرة أخرى لاحقاً؛ إذا استمرت المشكلة، اتصل بالدعم | Catch-all for unhandled exceptions; correlation ID enables log tracing |
| `SYS_SERVICE_UNAVAILABLE` | 503 | Service is temporarily unavailable | الخدمة غير متاحة حالياً | Try again later; maintenance windows are published on the status page | حاول مرة أخرى لاحقاً؛ يتم نشر نوافذ الصيانة على صفحة الحالة | Scheduled maintenance, unexpected outage, circuit breaker open |
| `SYS_RATE_LIMIT_EXCEEDED` | 429 | API rate limit exceeded | تجاوز الحد المسموح لطلبات API | Slow down request rate; see Retry-After header | قلل من معدل الطلبات؛ راجع رأس Retry-After | General API rate limit per user/IP |
| `SYS_INVALID_REQUEST` | 400 | Request validation failed | فشل التحقق من صحة الطلب | Check the request body, parameters, and headers against the API specification | تحقق من جسم الطلب والمعاملات والرؤوس وفقاً لمواصفات API | Missing required field, invalid format, constraint violation |
| `SYS_DUPLICATE_REQUEST` | 409 | Duplicate request detected via idempotency key | تم اكتشاف طلب مكرر عبر مفتاح idempotency | The original request was already processed; check its status | تمت معالجة الطلب الأصلي بالفعل؛ تحقق من حالته | Same idempotency key within 24-hour window |
| `SYS_DEPRECATED_VERSION` | 400 | API version is deprecated; upgrade required | إصدار API ملغي؛ الترقية مطلوبة | Migrate to the latest supported API version (see changelog) | قم بالترقية إلى أحدث إصدار مدعوم من API (راجع سجل التغييرات) | Client calling a sunset API version (e.g., v1 → v2 migration) |
| `SYS_DATABASE_TIMEOUT` | 503 | Database connection timed out | انتهت مهلة الاتصال بقاعدة البيانات | Retry the request; operations team has been alerted | أعد محاولة الطلب؛ تم إشعار فريق العمليات | Connection pool exhausted, replica lag, or DB overload |
| `SYS_REQUEST_TIMEOUT` | 408 | Request processing exceeded maximum allowed time | تجاوزت معالجة الطلب الحد الأقصى للوقت المسموح به | Retry with a simpler request or contact support | أعد المحاولة بطلب أبسط أو اتصل بالدعم | Processing time > 30 seconds; gateway timeout |
| `SYS_PAYLOAD_TOO_LARGE` | 413 | Request payload exceeds maximum size | حجم الطلب يتجاوز الحد الأقصى المسموح به | Reduce payload size or use multipart upload | قلل من حجم الطلب أو استخدم الرفع المجزّأ | Body > 10 MB (API gateway limit) |
| `SYS_MAINTENANCE_MODE` | 503 | System is in maintenance mode | النظام في وضع الصيانة | Check the status page for estimated completion time | تحقق من صفحة الحالة للوقت المتوقع للانتهاء | Active maintenance window; all non-critical endpoints disabled |

---

## Validation Error Details

When `SYS_INVALID_REQUEST` is returned, the `details` object contains a `fields` map describing each validation failure:

```json
{
  "error": {
    "code": "SYS_INVALID_REQUEST",
    "http_status": 400,
    "message": "Request validation failed",
    "message_ar": "فشل التحقق من صحة الطلب",
    "resolution": "Check the request body, parameters, and headers against the API specification",
    "resolution_ar": "تحقق من جسم الطلب والمعاملات والرؤوس وفقاً لمواصفات API",
    "details": {
      "fields": {
        "amount": {
          "code": "WALLET_BELOW_MINIMUM",
          "message": "Amount must be at least 100 SYP",
          "message_ar": "يجب أن يكون المبلغ 100 ل.س على الأقل"
        },
        "currency": {
          "code": "SYS_INVALID_REQUEST",
          "message": "Currency must be one of: SYP, USD",
          "message_ar": "يجب أن تكون العملة إما SYP أو USD"
        },
        "recipient_phone": {
          "code": "SYS_INVALID_REQUEST",
          "message": "Invalid phone number format; must be +963XXXXXXXX",
          "message_ar": "تنسيق رقم الهاتف غير صحيح؛ يجب أن يكون +963XXXXXXXX"
        }
      }
    }
  }
}
```

---

## Retry Strategy

| HTTP Status | Idempotent? | Retry Strategy | Notes |
|-------------|------------|---------------|-------|
| 400 | Depends | Do not retry; fix the request | Invalid request, wrong API version |
| 401 | No | Re-authenticate, then retry | Token expired, session revoked |
| 403 | No | Do not retry without user action | KYC, compliance, sanctions, fraud blocks |
| 404 | Depends | Verify identifier and retry | Resource not found; may be transient (replica lag) |
| 409 | Depends | Check original state; retry if safe | Duplicate, in-progress, or conflict |
| 422 | No | Do not retry; fix the business parameters | Business logic rejection (limits, balance, etc.) |
| 429 | Yes | Retry after `Retry-After` seconds | Rate limited; exponential backoff + jitter |
| 500 | Yes | Retry with exponential backoff | Internal error; may be transient |
| 502 | Yes | Retry with longer timeout | Upstream/downstream service failure |
| 503 | Yes | Retry after `Retry-After` seconds | Service unavailable; circuit breaker may be open |

### Exponential Backoff Recommendation

```
Attempt 1: wait 1s
Attempt 2: wait 2s
Attempt 3: wait 4s
Attempt 4: wait 8s
Attempt 5: wait 15s  (cap)
Max attempts: 5
Add jitter: random(0, 500ms) per attempt
```

---

## Syria-Specific Error Details

When applicable, error responses SHOULD include Syria-specific context in `details`:

| Detail Field | Type | Description | Example |
|-------------|------|-------------|---------|
| `syp_amount` | BigInt | Amount in SYP piasters for user reference | `500000` |
| `usd_equivalent` | BigInt | Equivalent in USD cents for cross-border context | `3817` |
| `cbs_reference` | String | CBS transaction reference number if generated | `CBS-20250529-001234` |
| `agent_distance_meters` | Integer | Distance from user GPS to agent location | `3200` |
| `kyc_required_tier` | String | KYC tier needed for the attempted action | `tier_2_verified` |
| `limit_reset_at` | ISO 8601 | When the daily/monthly limit resets | `2025-05-30T00:00:00Z` |
| `retry_after_seconds` | Integer | Recommended wait time before retry | `1800` |
| `correlation_id` | UUIDv4 | Trace ID for support/ops investigation | `c123e4567-e89b-12d3-a456-426614174000` |
| `available_corridors` | String[] | List of available corridors when corridor unavailable | `["SYR_LBN", "SYR_JOR"]` |

### Example: Wallet Limit Exceeded with Syria Context

```json
{
  "error": {
    "code": "WALLET_LIMIT_EXCEEDED",
    "http_status": 422,
    "message": "Daily transaction limit exceeded",
    "message_ar": "تجاوز الحد اليومي للمعاملات",
    "resolution": "Upgrade your KYC tier to increase your daily limit, or wait until tomorrow",
    "resolution_ar": "قم بترقية مستوى التحقق لزيادة حدك اليومي، أو انتظر حتى الغد",
    "details": {
      "limit_type": "daily_transaction_volume",
      "limit_value": 5000000,
      "used_today": 4850000,
      "remaining": 150000,
      "attempted_amount": 500000,
      "currency": "SYP",
      "syp_amount_formatted": "48,500 / 50,000 SYP used",
      "kyc_current_tier": "tier_1_basic",
      "kyc_required_tier": "tier_2_verified",
      "limit_reset_at": "2025-05-30T00:00:00+03:00",
      "correlation_id": "c123e4567-e89b-12d3-a456-426614174000"
    }
  }
}
```
