# W2 - عرض الرصيد

## الوصف
عرض أرصدة المحافظ للمستخدم (SYP + USD).

## المدخلات
- Bearer token

## المخرجات
| الحقل | الوصف |
|-------|-------|
| wallets | مصفوفة: currency, balance, frozen_balance, wallet_number |

## سير العمل
1. auth()->user()->wallets()->get()
2. Response

## API Endpoint
`GET /api/v1/wallet/balance`

## واجهات المستخدم
- Flutter: BalanceCard widget
- React SPA: BalanceCard component

## اختبارات
- عرض الرصيد لمستخدم مسجل ← 200 بمحفظتين
- عرض الرصيد بدون توكن ← 401
