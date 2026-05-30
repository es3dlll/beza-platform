# Central Error Catalog

> Single source of truth for ALL error codes across the Beza Platform. Every feature, service, and API response MUST use these codes. Never define ad-hoc error messages.

## Error Code Format

`{DOMAIN}_{SEQUENTIAL}` — 3-letter domain prefix + 3-digit sequential number.

| Domain       | Prefix | Service                  |
| ------------ | ------ | ------------------------ |
| Wallet       | `WAL`  | Wallet Service           |
| FX           | `FX`   | FX Service               |
| Remittance   | `REM`  | Remittance Service       |
| Compliance   | `AML`  | Compliance / AML Service |
| Auth         | `AUTH` | Auth Service             |
| Settlement   | `SET`  | Settlement Service       |
| CFE          | `CFE`  | CFE Connector            |
| Agent        | `AGT`  | Agent Service            |
| Merchant     | `MER`  | Merchant Service         |
| User         | `USR`  | User Service             |
| KYC          | `KYC`  | KYC Service              |
| Notification | `NOT`  | Notification Service     |
| General      | `GEN`  | API Gateway / General    |

## Wallet (WAL)

| Code    | HTTP | Message (AR)                                    | Cause                                           | Resolution                                  |
| ------- | ---- | ----------------------------------------------- | ----------------------------------------------- | ------------------------------------------- |
| WAL_001 | 402  | الرصيد غير كافٍ                                 | Insufficient balance                            | Fund wallet via cash-in or receive transfer |
| WAL_002 | 429  | تجاوز الحد اليومي                               | Daily transaction limit exceeded                | Upgrade KYC level or wait until tomorrow    |
| WAL_003 | 422  | المبلغ أقل من الحد الأدنى                       | Amount below minimum allowed (100 SYP)          | Increase amount to minimum                  |
| WAL_004 | 422  | المبلغ يتجاوز الحد الأقصى                       | Amount exceeds maximum per transaction          | Reduce amount or upgrade KYC                |
| WAL_005 | 422  | المحفظة غير نشطة                                | Wallet is frozen, closed, or suspended          | Contact support to reactivate               |
| WAL_006 | 404  | المحفظة غير موجودة                              | Wallet not found for given user                 | Verify user ID                              |
| WAL_007 | 409  | التحويل إلى نفس المحفظة غير مسموح               | Sender and recipient wallets are the same       | Use a different recipient                   |
| WAL_008 | 422  | العملة غير مدعومة                               | Currency not supported for this operation       | Use SYP for domestic transfers              |
| WAL_009 | 429  | تجاوز الحد الشهري                               | Monthly transaction limit exceeded              | Upgrade KYC level or wait until next month  |
| WAL_010 | 500  | فشل عملية التحويل                               | Transfer failed due to system error             | Retry the transaction                       |
| WAL_011 | 422  | مبلغ التحويل يتجاوز رصيد المحفظة بعد خصم الرسوم | Amount + fees exceed available balance          | Increase balance or reduce amount           |
| WAL_012 | 422  | تم تجاوز عدد المعاملات المسموح بها يومياً       | Daily transaction count limit exceeded (50/day) | Wait until tomorrow                         |
| WAL_013 | 422  | المحفظة المصدرية والمحفظة الوجهة متماثلتان      | Source and destination wallets are identical    | Choose different destination                |
| WAL_014 | 422  | لا يمكن التحويل إلى هذا المستخدم                | Recipient is not eligible to receive transfers  | Recipient must complete KYC level 1         |
| WAL_015 | 409  | العملية معلقة بالفعل                            | Transaction already has a pending processing    | Wait for completion or check status         |

## FX (FX)

| Code   | HTTP | Message (AR)                                   | Cause                                            | Resolution                              |
| ------ | ---- | ---------------------------------------------- | ------------------------------------------------ | --------------------------------------- |
| FX_001 | 422  | سعر الصرف منتهي الصلاحية                       | Rate lock has expired (TTL: 5 minutes)           | Fetch a new rate quote                  |
| FX_002 | 503  | مزود سعر الصرف غير متاح                        | Rate provider is unavailable or returning errors | Retry with fallback provider            |
| FX_003 | 422  | زوج العملات غير مدعوم                          | Currency pair not supported for conversion       | Check available corridors               |
| FX_004 | 422  | مبلغ التحويل أقل من الحد الأدنى لتحويل العملات | Amount below minimum for FX conversion           | Increase amount                         |
| FX_005 | 422  | مبلغ التحويل يتجاوز الحد الأقصى لتحويل العملات | Amount exceeds maximum for FX conversion         | Reduce amount or request limit increase |
| FX_006 | 500  | فشل في الحصول على سعر الصرف                    | Failed to fetch exchange rate from all providers | Try again later                         |
| FX_007 | 429  | تم تجاوز عدد طلبات سعر الصرف                   | Too many rate fetch requests (30/min per user)   | Cache rate locally and reuse            |
| FX_008 | 422  | سعر الصرف المقدم لا يتطابق مع السعر الحالي     | Provided rate does not match current market      | Fetch a new rate                        |

## Remittance (REM)

| Code    | HTTP | Message (AR)                         | Cause                                              | Resolution                                      |
| ------- | ---- | ------------------------------------ | -------------------------------------------------- | ----------------------------------------------- |
| REM_001 | 422  | المستفيد غير موجود على المنصة        | Recipient is not registered on Beza Pay            | Invite recipient to register                    |
| REM_002 | 403  | دولة المستفيد غير مدعومة             | Recipient country is not in supported corridors    | Check available corridors or contact support    |
| REM_003 | 422  | بيانات المستفيد غير مكتملة           | Recipient information is incomplete for remittance | Complete recipient KYC                          |
| REM_004 | 422  | تجاوز الحد المسموح للحوالات الشهرية  | Monthly remittance limit exceeded                  | Wait until next month or request limit increase |
| REM_005 | 422  | مبلغ الحوالة أقل من الحد الأدنى      | Remittance amount below minimum for corridor       | Increase amount to minimum                      |
| REM_006 | 500  | فشل في معالجة الحوالة                | Remittance processing failed due to system error   | Retry or contact support                        |
| REM_007 | 422  | الحوالة قيد المراجعة من قبل الامتثال | Remittance is under compliance review              | Wait for review completion                      |
| REM_008 | 403  | تم رفض الحوالة من قبل مزود الخدمة    | Remittance rejected by downstream provider         | Contact compliance for details                  |

## Compliance / AML (AML)

| Code    | HTTP | Message (AR)                                  | Cause                                                                | Resolution                                 |
| ------- | ---- | --------------------------------------------- | -------------------------------------------------------------------- | ------------------------------------------ |
| AML_001 | 403  | تم حظر المعاملة بسبب لوائح مكافحة غسل الأموال | Transaction flagged by AML rules (structuring, rapid movement, etc.) | Contact support to initiate review process |
| AML_002 | 403  | تم حظر المعاملة بسبب العقوبات                 | Sender or recipient matched sanctions list                           | Contact compliance immediately             |
| AML_003 | 403  | تم تجميد الحساب من قبل الامتثال               | Account frozen by compliance team pending investigation              | Contact compliance                         |
| AML_004 | 403  | يتطلب موافقة مسؤول الامتثال                   | Transaction requires compliance manager approval                     | Wait for manual review (24-48h)            |
| AML_005 | 422  | مصدر الأموال مطلوب                            | Source of funds declaration required for this transaction            | Complete source of funds form              |
| AML_006 | 403  | تم رفض المعاملة بسبب سياسة مكافحة غسل الأموال | Transaction rejected by AML policy due to risk score                 | Contact compliance                         |
| AML_007 | 422  | الغرض من التحويل مطلوب                        | Purpose of remittance required for cross-border transfer             | Specify the purpose of transfer            |

## Authentication / Authorization (AUTH)

| Code     | HTTP | Message (AR)                          | Cause                                                        | Resolution                                |
| -------- | ---- | ------------------------------------- | ------------------------------------------------------------ | ----------------------------------------- |
| AUTH_001 | 401  | رقم الهاتف أو كلمة المرور غير صحيحة   | Invalid phone number or PIN                                  | Check credentials and try again           |
| AUTH_002 | 429  | تم تجاوز عدد محاولات تسجيل الدخول     | Too many failed login attempts (5 per 30min)                 | Wait 30 minutes before trying again       |
| AUTH_003 | 403  | ليس لديك صلاحية للقيام بهذه العملية   | User lacks required permission/role                          | Request permissions from admin            |
| AUTH_004 | 401  | رمز الدخول غير صالح أو منتهي الصلاحية | JWT token is expired or invalid                              | Refresh token or re-authenticate          |
| AUTH_005 | 401  | رمز التحديث غير صالح                  | Refresh token is invalid, expired, or already used           | Re-authenticate with phone + PIN          |
| AUTH_006 | 401  | رمز التحقق غير صحيح                   | OTP code is incorrect                                        | Check code and try again                  |
| AUTH_007 | 429  | تم تجاوز عدد محاولات رمز التحقق       | Too many OTP verification attempts (5 per 15min)             | Wait 15 minutes or request new code       |
| AUTH_008 | 401  | رمز التحقق منتهي الصلاحية             | OTP code has expired (TTL: 5 minutes)                        | Request a new OTP code                    |
| AUTH_009 | 401  | الجهاز غير موثوق، يرجى تأكيد الهوية   | Device not recognized, MFA challenge required                | Complete MFA verification                 |
| AUTH_010 | 429  | تم تجاوز عدد طلبات رمز التحقق         | Too many OTP requests (3 per 15min)                          | Wait 15 minutes before requesting new OTP |
| AUTH_011 | 403  | تم إنهاء الجلسة                       | Session was terminated (password change, admin force-logout) | Re-authenticate                           |
| AUTH_012 | 401  | رمز PIN غير صحيح                      | PIN is incorrect                                             | Try again (3 attempts remaining)          |
| AUTH_013 | 422  | كلمة المرور الحالية غير صحيحة         | Current PIN does not match                                   | Enter correct current PIN                 |
| AUTH_014 | 422  | رمز PIN الجديد مطابق للقديم           | New PIN is identical to current PIN                          | Choose a different PIN                    |
| AUTH_015 | 422  | رمز PIN يجب أن يتكون من 4 إلى 6 أرقام | PIN must be 4-6 digits                                       | Enter PIN of valid length                 |
| AUTH_016 | 429  | تم تجاوز عدد محاولات تغيير رمز PIN    | Too many PIN change attempts (3 per 60min)                   | Wait 60 minutes                           |

## Settlement (SET)

| Code    | HTTP | Message (AR)                      | Cause                                               | Resolution                              |
| ------- | ---- | --------------------------------- | --------------------------------------------------- | --------------------------------------- |
| SET_001 | 500  | فشل التسوية اليومية               | Daily settlement batch processing failed            | Check settlement batch logs for details |
| SET_002 | 422  | التسوية قيد المعالجة بالفعل       | Settlement already in progress for this period      | Wait for current settlement to complete |
| SET_003 | 404  | لم يتم العثور على معاملات للتسوية | No pending transactions found for settlement period | Check transaction dates                 |
| SET_004 | 422  | مبلغ التسوية لا يتطابق مع السجلات | Settlement amount does not match calculated total   | Reconcile manually                      |
| SET_005 | 500  | فشل تسوية التاجر                  | Merchant settlement failed                          | Check merchant settlement config        |
| SET_006 | 500  | فشل تسوية الوكيل                  | Agent settlement failed                             | Check agent float reconciliation        |

## CFE (Core Financial Engine / CFE)

| Code    | HTTP | Message (AR)                                          | Cause                                 | Resolution                         |
| ------- | ---- | ----------------------------------------------------- | ------------------------------------- | ---------------------------------- |
| CFE_001 | 500  | فشل في نظام المعاملات المالية الأساسي                 | CFE posting transaction failed        | Check CFE system logs and retry    |
| CFE_002 | 500  | مهلة الاتصال بنظام المعاملات المالية الأساسي          | CFE connection timed out              | Check network and CFE availability |
| CFE_003 | 422  | نظام المعاملات المالية الأساسي يرفض المعاملة          | CFE rejected the transaction          | Check CFE error details            |
| CFE_004 | 500  | تنسيق بيانات غير صحيح لنظام المعاملات المالية الأساسي | Invalid data format sent to CFE       | Check message format specification |
| CFE_005 | 422  | المعاملة مكررة في نظام المعاملات المالية الأساسي      | Duplicate transaction detected by CFE | Check original transaction status  |

## Agent (AGT)

| Code    | HTTP | Message (AR)                           | Cause                                         | Resolution                      |
| ------- | ---- | -------------------------------------- | --------------------------------------------- | ------------------------------- |
| AGT_001 | 422  | رصيد الوكيل غير كافٍ                   | Agent float is insufficient for the operation | Fund agent float via operations |
| AGT_002 | 403  | الوكيل غير نشط                         | Agent account is suspended or inactive        | Contact support                 |
| AGT_003 | 422  | تجاوز الحد اليومي للوكيل               | Agent daily cash-in/cash-out limit exceeded   | Wait until tomorrow             |
| AGT_004 | 422  | مبلغ الإيداع النقدي أقل من الحد الأدنى | Cash-in amount below minimum (500 SYP)        | Increase amount                 |
| AGT_005 | 422  | مبلغ السحب النقدي أقل من الحد الأدنى   | Cash-out amount below minimum (500 SYP)       | Increase amount                 |
| AGT_006 | 422  | رصيد المحفظة لا يكفي للسحب النقدي      | User wallet balance insufficient for cash-out | Reduce cash-out amount          |
| AGT_007 | 422  | رمز الوكيل غير صحيح                    | Agent code is invalid                         | Verify agent code               |
| AGT_008 | 403  | المنطقة غير مدعومة للوكيل              | Agent location is outside supported area      | Contact support                 |
| AGT_009 | 422  | تجاوز الحد النقدي اليومي للوكيل        | Agent daily cash limit exceeded               | Wait until next day             |
| AGT_010 | 500  | فشل عملية التسوية النقدية              | Agent cash reconciliation failed              | Check agent float records       |

## Merchant (MER)

| Code    | HTTP | Message (AR)                  | Cause                                              | Resolution               |
| ------- | ---- | ----------------------------- | -------------------------------------------------- | ------------------------ |
| MER_001 | 403  | التاجر غير نشط                | Merchant account is suspended or not yet activated | Contact support          |
| MER_002 | 422  | رمز التاجر غير صحيح           | Merchant code is invalid                           | Verify merchant code     |
| MER_003 | 422  | مبلغ الدفع أقل من الحد الأدنى | Payment amount below minimum for merchant          | Increase payment amount  |
| MER_004 | 422  | تجاوز الحد اليومي للتاجر      | Merchant daily payment volume limit exceeded       | Wait until tomorrow      |
| MER_005 | 500  | فشل تسوية التاجر              | Merchant settlement processing failed              | Check settlement service |

## User / Account (USR)

| Code    | HTTP | Message (AR)                   | Cause                                                      | Resolution                               |
| ------- | ---- | ------------------------------ | ---------------------------------------------------------- | ---------------------------------------- |
| USR_001 | 404  | المستخدم غير موجود             | User not found in the system                               | Verify user ID or phone number           |
| USR_002 | 409  | رقم الهاتف مسجل مسبقاً         | Phone number is already registered                         | Login or use password recovery           |
| USR_003 | 409  | البريد الإلكتروني مسجل مسبقاً  | Email is already registered                                | Login or use password recovery           |
| USR_004 | 403  | الحساب موقوف                   | Account is suspended by admin or compliance                | Contact support for reinstatement        |
| USR_005 | 403  | الحساب محظور                   | Account is permanently banned                              | This decision is final                   |
| USR_006 | 422  | المستخدم غير مؤهل لهذه العملية | User does not meet eligibility criteria for this operation | Check KYC level and account age          |
| USR_007 | 422  | المستخدم دون السن القانوني     | User is under 18 years of age (minors not allowed)         | Cannot register                          |
| USR_008 | 422  | الاسم مطلوب                    | Full name is required                                      | Provide full name                        |
| USR_009 | 422  | البريد الإلكتروني غير صالح     | Email format is invalid                                    | Provide valid email                      |
| USR_010 | 422  | رقم الهاتف غير صالح            | Phone number format is invalid                             | Provide valid Syrian phone number (+963) |

## KYC (KYC)

| Code    | HTTP | Message (AR)                           | Cause                                            | Resolution                                   |
| ------- | ---- | -------------------------------------- | ------------------------------------------------ | -------------------------------------------- |
| KYC_001 | 422  | لم يتم إكمال التحقق من الهوية          | KYC verification not yet completed               | Complete KYC level 1 first                   |
| KYC_002 | 422  | مستوى التحقق من الهوية غير كافٍ        | KYC level insufficient for this operation        | Upgrade to required KYC level                |
| KYC_003 | 422  | تم رفض وثائق التحقق من الهوية          | KYC document verification failed                 | Submit new documents following guidelines    |
| KYC_004 | 422  | وثائق التحقق من الهوية منتهية الصلاحية | KYC document is expired                          | Upload a valid document                      |
| KYC_005 | 422  | فشل التحقق من الصورة                   | Selfie does not match ID document photo          | Retake photo in good lighting                |
| KYC_006 | 422  | فشل التحقق من البطاقة                  | ID card OCR verification failed                  | Ensure document is fully visible and clear   |
| KYC_007 | 422  | إثبات العنوان مطلوب                    | Proof of address required for KYC level 2        | Upload utility bill or bank statement        |
| KYC_008 | 422  | إثبات العنوان منتهي الصلاحية           | Proof of address document is older than 3 months | Upload a recent document                     |
| KYC_009 | 422  | وثيقة التحقق من الهوية قيد المراجعة    | KYC documents are under manual review            | Wait for review (1-2 business days)          |
| KYC_010 | 429  | تم تجاوز عدد محاولات رفع المستندات     | Too many KYC submission attempts (5 per day)     | Try again tomorrow                           |
| KYC_011 | 422  | نوع المستند غير مدعوم                  | Document type is not accepted                    | Use a supported document type (ID, passport) |

## Notification (NOT)

| Code    | HTTP | Message (AR)                       | Cause                                           | Resolution                             |
| ------- | ---- | ---------------------------------- | ----------------------------------------------- | -------------------------------------- |
| NOT_001 | 422  | فشل إرسال الإشعار                  | Push notification delivery failed               | Check device token validity            |
| NOT_002 | 422  | فشل إرسال رسالة SMS                | SMS delivery failed                             | Check phone number and try again       |
| NOT_003 | 422  | فشل إرسال البريد الإلكتروني        | Email delivery failed                           | Check email address and try again      |
| NOT_004 | 503  | خدمة الإشعارات غير متاحة           | Notification service is temporarily unavailable | Retry later                            |
| NOT_005 | 429  | تم تجاوز عدد الإشعارات المسموح بها | Push notification rate limit exceeded (2/hour)  | Wait before sending more notifications |

## General / System (GEN)

| Code    | HTTP | Message (AR)                     | Cause                                                        | Resolution                   |
| ------- | ---- | -------------------------------- | ------------------------------------------------------------ | ---------------------------- |
| GEN_001 | 500  | خطأ داخلي في الخادم              | Unexpected server error                                      | Try again or contact support |
| GEN_002 | 503  | الخدمة غير متاحة حالياً          | Service temporarily unavailable (maintenance or overload)    | Try again later              |
| GEN_003 | 400  | طلب غير صالح                     | Request validation failed                                    | Check request parameters     |
| GEN_004 | 429  | تم تجاوز الحد المسموح للطلبات    | API rate limit exceeded (general)                            | Slow down request rate       |
| GEN_005 | 404  | الصفحة أو المورد غير موجود       | Requested resource not found                                 | Check URL or resource ID     |
| GEN_006 | 405  | طريقة الطلب غير مسموح بها        | HTTP method not allowed for this endpoint                    | Use correct HTTP method      |
| GEN_007 | 413  | حجم الطلب كبير جداً              | Request payload exceeds maximum size                         | Reduce payload size          |
| GEN_008 | 422  | معامل مكرر                       | Request with duplicate idempotency key but different payload | Use correct idempotency key  |
| GEN_009 | 408  | انتهت مهلة الطلب                 | Request timed out                                            | Check network and retry      |
| GEN_010 | 422  | مفتاح idempotency منتهي الصلاحية | Idempotency key has expired (TTL: 24 hours)                  | Generate new key             |

## Error Response Format

### Standard Error Response

```json
{
  "success": false,
  "error_code": "WAL_001",
  "message": "الرصيد غير كافٍ",
  "message_en": "Insufficient balance",
  "details": {
    "current_balance": 5000,
    "required_amount": 10000,
    "shortfall": 5000
  },
  "correlation_id": "c123e4567-e89b-12d3-a456-426614174000",
  "timestamp": "2025-05-29T10:30:00.123Z"
}
```

### Validation Error Response

```json
{
  "success": false,
  "error_code": "GEN_003",
  "message": "طلب غير صالح",
  "message_en": "Invalid request",
  "errors": {
    "amount": [
      {
        "error_code": "WAL_003",
        "message": "المبلغ أقل من الحد الأدنى",
        "message_en": "Amount below minimum"
      }
    ],
    "phone": [
      {
        "error_code": "USR_010",
        "message": "رقم الهاتف غير صالح",
        "message_en": "Invalid phone number"
      }
    ]
  },
  "correlation_id": "c123e4567-e89b-12d3-a456-426614174000",
  "timestamp": "2025-05-29T10:30:00.123Z"
}
```

## Error Code Index

| Domain    | Code Range | Count   |
| --------- | ---------- | ------- |
| WAL       | 001-015    | 15      |
| FX        | 001-008    | 8       |
| REM       | 001-008    | 8       |
| AML       | 001-007    | 7       |
| AUTH      | 001-016    | 16      |
| SET       | 001-006    | 6       |
| CFE       | 001-005    | 5       |
| AGT       | 001-010    | 10      |
| MER       | 001-005    | 5       |
| USR       | 001-010    | 10      |
| KYC       | 001-011    | 11      |
| NOT       | 001-005    | 5       |
| GEN       | 001-010    | 10      |
| **Total** |            | **116** |
