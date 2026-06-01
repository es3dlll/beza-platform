# 07 - قواعد البيانات (Constraints)

## قيود المفاتيح الخارجية

| الجدول | المفتاح الخارجي | المرجع |
|--------|----------------|--------|
| wallets | user_id | users.id |
| transactions | from_wallet_id | wallets.id |
| transactions | to_wallet_id | wallets.id |
| merchants | user_id | users.id |
| merchant_products | merchant_id | merchants.id |
| merchant_orders | user_id | users.id |
| merchant_orders | merchant_id | merchants.id |
| deals | created_by | users.id |
| deal_investments | deal_id | deals.id |
| deal_investments | user_id | users.id |
| cards | user_id | users.id |
| card_transactions | card_id | cards.id |
| agents | user_id | users.id |
| agent_transactions | agent_id | agents.id |
| referrals | referrer_id | users.id |
| referred_id | referrals | users.id |
| kyc_documents | user_id | users.id |
| disputes | user_id | users.id |
| disputes | transaction_id | transactions.id |
| audit_logs | user_id | users.id |

## القيود الفريدة

| الجدول | العمود | السبب |
|--------|--------|-------|
| users | email | لا يمكن تكرار البريد الإلكتروني |
| users | phone | لا يمكن تكرار رقم الهاتف |
| users | uuid | المعرف العام يجب أن يكون فريدا |
| wallets | wallet_number | رقم المحفظة فريد |
| wallets | (user_id, currency) | محفظة واحدة لكل عملة للمستخدم |
| transactions | reference_number | الرقم المرجعي فريد |
| agents | agent_code | كود الوكيل فريد |
| referrals | code | كود الإحالة فريد |
| failed_jobs | uuid | معرف الوظيفة الفاشلة فريد |

## CHECK Constraints (MySQL 8.0)

```sql
ALTER TABLE wallets ADD CONSTRAINT chk_balance_non_negative CHECK (balance >= 0);
ALTER TABLE wallets ADD CONSTRAINT chk_frozen_non_negative CHECK (frozen_balance >= 0);
ALTER TABLE transactions ADD CONSTRAINT chk_amount_positive CHECK (amount > 0);
ALTER TABLE deals ADD CONSTRAINT chk_roi_positive CHECK (roi_percentage >= 0);
```
