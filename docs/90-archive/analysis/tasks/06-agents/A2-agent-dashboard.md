# AG2 - لوحة معلومات الوكيل

## الوصف
عرض أرصدة الوكيل النقدية وآخر المعاملات.

## المخرجات
| الحقل | الوصف |
|-------|-------|
| cash_balance_syp | الرصيد النقدي SYP |
| cash_balance_usd | الرصيد النقدي USD |
| wallet_balance_syp | رصيد محفظة Beza SYP |
| wallet_balance_usd | رصيد محفظة Beza USD |
| today_transactions_count | عدد معاملات اليوم |
| today_commission | عمولات اليوم |
| recent_transactions | آخر 10 معاملات |

## API Endpoint
`GET /api/v1/agent/dashboard`

## واجهات المستخدم
- Flutter Agent App: AgentDashboard

## اختبارات
- عرض اللوحة ← 200 مع بيانات
- عرض اللوحة لمستخدم غير وكيل ← 403
