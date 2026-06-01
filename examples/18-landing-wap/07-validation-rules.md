# قواعد التحقق — WAP API

## POST /api/v1/wap/auth/login
| الحقل | القاعدة |
|-------|---------|
| email | `required\|email\|exists:users,email` |
| password | `required\|string\|min:8` |
| device | `nullable\|string\|in:wap` |

## POST /api/v1/wap/wallet/transfer
| الحقل | القاعدة |
|-------|---------|
| receiver_phone | `required\|string\|exists:users,phone` |
| amount | `required\|integer\|min:100` (بالفلس) |
| currency | `required\|in:SYP,USD` |
| idempotency_key | `required\|string\|uuid\|unique:transactions,idempotency_key` |
| note | `nullable\|string\|max:255` |

## GET /api/v1/wap/wallet/balance
| Parameter | القاعدة |
|-----------|---------|
| format | `nullable\|in:minimal,full` |

## GET /api/v1/wap/merchant/qr
| Parameter | القاعدة |
|-----------|---------|
| amount | `nullable\|integer\|min:100` |
| format | `nullable\|in:svg,json` |
